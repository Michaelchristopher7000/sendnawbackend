<?php
require_once '../../config/db.php';
require_once '../auth.php';

$userId = authenticate($pdo);
$data = json_decode(file_get_contents('php://input'), true);
$cardCode = trim($data['card_code'] ?? '');

if (!$cardCode) {
    echo json_encode(['success' => false, 'message' => 'Card code required']);
    exit;
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("SELECT ug.id, ug.product_id, gp.buyback_price, gp.selling_price FROM user_giftcards ug JOIN giftcard_products gp ON ug.product_id = gp.id WHERE ug.card_code = ? AND ug.user_id = ? AND ug.status = 'active' FOR UPDATE");
    $stmt->execute([$cardCode, $userId]);
    $card = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$card) {
        throw new Exception('Invalid or already used card');
    }
    $sellAmount = $card['buyback_price'];

    // Credit wallet
    $stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency_code = 'NGN'");
    $stmt->execute([$sellAmount, $userId]);

    // Mark card as used
    $stmt = $pdo->prepare("UPDATE user_giftcards SET status = 'used' WHERE id = ?");
    $stmt->execute([$card['id']]);

    // Transaction log
    $ref = 'GC_SELL_' . time();
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, description, reference, status) VALUES (?, 'giftcard_sell', ?, 'NGN', ?, ?, 'success')");
    $stmt->execute([$userId, $sellAmount, "Sold gift card code $cardCode", $ref]);

    $pdo->commit();
    echo json_encode(['success' => true, 'amount' => $sellAmount]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
