<?php
declare(strict_types=1);

/**
 * Run this once (or after any schema.sql change) to (re)build the database:
 *   php run_schema.php
 *
 * It reads schema.sql and executes it against idep_groundwater.db,
 * both expected to sit in this same folder.
 */

$dbPath     = __DIR__ . '/idep_groundwater.db';
$schemaPath = __DIR__ . '/schema.sql';

if (!file_exists($schemaPath)) {
    fwrite(STDERR, "schema.sql not found at {$schemaPath}\n");
    exit(1);
}

$pdo = new PDO("sqlite:{$dbPath}", null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$sql = file_get_contents($schemaPath);
$pdo->exec($sql);

echo "Database built successfully at {$dbPath}\n";

// Populate the Bali location hierarchy, the 81 workbook wells, and the
// additional IDEP Ubud monitoring site connected to device_1.
require __DIR__ . '/import_bali_wilayah.php';
require __DIR__ . '/import_recharge_wells.php';
require __DIR__ . '/register_idep_device.php';
