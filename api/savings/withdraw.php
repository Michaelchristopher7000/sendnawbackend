<?php
require_once '../../config/db.php';
require_once '../auth.php';
$userId = authenticate($pdo);
$data = json_decode(file_get_contents('php://input'), true);
$savingId = intval($data['saving_id'] ?? 0);
if (!$savingId) exit(json_encode(['success'=>false,'message'=>'Invalid saving ID']));

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("SELECT us.*, sp.interest_rate FROM user_savings us JOIN savings_plans sp ON us.plan_id = sp.id WHERE us.id = ? AND us.user_id = ? AND us.status = 'active' FOR UPDATE");
    $stmt->execute([$savingId, $userId]);
    $saving = $stmt->fetch();
    if (!$saving) throw new Exception('Saving not found');
    if ($saving['type'] === 'fixed' && strtotime($saving['end_date']) > time()) {
        throw new Exception('Saving has not matured yet');
    }
    // Calculate interest
    $interest = 0;
    if ($saving['type'] === 'fixed') {
        $interest = $saving['amount'] * ($saving['interest_rate'] / 100);
    } else {
        // Flexible: simple interest based on days passed
        $days = floor((time() - strtotime($saving['start_date'])) / 86400);
        $interest = $saving['amount'] * ($saving['interest_rate'] / 100) * ($days / 365);
    }
    $total = $saving['amount'] + $interest;

    // Credit wallet
    $stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency_code = 'NGN'");
    $stmt->execute([$total, $userId]);

    $stmt = $pdo->prepare("UPDATE user_savings SET status = 'withdrawn' WHERE id = ?");
    $stmt->execute([$savingId]);

    $ref = 'SAV_WITHDRAW_' . time();
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, description, reference, status) VALUES (?, 'savings_withdraw', ?, 'NGN', ?, ?, 'success')");
    $stmt->execute([$userId, $total, "Withdrawn savings + interest", $ref]);

    $pdo->commit();
    echo json_encode(['success'=>true, 'amount'=>$total, 'interest'=>$interest, 'message'=>'Withdrawn successfully']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
?>