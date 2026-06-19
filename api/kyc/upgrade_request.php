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

$data = json_decode(file_get_contents('php://input'), true);
$requestedTier = intval($data['tier'] ?? 0);

if ($requestedTier < 2 || $requestedTier > 3) {
    echo json_encode(['success' => false, 'message' => 'Invalid tier requested']);
    exit;
}

// Get current tier
$stmt = $pdo->prepare("SELECT kyc_tier FROM users WHERE id = ?");
$stmt->execute([$userId]);
$currentTier = $stmt->fetchColumn();
if ($requestedTier <= $currentTier) {
    echo json_encode(['success' => false, 'message' => 'You already have this tier or higher']);
    exit;
}

// Check if there is already a pending request for this tier
$stmt = $pdo->prepare("SELECT id FROM kyc_upgrade_requests WHERE user_id = ? AND requested_tier = ? AND status = 'pending'");
$stmt->execute([$userId, $requestedTier]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'You already have a pending request for this tier']);
    exit;
}

// Insert request
$stmt = $pdo->prepare("INSERT INTO kyc_upgrade_requests (user_id, requested_tier, status) VALUES (?, ?, 'pending')");
$stmt->execute([$userId, $requestedTier]);

echo json_encode(['success' => true, 'message' => 'Upgrade request submitted. Admin will review.']);
