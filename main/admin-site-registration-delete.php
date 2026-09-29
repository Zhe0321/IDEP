<?php
declare(strict_types=1);

require __DIR__ . '/includes/admin-endpoint.php';
require __DIR__ . '/../database/db.php';
requireAdminJson();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$wellId = (int)($_POST['well_id'] ?? 0);
if ($wellId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Registered site was not found.']);
    exit;
}

try {
    $pdo = idepDatabase();
    $pdo->beginTransaction();

    $findStatement = $pdo->prepare('SELECT sensor_id FROM wells WHERE id = :id LIMIT 1');
    $findStatement->execute([':id' => $wellId]);
    $sensorId = $findStatement->fetchColumn();
    if ($sensorId === false) {
        throw new InvalidArgumentException('Registered site was not found.');
    }

    $deleteWell = $pdo->prepare('DELETE FROM wells WHERE id = :id');
    $deleteWell->execute([':id' => $wellId]);

    if ($sensorId !== null) {
        $readingCount = $pdo->prepare('SELECT COUNT(*) FROM sensor_readings WHERE sensor_id = :sensor_id');
        $readingCount->execute([':sensor_id' => $sensorId]);
        if ((int)$readingCount->fetchColumn() === 0) {
            $deleteSensor = $pdo->prepare('DELETE FROM sensors WHERE id = :sensor_id');
            $deleteSensor->execute([':sensor_id' => $sensorId]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $isValidationError = $e instanceof InvalidArgumentException;
    if (!$isValidationError) {
        error_log('Site registration delete failed: ' . $e->getMessage());
    }
    http_response_code($isValidationError ? 404 : 500);
    echo json_encode([
        'success' => false,
        'message' => $isValidationError ? $e->getMessage() : 'Unable to delete this site.',
    ]);
}
