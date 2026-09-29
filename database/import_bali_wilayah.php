<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

const BALI_PROVINCE_ID = '51'; // BPS code for Bali in the source dataset

$importDir = __DIR__ . '/wilayah_import/';

function readCsv(string $path): array
{
    if (!file_exists($path)) {
        throw new RuntimeException("Missing file: {$path}");
    }
    $rows = [];
    $handle = fopen($path, 'r');
    $header = fgetcsv($handle);
    while (($row = fgetcsv($handle)) !== false) {
        $rows[] = array_combine($header, $row);
    }
    fclose($handle);
    return $rows;
}

echo "Reading CSV files...\n";
$provinces = readCsv($importDir . 'provinces.csv');
$regencies = readCsv($importDir . 'regencies.csv');
$districts = readCsv($importDir . 'districts.csv');
$villages  = readCsv($importDir . 'villages.csv');

$pdo = idepDatabase();
$pdo->beginTransaction();

try {
    // 1. Province: Bali only
    $baliRow = null;
    foreach ($provinces as $p) {
        if ($p['id'] === BALI_PROVINCE_ID) {
            $baliRow = $p;
            break;
        }
    }
    if (!$baliRow) {
        throw new RuntimeException('Bali not found in provinces.csv');
    }

    $stmt = $pdo->prepare(
        "INSERT INTO province (code, name, status) VALUES (:code, :name, 1)"
    );
    $stmt->execute([':code' => $baliRow['id'], ':name' => $baliRow['name']]);
    $newProvinceId = (int)$pdo->lastInsertId();
    echo "Inserted province: {$baliRow['name']} (new id {$newProvinceId})\n";

    // 2. Regencies -> district_city, filtered to Bali
    $regencyIdMap = [];
    $insertRegency = $pdo->prepare(
        "INSERT INTO district_city (code, name, province_id, status) VALUES (:code, :name, :province_id, 1)"
    );
    $baliRegencyCount = 0;
    foreach ($regencies as $r) {
        if ($r['province_id'] !== BALI_PROVINCE_ID) {
            continue;
        }
        $insertRegency->execute([
            ':code'       => $r['id'],
            ':name'       => $r['name'],
            ':province_id'=> $newProvinceId,
        ]);
        $regencyIdMap[$r['id']] = (int)$pdo->lastInsertId();
        $baliRegencyCount++;
    }
    echo "Inserted {$baliRegencyCount} regencies/cities\n";

    // 3. Districts -> sub_district, filtered to Bali regencies
    $districtIdMap = [];
    $insertDistrict = $pdo->prepare(
        "INSERT INTO sub_district (code, name, district_id, status) VALUES (:code, :name, :district_id, 1)"
    );
    $baliDistrictCount = 0;
    foreach ($districts as $d) {
        if (!isset($regencyIdMap[$d['regency_id']])) {
            continue;
        }
        $insertDistrict->execute([
            ':code'        => $d['id'],
            ':name'        => $d['name'],
            ':district_id' => $regencyIdMap[$d['regency_id']],
        ]);
        $districtIdMap[$d['id']] = (int)$pdo->lastInsertId();
        $baliDistrictCount++;
    }
    echo "Inserted {$baliDistrictCount} districts/sub-districts\n";

    // 4. Villages, filtered to Bali districts
    $insertVillage = $pdo->prepare(
        "INSERT INTO village (code, name, sub_district_id, status) VALUES (:code, :name, :sub_district_id, 1)"
    );
    $baliVillageCount = 0;
    foreach ($villages as $v) {
        if (!isset($districtIdMap[$v['district_id']])) {
            continue;
        }
        $insertVillage->execute([
            ':code'            => $v['id'],
            ':name'            => $v['name'],
            ':sub_district_id' => $districtIdMap[$v['district_id']],
        ]);
        $baliVillageCount++;
    }
    echo "Inserted {$baliVillageCount} villages\n";

    $pdo->commit();
    echo "\n✅ Bali location hierarchy imported successfully.\n";

} catch (Throwable $e) {
    $pdo->rollBack();
    echo "❌ Import failed: " . $e->getMessage() . "\n";
    exit(1);
}