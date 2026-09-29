<?php
declare(strict_types=1);

require __DIR__ . '/includes/admin-endpoint.php';
require __DIR__ . '/../database/db.php';
requireAdminJson();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$wellId = (int)($_POST['well_id'] ?? 0);
$name = trim((string)($_POST['name'] ?? ''));
$installer = trim((string)($_POST['installer'] ?? ''));
$type = trim((string)($_POST['type'] ?? ''));
$date = trim((string)($_POST['date'] ?? ''));
$longitudeInput = trim((string)($_POST['longitude'] ?? ''));
$latitudeInput = trim((string)($_POST['latitude'] ?? ''));
$longitude = $longitudeInput !== '' && is_numeric($longitudeInput) ? (float)$longitudeInput : null;
$latitude = $latitudeInput !== '' && is_numeric($latitudeInput) ? (float)$latitudeInput : null;
$villageId = (int)($_POST['village_id'] ?? 0);
$deviceId = trim((string)($_POST['sensor_id'] ?? ''));

if ($name === '' || $deviceId === '' || $villageId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ID/Name, Sensor ID and Village are required.']);
    exit();
}

if (($latitudeInput !== '' && $latitude === null) || ($longitudeInput !== '' && $longitude === null)
    || ($latitude !== null && ($latitude < -90 || $latitude > 90))
    || ($longitude !== null && ($longitude < -180 || $longitude > 180))) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Latitude or longitude is invalid.']);
    exit();
}

try {
    $pdo = idepDatabase();
    $pdo->beginTransaction();

    $villageStatement = $pdo->prepare('SELECT id FROM village WHERE id = :id LIMIT 1');
    $villageStatement->execute([':id' => $villageId]);
    if (!$villageStatement->fetchColumn()) {
        throw new InvalidArgumentException('Please select a valid village.');
    }

    $currentSensorId = null;
    if ($wellId > 0) {
        $currentStatement = $pdo->prepare('SELECT sensor_id FROM wells WHERE id = :id LIMIT 1');
        $currentStatement->execute([':id' => $wellId]);
        $currentSensorId = $currentStatement->fetchColumn();
        if ($currentSensorId === false) {
            throw new InvalidArgumentException('Registered site was not found.');
        }
    }

    $sensorLookup = $pdo->prepare('SELECT id FROM sensors WHERE id_device = :device_id LIMIT 1');
    $sensorLookup->execute([':device_id' => $deviceId]);
    $sensorId = (int)($sensorLookup->fetchColumn() ?: 0);

    if ($sensorId === 0) {
        $sensorInsert = $pdo->prepare(
            'INSERT INTO sensors (sensor_code, sensor_name, sensor_type, id_device, status)
             VALUES (:sensor_code, :sensor_name, :sensor_type, :device_id, 1)'
        );
        $sensorInsert->execute([
            ':sensor_code' => $deviceId,
            ':sensor_name' => $name,
            ':sensor_type' => $type,
            ':device_id' => $deviceId,
        ]);
        $sensorId = (int)$pdo->lastInsertId();
    } else {
        $sensorUpdate = $pdo->prepare(
            'UPDATE sensors SET sensor_name = :name, sensor_type = :type, status = 1, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $sensorUpdate->execute([':name' => $name, ':type' => $type, ':id' => $sensorId]);
    }

    $linkedWell = $pdo->prepare('SELECT id FROM wells WHERE sensor_id = :sensor_id AND id != :well_id LIMIT 1');
    $linkedWell->execute([':sensor_id' => $sensorId, ':well_id' => $wellId]);
    if ($linkedWell->fetchColumn()) {
        throw new InvalidArgumentException('That Sensor ID is already connected to another site.');
    }

    $values = [
        ':name' => $name,
        ':village_id' => $villageId,
        ':lat' => $latitude,
        ':lng' => $longitude,
        ':sensor_id' => $sensorId,
        ':installer' => $installer,
        ':install_date' => $date !== '' ? $date : null,
    ];

    if ($wellId > 0) {
        $wellStatement = $pdo->prepare(
            'UPDATE wells SET well_name = :name, village_id = :village_id, latitude = :lat,
             longitude = :lng, sensor_id = :sensor_id, installer_name = :installer,
             installation_date = :install_date, status = 1, updated_at = CURRENT_TIMESTAMP
             WHERE id = :well_id'
        );
        $values[':well_id'] = $wellId;
        $wellStatement->execute($values);

        if ($currentSensorId !== null && (int)$currentSensorId !== $sensorId) {
            $oldSensorUsage = $pdo->prepare(
                'SELECT
                    (SELECT COUNT(*) FROM wells WHERE sensor_id = :sensor_id) +
                    (SELECT COUNT(*) FROM sensor_readings WHERE sensor_id = :sensor_id)'
            );
            $oldSensorUsage->execute([':sensor_id' => $currentSensorId]);
            if ((int)$oldSensorUsage->fetchColumn() === 0) {
                $deleteOldSensor = $pdo->prepare('DELETE FROM sensors WHERE id = :sensor_id');
                $deleteOldSensor->execute([':sensor_id' => $currentSensorId]);
            }
        }
    } else {
        $wellCode = preg_replace('/[^A-Za-z0-9]+/', '-', trim($name)) ?: 'well';
        $wellCode .= '-' . substr(bin2hex(random_bytes(3)), 0, 6);
        $wellStatement = $pdo->prepare(
            'INSERT INTO wells
             (well_code, well_name, village_id, latitude, longitude, sensor_id,
              installer_name, installation_date, status)
             VALUES (:code, :name, :village_id, :lat, :lng, :sensor_id,
                     :installer, :install_date, 1)'
        );
        $values[':code'] = $wellCode;
        $wellStatement->execute($values);
        $wellId = (int)$pdo->lastInsertId();
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'well_id' => $wellId]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $isValidationError = $e instanceof InvalidArgumentException;
    $message = $isValidationError
        ? $e->getMessage()
        : (str_contains($e->getMessage(), 'UNIQUE constraint failed')
            ? 'That Sensor ID is already registered.'
            : 'Unable to save this site.');
    if (!$isValidationError) {
        error_log('Site registration save failed: ' . $e->getMessage());
    }
    http_response_code($isValidationError ? 422 : 500);
    echo json_encode(['success' => false, 'message' => $message]);
}
