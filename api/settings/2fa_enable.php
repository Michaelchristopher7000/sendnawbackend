<?php
require_once 'totp.php';

// Headers, CORS, and authentication (same as above)
session_start();
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$code = $data['code'] ?? '';
$secret = $data['secret'] ?? ''; // client sends the secret shown during setup

// Retrieve the stored secret from DB (to ensure it matches)
$stmt = $pdo->prepare("SELECT two_factor_secret FROM users WHERE id = ?");
$stmt->execute([$userId]);
$storedSecret = $stmt->fetchColumn();

if (!$storedSecret || $storedSecret !== $secret) {
    echo json_encode(['success' => false, 'message' => 'Secret mismatch. Please restart 2FA setup.']);
    exit;
}

// Verify the TOTP code
if (TOTP::verify($secret, $code)) {
    $stmt = $pdo->prepare("UPDATE users SET two_factor_enabled = 1 WHERE id = ?");
    $stmt->execute([$userId]);
    echo json_encode(['success' => true, 'message' => '2FA enabled successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
}
?>