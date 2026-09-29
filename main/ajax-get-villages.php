<?php
declare(strict_types=1);
require __DIR__ . '/includes/admin-endpoint.php';
require __DIR__ . '/../database/db.php';
requireAdminJson();

$pdo = idepDatabase();
$subDistrictId = (int)($_GET['sub_district_id'] ?? 0);

$stmt = $pdo->prepare("SELECT id, name FROM village WHERE sub_district_id = :sid ORDER BY name");
$stmt->execute([':sid' => $subDistrictId]);
echo json_encode($stmt->fetchAll());
