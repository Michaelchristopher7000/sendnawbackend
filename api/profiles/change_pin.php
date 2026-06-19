<?php
header("Content-Type: application/json");
require_once '../../config/db.php';
$authenticated_user = require_once '../../middleware/auth_check.php';

if (!$authenticated_user || !isset($authenticated_user['id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $authenticated_user['id'];
$input = json_decode(file_get_contents('php://input'), true);

$current = $input['current_pin'] ?? '';
$new = $input['new_pin'] ?? '';

if (empty($current) || empty($new) || !preg_match('/^\d{4}$/', $new)) {
    echo json_encode(['status' => 'error', 'message' => 'PIN must be 4 digits']);
    exit;
}

$stmt = $pdo->prepare("SELECT pin_hash FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || !password_verify($current, $user['pin_hash'])) {
    echo json_encode(['status' => 'error', 'message' => 'Current PIN is incorrect']);
    exit;
}

$newHash = password_hash($new, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET pin_hash = ? WHERE id = ?");
$stmt->execute([$newHash, $user_id]);

echo json_encode(['status' => 'success', 'message' => 'PIN updated']);
