<?php
declare(strict_types=1);

/**
 * Aggregated historical absorption data for the Historical Data chart.
 * Results include every reading belonging to a registered monitoring well,
 * rather than the limited set of rows rendered in the measurements table.
 */

date_default_timezone_set('Asia/Makassar');
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/database/db.php';

try {
    $pdo = idepDatabase();

    $period = (string)($_GET['period'] ?? 'all');
    $allowedPeriods = ['all', '7', '30', '90', '365'];
    if (!in_array($period, $allowedPeriods, true)) {
        $period = 'all';
    }

    $wellCode = trim((string)($_GET['well'] ?? ''));
    $requestedGranularity = (string)($_GET['granularity'] ?? 'auto');
    $allowedGranularities = ['auto', 'day', 'month', 'year'];
    if (!in_array($requestedGranularity, $allowedGranularities, true)) {
        $requestedGranularity = 'auto';
    }

    $where = ['sr.received_at IS NOT NULL'];
    $parameters = [];
    if ($period !== 'all') {
        $cutoff = (new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar')))
            ->modify('-' . $period . ' days')
            ->format('Y-m-d H:i:s');
        $where[] = 'sr.received_at >= :cutoff';
        $parameters[':cutoff'] = $cutoff;
    }
    if ($wellCode !== '') {
        $where[] = 'w.well_code = :well_code';
        $parameters[':well_code'] = $wellCode;
    }
    $whereSql = implode(' AND ', $where);

    $rangeStatement = $pdo->prepare(
        "SELECT MIN(sr.received_at) AS first_received_at, MAX(sr.received_at) AS last_received_at
         FROM sensor_readings sr
         JOIN sensors s ON s.id = sr.sensor_id
         JOIN wells w ON w.sensor_id = s.id
         WHERE {$whereSql}"
    );
    $rangeStatement->execute($parameters);
    $range = $rangeStatement->fetch() ?: [];

    $granularity = $requestedGranularity;
    if ($granularity === 'auto') {
        $first = !empty($range['first_received_at']) ? new DateTimeImmutable((string)$range['first_received_at']) : null;
        $last = !empty($range['last_received_at']) ? new DateTimeImmutable((string)$range['last_received_at']) : null;
        $days = ($first !== null && $last !== null) ? $first->diff($last)->days + 1 : 0;
        $granularity = $days <= 31 ? 'day' : ($days <= 731 ? 'month' : 'year');
    }

    $periodExpression = match ($granularity) {
        'day' => "substr(sr.received_at, 1, 10)",
        'month' => "substr(sr.received_at, 1, 7)",
        default => "substr(sr.received_at, 1, 4)",
    };
    $validAbsorption = "CASE
        WHEN COALESCE(sr.h1, 0) = 0 AND COALESCE(sr.h2, 0) = 0 THEN 0
        WHEN COALESCE(sr.hasil, 0) > 0 THEN sr.hasil
        ELSE 0
    END";

    $statement = $pdo->prepare(
        "SELECT {$periodExpression} AS period,
                ROUND(SUM({$validAbsorption}), 2) AS absorbed_litres,
                COUNT(*) AS reading_count
         FROM sensor_readings sr
         JOIN sensors s ON s.id = sr.sensor_id
         JOIN wells w ON w.sensor_id = s.id
         WHERE {$whereSql}
         GROUP BY period
         ORDER BY period ASC"
    );
    $statement->execute($parameters);

    $buckets = [];
    $cumulativeLitres = 0.0;
    foreach ($statement->fetchAll() as $row) {
        $absorbedLitres = max(0, (float)($row['absorbed_litres'] ?? 0));
        $cumulativeLitres += $absorbedLitres;
        $buckets[] = [
            'period' => (string)$row['period'],
            'absorbedLitres' => round($absorbedLitres, 2),
            'cumulativeLitres' => round($cumulativeLitres, 2),
            'readingCount' => (int)$row['reading_count'],
        ];
    }

    echo json_encode([
        'success' => true,
        'granularity' => $granularity,
        'wellCode' => $wellCode !== '' ? $wellCode : null,
        'firstReceivedAt' => $range['first_received_at'] ?? null,
        'lastReceivedAt' => $range['last_received_at'] ?? null,
        'totalLitres' => round($cumulativeLitres, 2),
        'buckets' => $buckets,
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load historical measurements.',
    ]);
}
