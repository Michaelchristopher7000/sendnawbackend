<?php
// Same headers and auth
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) exit(json_encode(['success' => false, 'message' => 'Authentication required']));

$stmt = $pdo->prepare("UPDATE notifications SET read = 1 WHERE user_id = ?");
$stmt->execute([$userId]);

echo json_encode(['success' => true]);
