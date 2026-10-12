<?php
declare(strict_types=1);
require __DIR__ . '/includes/admin-endpoint.php';
require __DIR__ . '/../database/db.php';
requireAdminJson();

$pdo = idepDatabase();
$districtId = (int)($_GET['district_id'] ?? 0);

$stmt = $pdo->prepare("SELECT id, name FROM sub_district WHERE district_id = :did ORDER BY name");
$stmt->execute([':did' => $districtId]);
echo json_encode($stmt->fetchAll());
