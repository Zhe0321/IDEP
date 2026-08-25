<?php

header('Content-Type: application/json');

$latestFile = __DIR__ . '/latest.json';


// =====================================================
// GET
// Untuk melihat data sensor terakhir melalui browser
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (!file_exists($latestFile)) {
        echo json_encode([
            'status' => false,
            'message' => 'No sensor data received yet'
        ], JSON_PRETTY_PRINT);

        exit;
    }

    $data = file_get_contents($latestFile);

    echo $data;

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

        'received_at' => date('d-m-Y H:i:s'),
    ];


    // Simpan data terakhir
    file_put_contents(
        $latestFile,
        json_encode($data, JSON_PRETTY_PRINT),
        LOCK_EX
    );


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
