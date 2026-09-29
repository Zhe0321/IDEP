<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

$pdo = idepDatabase();

$pdo->exec("ALTER TABLE wells ADD COLUMN installer_name TEXT");
$pdo->exec("ALTER TABLE wells ADD COLUMN installation_date TEXT");

echo "Columns added successfully.\n";