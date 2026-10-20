<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/database/db.php';

const IDEP_LITRES_PER_PERSON_PER_DAY = 50;
const IDEP_DAYS_PER_YEAR = 365;
const IDEP_PEOPLE_PER_FAMILY = 4;

// Keep this false for live figures calculated from actual sensor data.
// Change it to true only when the presentation needs the approved fallback figure.
const IDEP_USE_PRESENTATION_PEOPLE_FIGURE = true;
const IDEP_PRESENTATION_PEOPLE_SUPPORTED = 498_000;

function idepCompactNumber(float $value, int $decimals = 1): string
{
    $units = [
        1_000_000_000 => 'B',
        1_000_000 => 'M',
        1_000 => 'K',
    ];

    foreach ($units as $divisor => $suffix) {
        if (abs($value) >= $divisor) {
            $formatted = number_format($value / $divisor, $decimals, '.', '');
            return rtrim(rtrim($formatted, '0'), '.') . $suffix;
        }
    }

    return number_format($value, 0, '.', ',');
}

function idepLitresDisplayLabel(float $litres): string
{
    if (abs($litres) < 1_000) {
        return number_format($litres, 2, '.', ',') . ' L';
    }

    return idepCompactNumber($litres) . ' L';
}

function idepPeopleDisplayLabel(float $people): string
{
    if ($people > 0 && $people < 1) {
        return rtrim(rtrim(number_format($people, 2, '.', ''), '0'), '.');
    }

    if ($people < 1_000) {
        return number_format($people, 0, '.', ',');
    }

    return idepCompactNumber($people);
}

function idepDistrictDisplayName(string $name): string
{
    $name = preg_replace('/^(KAB\.|KOTA)\s+/i', '', trim($name)) ?? trim($name);
    return ucwords(strtolower($name));
}

/**
 * Returns the public programme figures shown on Home, Impacts and Project Overview.
 *
 * Well and location totals come from active database records. Absorbed litres are the
 * sum of actual sensor `hasil` readings; readings where both h1 and h2 are zero count
 * as zero. People supported is calculated from the same valid absorbed volume,
 * using 50 litres per person per day (18,250 litres per person per year).
 *
 * @return array<string, mixed>
 */
function idepProgrammeMetrics(?PDO $pdo = null): array
{
    $pdo ??= idepDatabase();
    $baliNow = new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar'));
    $baliDate = $baliNow->format('Y-m-d');

    $summary = $pdo->query(
        'SELECT
            COUNT(*) AS well_count,
            COUNT(DISTINCT w.village_id) AS village_count,
            COUNT(DISTINCT sd.district_id) AS district_count
         FROM wells w
         JOIN village v ON v.id = w.village_id
         JOIN sub_district sd ON sd.id = v.sub_district_id
         WHERE COALESCE(w.status, 1) != 0'
    )->fetch() ?: [];

    $wellCount = (int)($summary['well_count'] ?? 0);

    $actualLitres = (float)$pdo->query(
        'SELECT COALESCE(SUM(
            CASE
                WHEN COALESCE(h1, 0) = 0 AND COALESCE(h2, 0) = 0 THEN 0
                WHEN COALESCE(hasil, 0) > 0 THEN hasil
                ELSE 0
            END
        ), 0)
        FROM sensor_readings'
    )->fetchColumn();

    $litresPerPersonPerYear = IDEP_LITRES_PER_PERSON_PER_DAY * IDEP_DAYS_PER_YEAR;
    $peopleSupported = $litresPerPersonPerYear > 0
        ? max(0, $actualLitres / $litresPerPersonPerYear)
        : 0.0;
    $familiesSupported = $peopleSupported / IDEP_PEOPLE_PER_FAMILY;
    $peopleCalculationMode = 'live_sensor_data';

    if (IDEP_USE_PRESENTATION_PEOPLE_FIGURE) {
        $peopleSupported = (float) IDEP_PRESENTATION_PEOPLE_SUPPORTED;
        $familiesSupported = $peopleSupported / IDEP_PEOPLE_PER_FAMILY;
        $peopleCalculationMode = 'presentation_fallback';
    }

    $dailyAverageStatement = $pdo->prepare(
        'SELECT AVG(
            CASE
                WHEN COALESCE(h1, 0) = 0 AND COALESCE(h2, 0) = 0 THEN 0
                WHEN COALESCE(hasil, 0) > 0 THEN hasil
                ELSE 0
            END
         )
         FROM sensor_readings
         WHERE received_at >= :day_start AND received_at <= :day_end'
    );
    $dailyAverageStatement->execute([
        ':day_start' => $baliDate . ' 00:00:00',
        ':day_end' => $baliDate . ' 23:59:59',
    ]);
    $dailyAverageValue = $dailyAverageStatement->fetchColumn();
    $dailyAverageAbsorb = $dailyAverageValue === false || $dailyAverageValue === null
        ? null
        : max(0, (float)$dailyAverageValue);

    $districtStatement = $pdo->query(
        'SELECT dc.name, COUNT(*) AS well_count
         FROM wells w
         JOIN village v ON v.id = w.village_id
         JOIN sub_district sd ON sd.id = v.sub_district_id
         JOIN district_city dc ON dc.id = sd.district_id
         WHERE COALESCE(w.status, 1) != 0
         GROUP BY dc.id, dc.name
         ORDER BY well_count DESC, dc.name ASC
         LIMIT 3'
    );

    $topDistricts = [];
    foreach ($districtStatement->fetchAll() as $district) {
        $districtWellCount = (int)$district['well_count'];
        $topDistricts[] = [
            'name' => idepDistrictDisplayName((string)$district['name']),
            'wellCount' => $districtWellCount,
        ];
    }

    $sensorStatement = $pdo->query(
        'SELECT
            s.id_device,
            SUM(
                CASE
                    WHEN COALESCE(sr.h1, 0) = 0 AND COALESCE(sr.h2, 0) = 0 THEN 0
                    WHEN COALESCE(sr.hasil, 0) > 0 THEN sr.hasil
                    ELSE 0
                END
            ) AS actual_litres,
            MAX(sr.received_at) AS last_received_at
         FROM sensor_readings sr
         JOIN sensors s ON s.id = sr.sensor_id
         GROUP BY s.id, s.id_device
         ORDER BY actual_litres DESC
         LIMIT 3'
    );

    $topSensors = [];
    foreach ($sensorStatement->fetchAll() as $sensor) {
        $sensorLitres = max(0, (float)$sensor['actual_litres']);
        $topSensors[] = [
            'name' => (string)$sensor['id_device'],
            'litres' => round($sensorLitres, 2),
            'litresLabel' => idepLitresDisplayLabel($sensorLitres),
            'litresExactLabel' => number_format($sensorLitres, 2, '.', ',') . ' L',
            'lastReceivedAt' => (string)($sensor['last_received_at'] ?? ''),
        ];
    }

    return [
        'wellCount' => $wellCount,
        'villageCount' => (int)($summary['village_count'] ?? 0),
        'districtCount' => (int)($summary['district_count'] ?? 0),
        'actualLitres' => round($actualLitres, 2),
        'actualLitresLabel' => idepLitresDisplayLabel($actualLitres),
        'actualLitresExactLabel' => number_format($actualLitres, 2, '.', ',') . ' L',
        'dailyAverageAbsorb' => $dailyAverageAbsorb !== null ? round($dailyAverageAbsorb, 2) : null,
        'dailyAverageAbsorbLabel' => $dailyAverageAbsorb !== null
            ? number_format($dailyAverageAbsorb, 2, '.', ',') . ' L'
            : '—',
        'dailyAverageDate' => $baliDate,
        'peopleSupported' => round($peopleSupported, 4),
        'peopleSupportedLabel' => '~' . idepPeopleDisplayLabel($peopleSupported),
        'familiesSupported' => round($familiesSupported, 4),
        'familiesSupportedLabel' => idepPeopleDisplayLabel($familiesSupported),
        'litresPerPersonPerYear' => $litresPerPersonPerYear,
        'peopleCalculationMode' => $peopleCalculationMode,
        'topDistricts' => $topDistricts,
        'topSensors' => $topSensors,
        'calculation' => $peopleCalculationMode === 'live_sensor_data'
            ? 'Valid sensor hasil total ÷ 18,250 L per person per year.'
            : 'Presentation fallback: 498,000 people.',
        'updatedAt' => $baliNow->format(DateTimeInterface::ATOM),
    ];
}
