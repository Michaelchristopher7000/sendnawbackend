<?php
require_once 'totp.php';
require_once '../../config/db.php';
require_once 'auth_helper.php';


// ─── Helper function (copied here to avoid missing include) ──────────────
function getUserIdFromToken($pdo) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    $token = '';
    if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) { // ✅ fixed typo
        $token = $matches[1];
    }
    if (!$token) return null;

    $stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ? $row['user_id'] : null;
}

$userId = getUserIdFromToken($pdo);
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$code = $data['code'] ?? '';

// Retrieve the stored secret from DB
$stmt = $pdo->prepare("SELECT two_factor_secret FROM users WHERE id = ?");
$stmt->execute([$userId]);
$storedSecret = $stmt->fetchColumn();

if (!$storedSecret) {
    echo json_encode(['success' => false, 'message' => '2FA not set up. Please run setup first.']);
    exit;
}

// Verify the TOTP code against stored secret
if (TOTP::verify($storedSecret, $code)) {
    // Enable 2FA
    $stmt = $pdo->prepare("UPDATE users SET two_factor_enabled = 1 WHERE id = ?");
    $stmt->execute([$userId]);
    echo json_encode(['success' => true, 'message' => '2FA enabled successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
}
?>