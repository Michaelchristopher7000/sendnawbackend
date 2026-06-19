<?php
require_once '../../config/db.php';
require_once '../auth.php';
$userId = authenticate($pdo);
$data = json_decode(file_get_contents('php://input'), true);
$amount = floatval($data['amount'] ?? 0);
if ($amount <= 0) exit(json_encode(['success'=>false,'message'=>'Invalid amount']));

$pdo->beginTransaction();
try {
    // Get card
    $stmt = $pdo->prepare("SELECT id FROM virtual_cards WHERE user_id = ? AND status = 'active' FOR UPDATE");
    $stmt->execute([$userId]);
    $card = $stmt->fetch();
    if (!$card) throw new Exception('No active virtual card');

    // Check wallet balance
    $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency_code = 'USD' FOR UPDATE");
    $stmt->execute([$userId]);
    $wallet = $stmt->fetch();
    if (!$wallet || $wallet['balance'] < $amount) throw new Exception('Insufficient USD balance');

    // Deduct wallet
    $stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND currency_code = 'USD'");
    $stmt->execute([$amount, $userId]);

    // Add to card balance
    $stmt = $pdo->prepare("UPDATE virtual_cards SET balance = balance + ? WHERE id = ?");
    $stmt->execute([$amount, $card['id']]);

    $ref = 'CARD_FUND_' . time();
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, description, reference, status) VALUES (?, 'card_fund', ?, 'USD', ?, ?, 'success')");
    $stmt->execute([$userId, -$amount, "Funded virtual card", $ref]);

    $pdo->commit();
    echo json_encode(['success'=>true, 'message'=>"Card funded with $$amount"]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
?>