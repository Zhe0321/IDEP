<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

$pdo = idepDatabase();

$columns = array_column($pdo->query('PRAGMA table_info(wells)')->fetchAll(), 'name');

if (!in_array('installer_name', $columns, true)) {
    $pdo->exec("ALTER TABLE wells ADD COLUMN installer_name TEXT");
}

if (!in_array('installation_date', $columns, true)) {
    $pdo->exec("ALTER TABLE wells ADD COLUMN installation_date TEXT");
}

echo "Columns added successfully.\n";
