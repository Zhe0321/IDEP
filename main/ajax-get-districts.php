<?php
declare(strict_types=1);
require __DIR__ . '/includes/admin-endpoint.php';
require __DIR__ . '/../database/db.php';
requireAdminJson();

$pdo = idepDatabase();
$provinceId = (int)($_GET['province_id'] ?? 0);

$stmt = $pdo->prepare("SELECT id, name FROM district_city WHERE province_id = :pid ORDER BY name");
$stmt->execute([':pid' => $provinceId]);
echo json_encode($stmt->fetchAll());
