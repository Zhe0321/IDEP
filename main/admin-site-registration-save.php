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
$depthInput = trim((string)($_POST['depth'] ?? ''));
$depth = $depthInput !== '' && is_numeric($depthInput) ? (float)$depthInput : null;
$absorption = trim((string)($_POST['absorption'] ?? ''));
$address = trim((string)($_POST['address'] ?? ''));
$date = trim((string)($_POST['date'] ?? ''));
$longitudeInput = trim((string)($_POST['longitude'] ?? ''));
$latitudeInput = trim((string)($_POST['latitude'] ?? ''));
$longitude = $longitudeInput !== '' && is_numeric($longitudeInput) ? (float)$longitudeInput : null;
$latitude = $latitudeInput !== '' && is_numeric($latitudeInput) ? (float)$latitudeInput : null;
$villageId = (int)($_POST['village_id'] ?? 0);
$deviceId = trim((string)($_POST['sensor_id'] ?? ''));

if ($name === '' || $villageId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ID/Name and Village are required.']);
    exit();
}

if (($depthInput !== '' && $depth === null) || ($depth !== null && $depth < 0)
    || ($latitudeInput !== '' && $latitude === null) || ($longitudeInput !== '' && $longitude === null)
    || ($latitude !== null && ($latitude < -90 || $latitude > 90))
    || ($longitude !== null && ($longitude < -180 || $longitude > 180))) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Latitude or longitude is invalid.']);
    exit();
}

// ---- Optional site photo -------------------------------------------------
const SITE_PHOTO_MAX_BYTES = 5 * 1024 * 1024;
const SITE_PHOTO_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];
$newPhotoPath = null;   // web path saved in the DB, e.g. /uploads/wells/abc.jpg
$newPhotoFile = null;   // absolute file path on disk
$removePhoto = ($_POST['remove_photo'] ?? '') === '1';

if (isset($_FILES['photo']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $upload = $_FILES['photo'];
    if ($upload['error'] === UPLOAD_ERR_INI_SIZE || $upload['error'] === UPLOAD_ERR_FORM_SIZE) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Photo is too large (max 5 MB).']);
        exit();
    }
    if ($upload['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Photo upload failed. Please try again.']);
        exit();
    }
    if ($upload['size'] > SITE_PHOTO_MAX_BYTES) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Photo is too large (max 5 MB).']);
        exit();
    }
    $imageInfo = @getimagesize($upload['tmp_name']);
    $mime = is_array($imageInfo) ? (string)($imageInfo['mime'] ?? '') : '';
    if (!isset(SITE_PHOTO_TYPES[$mime])) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Photo must be a JPG, PNG or WebP image.']);
        exit();
    }
    $uploadDir = dirname(__DIR__) . '/uploads/wells';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Could not create the photo folder.']);
        exit();
    }
    $fileName = bin2hex(random_bytes(12)) . '.' . SITE_PHOTO_TYPES[$mime];
    $newPhotoFile = $uploadDir . '/' . $fileName;
    if (!move_uploaded_file($upload['tmp_name'], $newPhotoFile)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Could not save the photo.']);
        exit();
    }
    $newPhotoPath = '/uploads/wells/' . $fileName;
}

$oldPhotoPath = null;

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
        $currentStatement = $pdo->prepare('SELECT sensor_id, photo_path FROM wells WHERE id = :id LIMIT 1');
        $currentStatement->execute([':id' => $wellId]);
        $currentRow = $currentStatement->fetch(PDO::FETCH_ASSOC);
        if ($currentRow === false) {
            throw new InvalidArgumentException('Registered site was not found.');
        }
        $currentSensorId = $currentRow['sensor_id'];
        $oldPhotoPath = $currentRow['photo_path'];
    }

    $sensorId = null;
    if ($deviceId !== '') {
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
    }

    $values = [
        ':name' => $name,
        ':village_id' => $villageId,
        ':lat' => $latitude,
        ':lng' => $longitude,
        ':sensor_id' => $sensorId,
        ':well_type' => $type,
        ':well_depth' => $depth,
        ':absorption' => $absorption !== '' ? $absorption : null,
        ':address' => $address !== '' ? $address : null,
        ':installer' => $installer,
        ':install_date' => $date !== '' ? $date : null,
    ];

    if ($wellId > 0) {
        $wellStatement = $pdo->prepare(
            'UPDATE wells SET well_name = :name, village_id = :village_id, latitude = :lat,
             longitude = :lng, sensor_id = :sensor_id, installer_name = :installer,
             installation_date = :install_date, well_type = :well_type,
             well_depth = :well_depth, absorption_capacity = :absorption,
             address = :address, status = 1, updated_at = CURRENT_TIMESTAMP,
             photo_path = :photo_path
             WHERE id = :well_id'
        );
        $values[':well_id'] = $wellId;
        $values[':photo_path'] = $newPhotoPath ?? ($removePhoto ? null : $oldPhotoPath);
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
              installer_name, installation_date, well_type, well_depth,
              absorption_capacity, address, photo_path, status)
             VALUES (:code, :name, :village_id, :lat, :lng, :sensor_id,
                     :installer, :install_date, :well_type, :well_depth,
                     :absorption, :address, :photo_path, 1)'
        );
        $values[':code'] = $wellCode;
        $values[':photo_path'] = $newPhotoPath;
        $wellStatement->execute($values);
        $wellId = (int)$pdo->lastInsertId();
    }

    $pdo->commit();

    // The DB now points at the new photo (or none), so delete the replaced file.
    if ($oldPhotoPath && ($newPhotoPath !== null || $removePhoto)
        && str_starts_with($oldPhotoPath, '/uploads/wells/')) {
        $oldFile = dirname(__DIR__) . $oldPhotoPath;
        if (is_file($oldFile)) {
            @unlink($oldFile);
        }
    }

    echo json_encode(['success' => true, 'well_id' => $wellId]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($newPhotoFile !== null && is_file($newPhotoFile)) {
        @unlink($newPhotoFile);
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