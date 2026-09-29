<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

$pdo = idepDatabase();
$pdo->exec("ALTER TABLE wells ADD COLUMN photo_path TEXT");
echo "Column 'photo_path' added to wells table.\n";