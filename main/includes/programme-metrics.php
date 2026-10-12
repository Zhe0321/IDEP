<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/database/db.php';

const IDEP_REFERENCE_WELL_COUNT = 76;
const IDEP_REFERENCE_ANNUAL_LITRES = 9_100_000_000;
const IDEP_REFERENCE_PEOPLE_SUPPORTED = 498_000;

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

function idepDistrictDisplayName(string $name): string
{
    $name = preg_replace('/^(KAB\.|KOTA)\s+/i', '', trim($name)) ?? trim($name);
    return ucwords(strtolower($name));
}

/**
 * Returns the public programme figures shown on Home, Impacts and Project Overview.
 *
 * The original approved impact estimate is 9.1B litres and 498K people for 76 wells.
 * Until every historical well has a measured capacity, estimates are scaled from that
 * baseline using the current active well count in the database.
 *
 * @return array<string, mixed>
 */
function idepProgrammeMetrics(?PDO $pdo = null): array
{
    $pdo ??= idepDatabase();

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
    $annualLitres = $wellCount * (IDEP_REFERENCE_ANNUAL_LITRES / IDEP_REFERENCE_WELL_COUNT);
    $peopleSupported = (int)round(
        $wellCount * (IDEP_REFERENCE_PEOPLE_SUPPORTED / IDEP_REFERENCE_WELL_COUNT)
    );
    $familiesSupported = (int)round($peopleSupported / 4);

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
        $districtAnnualLitres = $districtWellCount
            * (IDEP_REFERENCE_ANNUAL_LITRES / IDEP_REFERENCE_WELL_COUNT);
        $topDistricts[] = [
            'name' => idepDistrictDisplayName((string)$district['name']),
            'wellCount' => $districtWellCount,
            'annualLitres' => (int)round($districtAnnualLitres),
            'annualLitresLabel' => idepCompactNumber($districtAnnualLitres) . ' L',
        ];
    }

    return [
        'wellCount' => $wellCount,
        'villageCount' => (int)($summary['village_count'] ?? 0),
        'districtCount' => (int)($summary['district_count'] ?? 0),
        'annualLitres' => (int)round($annualLitres),
        'annualLitresLabel' => idepCompactNumber($annualLitres) . ' L',
        'peopleSupported' => $peopleSupported,
        'peopleSupportedLabel' => '~' . idepCompactNumber($peopleSupported, 0),
        'familiesSupported' => $familiesSupported,
        'familiesSupportedLabel' => idepCompactNumber($familiesSupported, 0),
        'topDistricts' => $topDistricts,
        'calculation' => 'Scaled from the approved 76-well impact estimate.',
        'updatedAt' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar')))
            ->format(DateTimeInterface::ATOM),
    ];
}
