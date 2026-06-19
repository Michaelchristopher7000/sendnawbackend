<?php
require_once '../../config/db.php';
require_once '../auth.php';

$userId = authenticate($pdo);

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'notifications' => $notifications]);
