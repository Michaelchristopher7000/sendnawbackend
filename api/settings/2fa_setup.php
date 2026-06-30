<?php
header('Content-Type: application/json');

require_once 'totp.php';
require_once '../../config/db.php';

// ─── Helper function ──────────────────────────────────────────────────────
function getUserIdFromToken($pdo) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    $token = '';
    if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
    }
    if (!$token) return null;

    $stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ? $row['user_id'] : null;
}
// ──────────────────────────────────────────────────────────────────────────

$userId = getUserIdFromToken($pdo);
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if 2FA is already enabled
$stmt = $pdo->prepare("SELECT two_factor_enabled FROM users WHERE id = ?");
$stmt->execute([$userId]);
$enabled = $stmt->fetchColumn();
if ($enabled) {
    echo json_encode(['success' => false, 'message' => '2FA is already enabled']);
    exit;
}

// Get user email
$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$email = $stmt->fetchColumn();

// Generate new secret
$secret = TOTP::generateSecret(16);

// Store secret in DB
$stmt = $pdo->prepare("UPDATE users SET two_factor_secret = ? WHERE id = ?");
$stmt->execute([$secret, $userId]);

// Build OTP URL
$otpUrl = "otpauth://totp/SendNaw:{$email}?secret={$secret}&issuer=SendNaw";

// Use external API for QR code — phpqrcode requires GD (ImageCreate) which is unavailable on Render
$qrDataUri = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($otpUrl);

echo json_encode([
    'success' => true,
    'secret'  => $secret,
    'otp_url' => $otpUrl,
    'qr_url'  => $qrDataUri,
]);