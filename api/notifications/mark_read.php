<?php
header("Content-Type: application/json");
// Handle preflight OPTIONS request
?>



// Same headers and auth
if (session_status() === PHP_SESSION_NONE) {
session_start();
}
$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) exit(json_encode(['success'=>false,'message'=>'Authentication required']));

$data = json_decode(file_get_contents('php://input'), true);
$notifId = $data['notification_id'] ?? 0;
if (!$notifId) exit(json_encode(['success'=>false,'message'=>'Invalid ID']));

$stmt = $pdo->prepare("UPDATE notifications SET read = 1 WHERE id = ? AND user_id = ?");
$stmt->execute([$notifId, $userId]);

echo json_encode(['success'=>true]);
?>