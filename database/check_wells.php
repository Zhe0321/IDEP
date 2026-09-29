<?php
require __DIR__ . '/db.php';
$pdo = idepDatabase();
print_r($pdo->query("SELECT id, well_code, well_name, latitude, longitude, installer_name FROM wells ORDER BY id DESC LIMIT 5")->fetchAll());