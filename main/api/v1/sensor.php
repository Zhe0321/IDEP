<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

/**
 * @param array<string, mixed> $body
 */
function respond(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respond(405, [
        'success' => false,
        'error' => 'method_not_allowed',
        'message' => 'Send sensor readings with an HTTP POST request.',
    ]);
}

$contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
if ($contentType !== 'application/json') {
    respond(415, [
        'success' => false,
        'error' => 'unsupported_media_type',
        'message' => 'Content-Type must be application/json.',
    ]);
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false || trim($rawBody) === '') {
    respond(400, [
        'success' => false,
        'error' => 'empty_request_body',
        'message' => 'The request body must contain sensor JSON.',
    ]);
}

if (strlen($rawBody) > 16_384) {
    respond(413, [
        'success' => false,
        'error' => 'payload_too_large',
        'message' => 'The sensor payload must not exceed 16 KB.',
    ]);
}

try {
    $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    respond(400, [
        'success' => false,
        'error' => 'invalid_json',
        'message' => 'The request body is not valid JSON.',
    ]);
}

if (!is_array($payload) || array_is_list($payload)) {
    respond(400, [
        'success' => false,
        'error' => 'invalid_payload',
        'message' => 'The JSON body must be an object.',
    ]);
}

$requiredFields = ['id_device', 'h1', 'h2', 'hasil'];
$missingFields = array_values(array_filter(
    $requiredFields,
    static fn (string $field): bool => !array_key_exists($field, $payload)
));

if ($missingFields !== []) {
    respond(422, [
        'success' => false,
        'error' => 'missing_fields',
        'message' => 'The sensor payload is missing required fields.',
        'fields' => $missingFields,
    ]);
}

if (!is_string($payload['id_device'])) {
    respond(422, [
        'success' => false,
        'error' => 'invalid_device_id',
        'message' => 'id_device must be a string.',
    ]);
}

$deviceId = trim($payload['id_device']);
if ($deviceId === '' || preg_match('/^[A-Za-z0-9_-]{1,64}$/', $deviceId) !== 1) {
    respond(422, [
        'success' => false,
        'error' => 'invalid_device_id',
        'message' => 'id_device may contain only letters, numbers, underscores and hyphens.',
    ]);
}

$measurements = [];
$invalidMeasurements = [];

foreach (['h1', 'h2', 'hasil'] as $field) {
    $value = $payload[$field];

    if ((!is_int($value) && !is_float($value) && !is_string($value)) || !is_numeric($value)) {
        $invalidMeasurements[] = $field;
        continue;
    }

    $numericValue = (float) $value;
    if (!is_finite($numericValue)) {
        $invalidMeasurements[] = $field;
        continue;
    }

    $measurements[$field] = $numericValue;
}

if ($invalidMeasurements !== []) {
    respond(422, [
        'success' => false,
        'error' => 'invalid_measurements',
        'message' => 'h1, h2 and hasil must contain valid numeric values.',
        'fields' => $invalidMeasurements,
    ]);
}

$reading = [
    'id_device' => $deviceId,
    'h1' => $measurements['h1'],
    'h2' => $measurements['h2'],
    'hasil' => $measurements['hasil'],
    'received_at' => gmdate('c'),
];

error_log('Sensor reading received: ' . json_encode(
    $reading,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
));

respond(200, [
    'success' => true,
    'message' => 'Sensor data received and validated.',
    'stored' => false,
    'data' => $reading,
]);
