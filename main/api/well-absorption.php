<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../../database/db.php';

function requestedDate(string $name): ?string
{
    $value = trim((string)($_GET[$name] ?? ''));
    if ($value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if ($date === false || $date->format('Y-m-d') !== $value) {
        throw new InvalidArgumentException("Invalid {$name} date.");
    }

    return $value;
}

try {
    $startDate = requestedDate('start');
    $endDate = requestedDate('end');
    if ($startDate !== null && $endDate !== null && $startDate > $endDate) {
        throw new InvalidArgumentException('Start date must be before the end date.');
    }

    $joinConditions = ['sr.sensor_id = w.sensor_id'];
    $parameters = [];
    if ($startDate !== null) {
        $joinConditions[] = 'sr.received_at >= :start_at';
        $parameters[':start_at'] = $startDate . ' 00:00:00';
    }
    if ($endDate !== null) {
        $joinConditions[] = 'sr.received_at <= :end_at';
        $parameters[':end_at'] = $endDate . ' 23:59:59';
    }

    $statement = idepDatabase()->prepare(
        'SELECT
            w.well_code,
            COUNT(sr.id) AS reading_count,
            COALESCE(SUM(
                CASE
                    WHEN COALESCE(sr.h1, 0) = 0 AND COALESCE(sr.h2, 0) = 0 THEN 0
                    WHEN COALESCE(sr.hasil, 0) > 0 THEN sr.hasil
                    ELSE 0
                END
            ), 0) AS total_absorption
         FROM wells w
         LEFT JOIN sensor_readings sr ON ' . implode(' AND ', $joinConditions) . '
         GROUP BY w.id, w.well_code
         ORDER BY w.id'
    );
    $statement->execute($parameters);

    $data = [];
    foreach ($statement->fetchAll() as $row) {
        $readingCount = (int)$row['reading_count'];
        $litres = max(0, (float)$row['total_absorption']);
        $data[(string)$row['well_code']] = [
            'readingCount' => $readingCount,
            'litres' => round($litres, 2),
            'label' => $readingCount > 0 ? number_format($litres, 2, '.', ',') . ' L' : '—',
            'exactLabel' => $readingCount > 0 ? number_format($litres, 2, '.', ',') . ' L' : '—',
        ];
    }

    echo json_encode([
        'success' => true,
        'filters' => ['start' => $startDate, 'end' => $endDate],
        'data' => $data,
    ], JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException $error) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $error->getMessage()]);
} catch (Throwable $error) {
    error_log('Well absorption totals failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Water absorption totals are temporarily unavailable.',
    ]);
}
