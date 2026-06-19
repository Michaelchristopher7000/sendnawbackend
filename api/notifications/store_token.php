<?php
require_once '../../config/db.php';
require_once '../auth.php';

$userId = authenticate($pdo);

$data = json_decode(file_get_contents('php://input'), true);
$fcmToken = $data['fcm_token'] ?? null;

if (!$fcmToken) {
    echo json_encode(['success' => false, 'message' => 'FCM token required']);
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET fcm_token = ? WHERE id = ?");
$stmt->execute([$fcmToken, $userId]);

// Optional: create an in-app notification
$stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'alert', 'Push Notifications Enabled', 'You will now receive important updates.')");
$stmt->execute([$userId]);

echo json_encode(['success' => true, 'message' => 'Token stored']);
