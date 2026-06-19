<?php
require_once '../../config/db.php';

// Auth
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
$tokenRow = $stmt->fetch();
if (!$tokenRow) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}
$userId = $tokenRow['user_id'];

// Get user's tier
$stmt = $pdo->prepare("SELECT kyc_tier FROM users WHERE id = ?");
$stmt->execute([$userId]);
$tier = $stmt->fetchColumn();

// Get limits for that tier
$stmt = $pdo->prepare("SELECT * FROM user_limits WHERE tier = ?");
$stmt->execute([$tier]);
$limits = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'tier' => $tier, 'limits' => $limits]);
