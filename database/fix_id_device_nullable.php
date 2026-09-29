<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
$pdo = idepDatabase();

$pdo->exec('PRAGMA foreign_keys = OFF');
$pdo->beginTransaction();

try {
    // Rebuild sensors table with id_device now nullable
    $pdo->exec("
        CREATE TABLE sensors_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sensor_code TEXT UNIQUE,
            sensor_name TEXT NOT NULL,
            sensor_type TEXT,
            id_device TEXT UNIQUE,
            status INTEGER,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT
        )
    ");

    $pdo->exec("
        INSERT INTO sensors_new (id, sensor_code, sensor_name, sensor_type, id_device, status, created_at, updated_at)
        SELECT id, sensor_code, sensor_name, sensor_type, NULLIF(id_device, ''), status, created_at, updated_at
        FROM sensors
    ");

    $pdo->exec("DROP TABLE sensors");
    $pdo->exec("ALTER TABLE sensors_new RENAME TO sensors");

    $pdo->commit();
    echo "sensors.id_device is now nullable. Existing empty strings converted to NULL.\n";

} catch (Throwable $e) {
    $pdo->rollBack();
    echo "Failed: " . $e->getMessage() . "\n";
}

$pdo->exec('PRAGMA foreign_keys = ON');