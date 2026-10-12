<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../includes/programme-metrics.php';

try {
    echo json_encode([
        'success' => true,
        'data' => idepProgrammeMetrics(),
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    error_log('Programme metrics failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Programme statistics are temporarily unavailable.',
    ]);
}
