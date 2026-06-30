<?php
header('Content-Type: application/json');
require_once '../../config/db.php';

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
$token = '';
if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}
if (!$token) {
    echo json_encode(['status' => 'error', 'message' => 'No token']);
    exit;
}

$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
    exit;
}

$userId = $row['user_id'];
$stmt = $pdo->prepare("SELECT id, full_name, email, phone, sendnaw_tag, account_number,
       default_currency, display_currency, role, account_type, avatar_url,
       kyc_tier, kyc_status, dob, address, bvn, nin,
       two_factor_enabled, (transaction_pin IS NOT NULL) as has_pin
FROM users
WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

echo json_encode(['status' => 'success', 'data' => $user]);
