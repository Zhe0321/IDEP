<?php
declare(strict_types=1);

function idepDatabase(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $databasePath = __DIR__ . '/idep_groundwater.db';

    if (!file_exists($databasePath)) {
        throw new RuntimeException(
            'Database not found. Run: php database/run_schema.php'
        );
    }

    $pdo = new PDO("sqlite:{$databasePath}", null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');

    return $pdo;
}
