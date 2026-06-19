<?php
require_once '../../config/db.php';

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

$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tokenRow) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}
$userId = $tokenRow['user_id'];

$stmt = $pdo->prepare("SELECT id, device_name, ip_address, user_agent, last_activity, created_at 
                       FROM user_tokens 
                       WHERE user_id = ? 
                       ORDER BY last_activity DESC");
$stmt->execute([$userId]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'sessions' => $sessions]);
