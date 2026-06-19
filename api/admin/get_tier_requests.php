<?php
require_once '../../config/db.php';

// Admin auth
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
$token = '';
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}
if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$stmt = $pdo->prepare("SELECT u.role FROM user_tokens t JOIN users u ON t.user_id = u.id WHERE t.token = ?");
$stmt->execute([$token]);
$admin = $stmt->fetch();
if (!$admin || !in_array($admin['role'], ['admin', 'ceo'])) {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

$stmt = $pdo->prepare("SELECT r.*, u.full_name, u.email, u.phone, u.kyc_tier as current_tier 
                       FROM kyc_upgrade_requests r 
                       JOIN users u ON r.user_id = u.id 
                       WHERE r.status = 'pending' 
                       ORDER BY r.created_at ASC");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'requests' => $requests]);
