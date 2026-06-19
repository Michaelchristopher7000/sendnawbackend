<?php
require_once '../../config/db.php';
require_once '../auth.php';
$userId = authenticate($pdo);
$data = json_decode(file_get_contents('php://input'), true);
$groupId = intval($data['group_id'] ?? 0);
if (!$groupId) exit(json_encode(['success'=>false,'message'=>'Invalid group']));

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("SELECT contribution_amount, current_cycle FROM ajo_groups WHERE id = ? AND status = 'active' FOR UPDATE");
    $stmt->execute([$groupId]);
    $group = $stmt->fetch();
    if (!$group) throw new Exception('Group not active');
    $amount = $group['contribution_amount'];
    $cycle = $group['current_cycle'];

    // Check if user already contributed this cycle
    $stmt = $pdo->prepare("SELECT id FROM ajo_contributions WHERE group_id = ? AND user_id = ? AND cycle = ?");
    $stmt->execute([$groupId, $userId, $cycle]);
    if ($stmt->fetch()) throw new Exception('Already contributed this cycle');

    // Wallet check & deduct
    $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency_code = 'NGN' FOR UPDATE");
    $stmt->execute([$userId]);
    $wallet = $stmt->fetch();
    if (!$wallet || $wallet['balance'] < $amount) throw new Exception('Insufficient balance');
    $stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND currency_code = 'NGN'");
    $stmt->execute([$amount, $userId]);

    // Record contribution
    $stmt = $pdo->prepare("INSERT INTO ajo_contributions (group_id, user_id, cycle, amount) VALUES (?, ?, ?, ?)");
    $stmt->execute([$groupId, $userId, $cycle, $amount]);

    // Transaction log
    $ref = 'AJO_' . time() . '_' . rand(1000,9999);
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, description, reference, status) VALUES (?, 'ajo', ?, 'NGN', ?, ?, 'success')");
    $stmt->execute([$userId, -$amount, "Ajo contribution to group {$groupId}", $ref]);

    $pdo->commit();
    echo json_encode(['success'=>true, 'message'=>'Contribution recorded']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
?>