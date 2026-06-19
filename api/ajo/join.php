<?php
require_once '../../config/db.php';
require_once '../auth.php';
$userId = authenticate($pdo);
$data = json_decode(file_get_contents('php://input'), true);
$groupId = intval($data['group_id'] ?? 0);
if (!$groupId) exit(json_encode(['success'=>false,'message'=>'Invalid group']));
$stmt = $pdo->prepare("SELECT id FROM ajo_members WHERE group_id = ? AND user_id = ?");
$stmt->execute([$groupId, $userId]);
if ($stmt->fetch()) exit(json_encode(['success'=>false,'message'=>'Already joined']));
$stmt = $pdo->prepare("INSERT INTO ajo_members (group_id, user_id) VALUES (?, ?)");
$stmt->execute([$groupId, $userId]);
// Update member count
$stmt = $pdo->prepare("UPDATE ajo_groups SET member_count = member_count + 1 WHERE id = ?");
$stmt->execute([$groupId]);
echo json_encode(['success'=>true]);
?>