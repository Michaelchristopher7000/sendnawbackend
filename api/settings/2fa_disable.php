<?php
// Headers and auth block (same as above)

$userId = $user['id'] ?? null;
if (empty($userId)) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET two_factor_enabled = 0, two_factor_secret = NULL WHERE id = ?");
$stmt->execute([$userId]);

echo json_encode(['success' => true, 'message' => '2FA disabled']);
?>