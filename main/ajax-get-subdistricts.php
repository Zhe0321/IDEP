<?php
declare(strict_types=1);
header('Content-Type: application/json');
require __DIR__ . '/../database/db.php';

$pdo = idepDatabase();
$districtId = (int)($_GET['district_id'] ?? 0);

$stmt = $pdo->prepare("SELECT id, name FROM sub_district WHERE district_id = :did ORDER BY name");
$stmt->execute([':did' => $districtId]);
echo json_encode($stmt->fetchAll());