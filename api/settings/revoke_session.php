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

$stmt = $pdo->prepare("SELECT id, user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tokenRow) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}
$currentTokenId = $tokenRow['id'];
$userId = $tokenRow['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$tokenId = $data['token_id'] ?? 0;
if (!$tokenId) {
    echo json_encode(['success' => false, 'message' => 'Token ID required']);
    exit;
}

// Prevent revoking the current session
if ($tokenId == $currentTokenId) {
    echo json_encode(['success' => false, 'message' => 'Cannot revoke your current session']);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM user_tokens WHERE id = ? AND user_id = ?");
$stmt->execute([$tokenId, $userId]);

echo json_encode(['success' => true, 'message' => 'Session revoked']);
