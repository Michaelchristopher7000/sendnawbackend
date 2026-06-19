<?php
require_once '../../config/db.php';

$type = $_GET['type'] ?? '';
if (!$type) {
    echo json_encode(['success' => false, 'message' => 'Type required']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name FROM service_providers WHERE type = ? AND is_active = 1");
$stmt->execute([$type]);
$providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'providers' => $providers]);
