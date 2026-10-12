<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Imports the 81 records supplied in LIST OF RECHARGE WELLS IN BALI.xlsx.
 *
 * The normalized source records already live in main/includes/well-data.php;
 * this importer makes the database the shared source used by registration,
 * monitoring and map pages. Re-running it is safe because well_code is unique.
 */

$pdo = idepDatabase();

$wellColumns = array_column($pdo->query('PRAGMA table_info(wells)')->fetchAll(), 'name');
$requiredColumns = [
    'well_type' => 'TEXT',
    'absorption_capacity' => 'TEXT',
    'address' => 'TEXT',
    'source_reference' => 'TEXT',
];
foreach ($requiredColumns as $column => $type) {
    if (!in_array($column, $wellColumns, true)) {
        $pdo->exec("ALTER TABLE wells ADD COLUMN {$column} {$type}");
    }
}

$wells = [];
$skipDatabaseWellMerge = true;
require __DIR__ . '/../main/includes/well-data.php';

$sourceWells = [];
foreach ($wells as $well) {
    $wellCode = (string)($well['id'] ?? '');
    if (preg_match('/^RCH-\d{3}$/', $wellCode) !== 1 || isset($sourceWells[$wellCode])) {
        continue;
    }
    $sourceWells[$wellCode] = $well;
}

if (count($sourceWells) !== 81) {
    throw new RuntimeException('Expected 81 normalized recharge wells, found ' . count($sourceWells));
}

function normalizeLocationText(string $value): string
{
    $value = strtoupper(trim($value));
    $value = preg_replace('/[^A-Z0-9]+/', ' ', $value) ?? '';
    return trim(preg_replace('/\s+/', ' ', $value) ?? '');
}

function villageForWell(PDO $pdo, array $well): int
{
    static $districtCandidates = [];

    $city = normalizeLocationText((string)($well['city'] ?? ''));
    if (!isset($districtCandidates[$city])) {
        $statement = $pdo->prepare(
            "SELECT v.id, v.name
             FROM village v
             JOIN sub_district sd ON sd.id = v.sub_district_id
             JOIN district_city dc ON dc.id = sd.district_id
             WHERE UPPER(dc.name) LIKE :city
             ORDER BY LENGTH(v.name) DESC, v.name ASC"
        );
        $statement->execute([':city' => '%' . $city . '%']);
        $districtCandidates[$city] = $statement->fetchAll();
    }

    $candidates = $districtCandidates[$city];
    if ($candidates === []) {
        throw new RuntimeException('No database location found for ' . ($well['city'] ?? 'unknown city'));
    }

    $searchText = normalizeLocationText(
        (string)($well['name'] ?? '') . ' ' . (string)($well['address'] ?? '')
    );
    foreach ($candidates as $candidate) {
        $villageName = normalizeLocationText((string)$candidate['name']);
        if ($villageName !== '' && str_contains($searchText, $villageName)) {
            return (int)$candidate['id'];
        }
    }

    // The workbook gives a district for every row but not always a village.
    // Use a stable village in that district so the required foreign key stays valid.
    return (int)$candidates[0]['id'];
}

$upsert = $pdo->prepare(
    'INSERT INTO wells
        (well_code, well_name, village_id, latitude, longitude, well_depth,
         well_type, absorption_capacity, address, source_reference,
         installer_name, installation_date, status)
     VALUES
        (:well_code, :well_name, :village_id, :latitude, :longitude, :well_depth,
         :well_type, :absorption_capacity, :address, :source_reference,
         :installer_name, :installation_date, 1)
     ON CONFLICT(well_code) DO UPDATE SET
        well_name = excluded.well_name,
        village_id = excluded.village_id,
        latitude = excluded.latitude,
        longitude = excluded.longitude,
        well_depth = excluded.well_depth,
        well_type = excluded.well_type,
        absorption_capacity = excluded.absorption_capacity,
        address = excluded.address,
        source_reference = excluded.source_reference,
        installer_name = excluded.installer_name,
        installation_date = excluded.installation_date,
        status = 1,
        updated_at = CURRENT_TIMESTAMP'
);

$pdo->beginTransaction();
try {
    foreach ($sourceWells as $well) {
        $depth = null;
        if (preg_match('/-?\d+(?:\.\d+)?/', (string)($well['depth'] ?? ''), $matches) === 1) {
            $depth = (float)$matches[0];
        }

        $upsert->execute([
            ':well_code' => $well['id'],
            ':well_name' => trim((string)$well['name']),
            ':village_id' => villageForWell($pdo, $well),
            ':latitude' => is_numeric($well['lat'] ?? null) ? (float)$well['lat'] : null,
            ':longitude' => is_numeric($well['lng'] ?? null) ? (float)$well['lng'] : null,
            ':well_depth' => $depth,
            ':well_type' => (string)($well['wellType'] ?? ''),
            ':absorption_capacity' => (string)($well['absorption'] ?? ''),
            ':address' => (string)($well['address'] ?? ''),
            ':source_reference' => 'LIST OF RECHARGE WELLS IN BALI.xlsx',
            ':installer_name' => 'IDEP Foundation',
            ':installation_date' => $well['startDateISO'] ?? null,
        ]);
    }
    $pdo->commit();
} catch (Throwable $error) {
    $pdo->rollBack();
    throw $error;
}

echo 'Imported ' . count($sourceWells) . " recharge wells.\n";
