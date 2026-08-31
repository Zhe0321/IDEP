<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Makassar');

header('Content-Type: application/json');

require_once __DIR__ . '/../../../database/db.php';

try {
    $pdo = idepDatabase();
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => $error->getMessage(),
    ], JSON_PRETTY_PRINT);
    exit;
}


// =====================================================
// GET
// Untuk melihat data sensor terakhir melalui browser
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $statement = $pdo->query(
        'SELECT sensors.id_device, sensor_readings.h1, sensor_readings.h2,
                sensor_readings.hasil, sensor_readings.received_at
         FROM sensor_readings
         INNER JOIN sensors ON sensors.id = sensor_readings.sensor_id
         ORDER BY sensor_readings.id DESC
         LIMIT 1'
    );
    $latest = $statement->fetch();

    if (!$latest) {
        echo json_encode([
            'status' => false,
            'message' => 'No sensor data received yet'
        ], JSON_PRETTY_PRINT);

        exit;
    }

    echo json_encode([
        'status' => true,
        'message' => 'Latest sensor data',
        'data' => [
            'id_device' => $latest['id_device'],
            'h1' => $latest['h1'],
            'h2' => $latest['h2'],
            'hasil' => $latest['hasil'],
        ],
        'received_at' => $latest['received_at'],
    ], JSON_PRETTY_PRINT);

    exit;
}


// =====================================================
// POST
// Untuk menerima data dari sensor
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_device = $_POST['id_device'] ?? null;
    $h1        = $_POST['h1'] ?? null;
    $h2        = $_POST['h2'] ?? null;
    $hasil     = $_POST['hasil'] ?? null;


    // Validasi
    if (
        $id_device === null ||
        $h1 === null ||
        $h2 === null ||
        $hasil === null
    ) {

        http_response_code(400);

        echo json_encode([
            'status' => false,
            'message' => 'Missing required parameters',
            'received' => $_POST
        ], JSON_PRETTY_PRINT);

        exit;
    }

    // If both height readings are zero, the result must also be zero.
    if (
        is_numeric($h1) &&
        is_numeric($h2) &&
        (float) $h1 === 0.0 &&
        (float) $h2 === 0.0
    ) {
        $hasil = 0;
    }


    $receivedAt = date('Y-m-d H:i:s');

    try {
        $pdo->beginTransaction();

        $sensorStatement = $pdo->prepare(
            'INSERT OR IGNORE INTO sensors
             (sensor_code, sensor_name, id_device, status)
             VALUES (:sensor_code, :sensor_name, :id_device, 1)'
        );
        $sensorStatement->execute([
            ':sensor_code' => $id_device,
            ':sensor_name' => $id_device,
            ':id_device' => $id_device,
        ]);

        $sensorIdStatement = $pdo->prepare(
            'SELECT id FROM sensors WHERE id_device = :id_device LIMIT 1'
        );
        $sensorIdStatement->execute([':id_device' => $id_device]);
        $sensorId = $sensorIdStatement->fetchColumn();

        if ($sensorId === false) {
            throw new RuntimeException('Unable to register sensor');
        }

        $readingStatement = $pdo->prepare(
            'INSERT INTO sensor_readings
             (sensor_id, h1, h2, hasil, received_at, created_at)
             VALUES (:sensor_id, :h1, :h2, :hasil, :received_at, :created_at)'
        );
        $readingStatement->execute([
            ':sensor_id' => $sensorId,
            ':h1' => $h1,
            ':h2' => $h2,
            ':hasil' => $hasil,
            ':received_at' => $receivedAt,
            ':created_at' => $receivedAt,
        ]);

        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        http_response_code(500);
        echo json_encode([
            'status' => false,
            'message' => 'Unable to save sensor data',
        ], JSON_PRETTY_PRINT);
        exit;
    }

    // Data yang diterima
    $data = [
        'status' => true,
        'message' => 'Data received successfully',

        'data' => [
            'id_device' => $id_device,
            'h1' => $h1,
            'h2' => $h2,
            'hasil' => $hasil
        ],

        'received_at' => $receivedAt,
    ];


    // Response ke sensor
    echo json_encode($data, JSON_PRETTY_PRINT);

    exit;
}


// =====================================================
// Method selain GET / POST
// =====================================================

http_response_code(405);

echo json_encode([
    'status' => false,
    'message' => 'Method not allowed'
], JSON_PRETTY_PRINT);
