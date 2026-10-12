<?php
declare(strict_types=1);

/**
 * Optional: adds a `type` column to `village` so the Add Location manager can
 * remember whether a village is a Desa or a Kelurahan. Safe to run more than once.
 *
 *   php database/add_location_types.php
 *
 * Without it everything still works: imported villages get their type from the
 * BPS code, and newly added villages show as Desa.
 */

require __DIR__ . '/db.php';

$pdo = idepDatabase();
$columns = array_column($pdo->query('PRAGMA table_info(village)')->fetchAll(), 'name');

if (in_array('type', $columns, true)) {
    echo "village.type already exists. Nothing to do.\n";
    exit(0);
}

$pdo->beginTransaction();
$pdo->exec("ALTER TABLE village ADD COLUMN type TEXT");
// BPS codes: 7th digit 1 = Kelurahan, 2 = Desa.
$pdo->exec("UPDATE village SET type = CASE substr(code, 7, 1) WHEN '1' THEN 'kelurahan' ELSE 'desa' END");
$pdo->commit();

echo "Added village.type and filled it for existing villages.\n";
