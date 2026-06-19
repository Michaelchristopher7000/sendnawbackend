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

$data = json_decode(file_get_contents('php://input'), true);
$currentPassword = $data['current_password'] ?? '';
$newPassword = $data['new_password'] ?? '';

if (!$currentPassword || !$newPassword || strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'message' => 'Current password and new password (min 6 chars) required']);
    exit;
}

$stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    exit;
}

$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
$stmt->execute([$newHash, $userId]);

echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
