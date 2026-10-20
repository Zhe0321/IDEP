<?php
declare(strict_types=1);


if (PHP_SAPI !== 'cli') { http_response_code(404); exit; } // command-line only
require __DIR__ . '/db.php';

$pdo = idepDatabase();
$columns = array_column($pdo->query('PRAGMA table_info(wells)')->fetchAll(), 'name');
if (!in_array('photo_path', $columns, true)) {
    $pdo->exec("ALTER TABLE wells ADD COLUMN photo_path TEXT");
}
echo "Column 'photo_path' added to wells table.\n";
