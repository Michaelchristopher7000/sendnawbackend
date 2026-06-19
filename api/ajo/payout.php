<?php
require_once '../../config/db.php';
require_once '../auth.php';
$userId = authenticate($pdo);
$data = json_decode(file_get_contents('php://input'), true);
$groupId = intval($data['group_id'] ?? 0);
if (!$groupId) exit(json_encode(['success'=>false,'message'=>'Invalid group']));

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("SELECT created_by, contribution_amount, member_count, current_cycle, next_payout_user FROM ajo_groups WHERE id = ? AND status = 'active' FOR UPDATE");
    $stmt->execute([$groupId]);
    $group = $stmt->fetch();
    if (!$group) throw new Exception('Group not found');
    if ($group['created_by'] != $userId) throw new Exception('Only group creator can process payout');
    $payoutUser = $group['next_payout_user'];
    if (!$payoutUser) throw new Exception('No payout user set');

    // Check all members contributed this cycle
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM ajo_members WHERE group_id = ?");
    $stmt->execute([$groupId]);
    $totalMembers = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ajo_contributions WHERE group_id = ? AND cycle = ?");
    $stmt->execute([$groupId, $group['current_cycle']]);
    $contributed = $stmt->fetchColumn();
    if ($contributed < $totalMembers) throw new Exception('Not all members have contributed');

    $totalAmount = $totalMembers * $group['contribution_amount'];

    // Credit payout user
    $stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency_code = 'NGN'");
    $stmt->execute([$totalAmount, $payoutUser]);

    // Transaction for payout
    $ref = 'AJO_PAYOUT_' . time();
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, description, reference, status) VALUES (?, 'ajo_payout', ?, 'NGN', ?, ?, 'success')");
    $stmt->execute([$payoutUser, $totalAmount, "Ajo payout from group {$groupId}", $ref]);

    // Move to next cycle
    $nextCycle = $group['current_cycle'] + 1;
    // Determine next payout user (simplified: next member in list)
    $stmt = $pdo->prepare("SELECT user_id FROM ajo_members WHERE group_id = ? ORDER BY joined_at LIMIT 1 OFFSET ?");
    $stmt->execute([$groupId, $nextCycle % $totalMembers]);
    $nextUser = $stmt->fetchColumn();
    if (!$nextUser) $nextUser = $payoutUser; // fallback

    $stmt = $pdo->prepare("UPDATE ajo_groups SET current_cycle = ?, next_payout_user = ? WHERE id = ?");
    $stmt->execute([$nextCycle, $nextUser, $groupId]);

    $pdo->commit();
    echo json_encode(['success'=>true, 'message'=>"Payout of $totalAmount sent to user $payoutUser"]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
?>