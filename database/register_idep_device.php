<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = idepDatabase();
$pdo->beginTransaction();

try {
    $villageStatement = $pdo->query(
        "SELECT v.id
         FROM village v
         JOIN sub_district sd ON sd.id = v.sub_district_id
         JOIN district_city dc ON dc.id = sd.district_id
         WHERE UPPER(v.name) = 'UBUD'
           AND UPPER(sd.name) = 'UBUD'
           AND UPPER(dc.name) LIKE '%GIANYAR%'
         LIMIT 1"
    );
    $villageId = (int)$villageStatement->fetchColumn();
    if ($villageId <= 0) {
        throw new RuntimeException('Ubud village was not found. Import Bali locations first.');
    }

    $sensorUpsert = $pdo->prepare(
        'INSERT INTO sensors
            (sensor_code, sensor_name, sensor_type, id_device, status)
         VALUES
            (:sensor_code, :sensor_name, :sensor_type, :id_device, 1)
         ON CONFLICT(id_device) DO UPDATE SET
            sensor_name = excluded.sensor_name,
            sensor_type = excluded.sensor_type,
            status = 1,
            updated_at = CURRENT_TIMESTAMP'
    );
    $sensorUpsert->execute([
        ':sensor_code' => 'device_1',
        ':sensor_name' => 'IDEP',
        ':sensor_type' => 'Monitoring Well',
        ':id_device' => 'device_1',
    ]);

    $sensorLookup = $pdo->prepare('SELECT id FROM sensors WHERE id_device = :id_device LIMIT 1');
    $sensorLookup->execute([':id_device' => 'device_1']);
    $sensorId = (int)$sensorLookup->fetchColumn();

    $unlink = $pdo->prepare(
        'UPDATE wells
         SET sensor_id = NULL, updated_at = CURRENT_TIMESTAMP
         WHERE sensor_id = :sensor_id AND well_code != :well_code'
    );
    $unlink->execute([':sensor_id' => $sensorId, ':well_code' => 'RCH-082']);

    $wellUpsert = $pdo->prepare(
        'INSERT INTO wells
            (well_code, well_name, village_id, latitude, longitude, well_type,
             sensor_id, installer_name, address, source_reference, status)
         VALUES
            (:well_code, :well_name, :village_id, :latitude, :longitude, :well_type,
             :sensor_id, :installer_name, :address, :source_reference, 1)
         ON CONFLICT(well_code) DO UPDATE SET
            well_name = excluded.well_name,
            village_id = excluded.village_id,
            latitude = excluded.latitude,
            longitude = excluded.longitude,
            well_type = excluded.well_type,
            sensor_id = excluded.sensor_id,
            installer_name = excluded.installer_name,
            address = excluded.address,
            source_reference = excluded.source_reference,
            status = 1,
            updated_at = CURRENT_TIMESTAMP'
    );
    $wellUpsert->execute([
        ':well_code' => 'RCH-082',
        ':well_name' => 'IDEP',
        ':village_id' => $villageId,
        ':latitude' => -8.5069,
        ':longitude' => 115.2625,
        ':well_type' => 'Monitoring Well',
        ':sensor_id' => $sensorId,
        ':installer_name' => 'IDEP Foundation',
        ':address' => 'IDEP Foundation, Ubud, Gianyar, Bali',
        ':source_reference' => 'device_1 installation site',
    ]);

    $pdo->commit();
    echo "Registered RCH-082 (IDEP) and linked device_1.\n";
} catch (Throwable $error) {
    $pdo->rollBack();
    throw $error;
}
