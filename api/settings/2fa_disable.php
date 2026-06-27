<?php
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

$stmt = $pdo->prepare("UPDATE users SET two_factor_enabled = 0, two_factor_secret = NULL WHERE id = ?");
$stmt->execute([$userId]);

echo json_encode(['success' => true, 'message' => '2FA disabled']);
?>