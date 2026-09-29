<?php
declare(strict_types=1);

header('Content-Type: application/json');
require __DIR__ . '/../database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$name       = trim($_POST['name'] ?? '');
$installer  = trim($_POST['installer'] ?? '');
$type       = trim($_POST['type'] ?? '');
$date       = trim($_POST['date'] ?? '');
$longitude  = $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
$latitude   = $_POST['latitude']  !== '' ? (float)$_POST['latitude']  : null;
$villageId  = (int)($_POST['village_id'] ?? 0);
$mac        = trim($_POST['mac'] ?? '');
$sensorIdInput = trim($_POST['sensor_id'] ?? '');

if ($name === '' || $villageId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ID/Name and Village are required.']);
    exit();
}

try {
    $pdo = idepDatabase();
    $pdo->beginTransaction();

    // Generate a guaranteed-unique well code even if two wells share the same name
    $wellCode = $name . '-' . substr(bin2hex(random_bytes(3)), 0, 6);

    $sensorStmt = $pdo->prepare(
        "INSERT INTO sensors (sensor_code, sensor_name, sensor_type, id_device, status)
         VALUES (:sensor_code, :sensor_name, :sensor_type, :device_id, 1)"
    );
    $sensorStmt->execute([
        ':sensor_code' => $sensorIdInput !== '' ? $sensorIdInput : null,
        ':sensor_name' => $name,
        ':sensor_type' => $type,
        ':device_id'   => $mac !== '' ? $mac : null,
    ]);
    $sensorId = (int)$pdo->lastInsertId();

    $wellStmt = $pdo->prepare(
        "INSERT INTO wells
            (well_code, well_name, village_id, latitude, longitude,
             sensor_id, installer_name, installation_date, status)
         VALUES
            (:code, :name, :village_id, :lat, :lng,
             :sensor_id, :installer, :install_date, 1)"
    );
    $wellStmt->execute([
        ':code'         => $wellCode,
        ':name'         => $name,
        ':village_id'   => $villageId,
        ':lat'          => $latitude,
        ':lng'          => $longitude,
        ':sensor_id'    => $sensorId,
        ':installer'    => $installer,
        ':install_date' => $date,
    ]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $message = str_contains($e->getMessage(), 'UNIQUE constraint failed')
        ? 'That sensor code or MAC address is already registered.'
        : 'Database error: ' . $e->getMessage();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $message]);
}