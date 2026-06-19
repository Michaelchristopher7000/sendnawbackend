<?php
require_once '../../config/db.php';
require_once '../auth.php';
$userId = authenticate($pdo);
$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'] ?? '';
$amount = floatval($data['amount'] ?? 0);
$frequency = $data['frequency'] ?? 'weekly';
if (!$name || $amount <= 0) exit(json_encode(['success'=>false,'message'=>'Invalid data']));

$stmt = $pdo->prepare("INSERT INTO ajo_groups (name, created_by, contribution_amount, frequency, member_count, next_payout_user) VALUES (?, ?, ?, ?, 1, ?)");
$stmt->execute([$name, $userId, $amount, $frequency, $userId]);
$groupId = $pdo->lastInsertId();
$stmt = $pdo->prepare("INSERT INTO ajo_members (group_id, user_id) VALUES (?, ?)");
$stmt->execute([$groupId, $userId]);
echo json_encode(['success'=>true, 'group_id'=>$groupId]);
?>