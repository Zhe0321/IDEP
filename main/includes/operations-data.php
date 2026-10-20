<?php
declare(strict_types=1);

$measurementRecords = [];

$databaseBootstrap = dirname(__DIR__, 2) . '/database/db.php';
if (($currentPage ?? '') === 'historical' && is_file($databaseBootstrap)) {
    try {
        require_once $databaseBootstrap;
        $deviceLocations = [];
        foreach ($wells as $well) {
            if (isset($well['deviceId'])) {
                $deviceLocations[$well['deviceId']] = $well;
            }
        }

        $readingStatement = idepDatabase()->query(
            'SELECT sr.h1, sr.h2, sr.hasil, sr.received_at, s.id_device
             FROM sensor_readings sr
             JOIN sensors s ON s.id = sr.sensor_id
             ORDER BY sr.received_at DESC, sr.id DESC
             LIMIT 100'
        );

        foreach ($readingStatement->fetchAll() as $reading) {
            $well = $deviceLocations[$reading['id_device']] ?? null;
            $receivedAt = (string) ($reading['received_at'] ?? '');
            $receivedDate = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $receivedAt)
                ?: new DateTimeImmutable($receivedAt ?: 'now');
            $h1 = (float) ($reading['h1'] ?? 0);
            $h2 = (float) ($reading['h2'] ?? 0);
            $hasil = ($h1 === 0.0 && $h2 === 0.0) ? 0.0 : (float) ($reading['hasil'] ?? 0);

            $measurementRecords[] = [
                'date' => $receivedDate->format('d M Y H:i'),
                'dateIso' => $receivedDate->format('Y-m-d\TH:i:s'),
                'wellId' => $well['id'] ?? (string) $reading['id_device'],
                'village' => $well['city'] ?? 'Unassigned',
                'wellType' => $well['wellType'] ?? '',
                'waterAbsorb' => number_format($hasil, 2) . ' L',
                'waterValue' => $hasil,
                'quality' => ($h1 === 0.0 && $h2 === 0.0) ? 'No reading' : 'Good',
                'export' => 'CSV',
            ];
        }
    } catch (Throwable) {
        $measurementRecords = [];
    }
}

$alerts = [];
$alertDatabaseBootstrap = dirname(__DIR__, 2) . '/database/db.php';

if (is_file($alertDatabaseBootstrap)) {
    try {
        require_once $alertDatabaseBootstrap;

        $alertStatement = idepDatabase()->query(
            'SELECT
                w.well_code,
                COALESCE(v.name, dc.name, \'Unassigned\') AS village_name,
                s.id_device,
                COALESCE(s.status, 1) AS sensor_status,
                latest.h1,
                latest.h2,
                latest.hasil,
                latest.received_at
             FROM wells w
             JOIN sensors s ON s.id = w.sensor_id
             LEFT JOIN village v ON v.id = w.village_id
             LEFT JOIN sub_district sd ON sd.id = v.sub_district_id
             LEFT JOIN district_city dc ON dc.id = sd.district_id
             LEFT JOIN sensor_readings latest ON latest.id = (
                SELECT sr.id
                FROM sensor_readings sr
                WHERE sr.sensor_id = s.id
                ORDER BY sr.received_at DESC, sr.id DESC
                LIMIT 1
             )
             WHERE COALESCE(w.status, 1) != 0
             ORDER BY w.well_code'
        );

        $baliTimezone = new DateTimeZone('Asia/Makassar');
        $baliNow = new DateTimeImmutable('now', $baliTimezone);
        $ageLabel = static function (?int $seconds): string {
            if ($seconds === null) {
                return 'Never';
            }
            if ($seconds < 60) {
                return 'Just now';
            }
            if ($seconds < 3600) {
                $minutes = max(1, (int) floor($seconds / 60));
                return $minutes . ' min ago';
            }
            if ($seconds < 86400) {
                $hours = max(1, (int) floor($seconds / 3600));
                return $hours . ($hours === 1 ? ' hour ago' : ' hours ago');
            }
            $days = max(1, (int) floor($seconds / 86400));
            return $days . ($days === 1 ? ' day ago' : ' days ago');
        };

        foreach ($alertStatement->fetchAll() as $sensor) {
            $receivedAt = null;
            $secondsSinceReading = null;
            $receivedAtValue = trim((string) ($sensor['received_at'] ?? ''));
            if ($receivedAtValue !== '') {
                $receivedAt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $receivedAtValue, $baliTimezone);
                if ($receivedAt !== false) {
                    $secondsSinceReading = max(0, $baliNow->getTimestamp() - $receivedAt->getTimestamp());
                }
            }

            $lastTransmission = $receivedAt instanceof DateTimeImmutable
                ? $receivedAt->format('d M Y H:i') . ' WITA (' . $ageLabel($secondsSinceReading) . ')'
                : 'Never';
            $baseAlert = [
                'wellId' => (string) $sensor['well_code'],
                'village' => (string) $sensor['village_name'],
                'state' => 'Open',
                'lastTransmission' => $lastTransmission,
            ];

            if ((int) $sensor['sensor_status'] === 0) {
                $alerts[] = $baseAlert + [
                    'title' => 'Inactive',
                    'summary' => 'Sensor is disabled',
                    'severity' => 'critical',
                    'alert' => 'No Activity',
                    'signal' => 'Inactive',
                    'action' => 'Activate or inspect the sensor',
                ];
                continue;
            }

            if ($secondsSinceReading === null || $secondsSinceReading > 180) {
                $alerts[] = $baseAlert + [
                    'title' => 'Missing Transmission',
                    'summary' => $secondsSinceReading === null
                        ? 'No sensor data received'
                        : 'Last data ' . $ageLabel($secondsSinceReading),
                    'severity' => 'critical',
                    'alert' => 'Missing Data',
                    'signal' => 'No Signal',
                    'action' => 'Check sensor power and connection',
                ];
                continue;
            }

            $h1 = (float) ($sensor['h1'] ?? 0);
            $h2 = (float) ($sensor['h2'] ?? 0);
            $hasil = (float) ($sensor['hasil'] ?? 0);
            if (($h1 === 0.0 && $h2 === 0.0 && $hasil !== 0.0) || $hasil < 0.0) {
                $alerts[] = $baseAlert + [
                    'title' => 'Data Quality Review',
                    'summary' => 'Reading does not match the sensor rules',
                    'severity' => 'warning',
                    'alert' => 'Quality Review',
                    'signal' => 'Online',
                    'action' => 'Validate h1, h2 and hasil',
                ];
            }
        }
    } catch (Throwable $error) {
        error_log('Alert query failed: ' . $error->getMessage());
        $alerts = [];
    }
}

$generatedReports = [
    ['name' => 'Ubud Groundwater Summary', 'period' => 'Mar–May 2026', 'createdBy' => 'Field Team', 'date' => '14 Jul', 'status' => 'Ready', 'export' => 'PDF'],
    ['name' => 'Sensor Status Overview', 'period' => 'Last 7 Days', 'createdBy' => 'Research Admin', 'date' => '13 Jul', 'status' => 'Ready', 'export' => 'Excel'],
    ['name' => 'Recharge Well Review', 'period' => 'Q2 2026', 'createdBy' => 'IDEP Team', 'date' => '10 Jul', 'status' => 'Draft', 'export' => 'CSV'],
];

// Hardware records now come from the live database instead of a hardcoded list
require_once __DIR__ . '/../../database/db.php';

try {
    $pdo = idepDatabase();
    $hardwareRecords = $pdo->query("
        SELECT
            w.id AS wellId,
            w.well_code AS wellCode,
            w.well_name AS name,
            COALESCE(dc.name, '—') AS city,
            COALESCE(s.id_device, '—') AS mac,
            s.id_device AS sensorDeviceId,
            w.village_id AS villageId,
            sd.id AS subDistrictId,
            dc.id AS districtId,
            p.id AS provinceId,
            w.installation_date AS date,
            CASE
                WHEN w.installation_date IS NOT NULL AND w.installation_date != ''
                THEN strftime('%d/%m/%Y', w.installation_date)
                ELSE '—'
            END AS displayDate,
            COALESCE(w.installer_name, '—') AS installer,
            COALESCE(w.well_type, s.sensor_type, '—') AS type,
            w.well_depth AS depth,
            COALESCE(w.absorption_capacity, '—') AS absorption,
            COALESCE(w.address, '—') AS address,
            w.longitude,
            w.latitude,
            w.photo_path AS photo
        FROM wells w
        LEFT JOIN village v ON v.id = w.village_id
        LEFT JOIN sub_district sd ON sd.id = v.sub_district_id
        LEFT JOIN district_city dc ON dc.id = sd.district_id
        LEFT JOIN province p ON p.id = dc.province_id
        LEFT JOIN sensors s ON s.id = w.sensor_id
        ORDER BY w.id DESC
    ")->fetchAll();
    foreach ($hardwareRecords as &$record) {
        $city = preg_replace('/^(KAB\.|KOTA)\s+/i', '', (string)$record['city']) ?? (string)$record['city'];
        $record['city'] = ucwords(strtolower($city));
    }
    unset($record);
} catch (Throwable $e) {
    $hardwareRecords = [];
    error_log('Hardware query failed: ' . $e->getMessage());
}
