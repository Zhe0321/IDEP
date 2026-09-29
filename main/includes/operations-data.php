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
                'waterLevel' => number_format($hasil, 2) . ' m',
                'waterValue' => $hasil,
                'quality' => ($h1 === 0.0 && $h2 === 0.0) ? 'No reading' : 'Good',
                'export' => 'CSV',
            ];
        }
    } catch (Throwable) {
        $measurementRecords = [];
    }
}

if ($measurementRecords === []) {
    $measurementRecords = [
    ['date' => '14 Jul', 'wellId' => 'RW-01', 'village' => 'Ubud', 'waterLevel' => '2.31 m', 'quality' => 'Good', 'export' => 'CSV'],
    ['date' => '14 Jul', 'wellId' => 'RW-07', 'village' => 'Tabanan', 'waterLevel' => '1.82 m', 'quality' => 'Good', 'export' => 'Excel'],
    ['date' => '14 Jul', 'wellId' => 'RW-12', 'village' => 'Denpasar', 'waterLevel' => '3.05 m', 'quality' => 'Review', 'export' => 'CSV'],
    ['date' => '13 Jul', 'wellId' => 'RW-18', 'village' => 'Gianyar', 'waterLevel' => '2.10 m', 'quality' => 'Good', 'export' => 'Excel'],
    ['date' => '13 Jul', 'wellId' => 'RW-23', 'village' => 'Badung', 'waterLevel' => '1.67 m', 'quality' => 'Good', 'export' => 'CSV'],
    ];
}

$alerts = [
    [
        'title' => 'Inactive',
        'wellId' => 'RW-12',
        'village' => 'Denpasar',
        'summary' => 'Inactive',
        'severity' => 'critical',
        'state' => 'Open',
        'alert' => 'No Activity',
        'signal' => 'Weak',
        'lastTransmission' => '35 min ago',
        'action' => 'Field check',
    ],
    [
        'title' => 'Weak Signal',
        'wellId' => 'RW-31',
        'village' => 'Jembrana',
        'summary' => 'Signal below threshold',
        'severity' => 'warning',
        'state' => 'Open',
        'alert' => 'Low Signal',
        'signal' => 'Weak',
        'lastTransmission' => '20 min ago',
        'action' => 'Inspect antenna',
    ],
    [
        'title' => 'Missing Transmission',
        'wellId' => 'RW-04',
        'village' => 'Sanur',
        'summary' => 'No data for 4 hours',
        'severity' => 'critical',
        'state' => 'Open',
        'alert' => 'Missing Data',
        'signal' => 'No Signal',
        'lastTransmission' => '4 hours ago',
        'action' => 'Check sensor power',
    ],
    [
        'title' => 'Data Quality Review',
        'wellId' => 'RW-07',
        'village' => 'Tabanan',
        'summary' => 'Unexpected level jump',
        'severity' => 'warning',
        'state' => 'Open',
        'alert' => 'Quality Review',
        'signal' => 'Strong',
        'lastTransmission' => '15 min ago',
        'action' => 'Validate reading',
    ],
    [
        'title' => 'Normalised',
        'wellId' => 'RW-18',
        'village' => 'Gianyar',
        'summary' => 'Back within threshold',
        'severity' => 'resolved',
        'state' => 'Resolved',
        'alert' => 'Recovered',
        'signal' => 'Strong',
        'lastTransmission' => '10 min ago',
        'action' => 'No action required',
    ],
];

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
            w.well_name AS name,
            COALESCE(dc.name, '—') AS city,
            COALESCE(s.id_device, '—') AS mac,
            w.installation_date AS date,
            CASE
                WHEN w.installation_date IS NOT NULL AND w.installation_date != ''
                THEN strftime('%d/%m/%Y', w.installation_date)
                ELSE '—'
            END AS displayDate,
            COALESCE(w.installer_name, '—') AS installer,
            COALESCE(s.sensor_type, '—') AS type,
            w.longitude,
            w.latitude
        FROM wells w
        LEFT JOIN village v ON v.id = w.village_id
        LEFT JOIN sub_district sd ON sd.id = v.sub_district_id
        LEFT JOIN district_city dc ON dc.id = sd.district_id
        LEFT JOIN sensors s ON s.id = w.sensor_id
        ORDER BY w.id DESC
    ")->fetchAll();
} catch (Throwable $e) {
    $hardwareRecords = [];
    $hardwareQueryErrorMessage = $e->getMessage();
}

// TEMP DEBUG
if (isset($_GET['debug_hw'])) {
    echo '<pre style="background:#efe;padding:10px;position:relative;z-index:9999;">';
    echo "Error: " . ($hardwareQueryErrorMessage ?? 'none') . "\n\n";
    echo "Row count: " . count($hardwareRecords) . "\n\n";
    print_r($hardwareRecords);
    echo '</pre>';
}