<?php
require_once '../../config/db.php';
require_once '../auth.php';

$userId = authenticate($pdo);
$data = json_decode(file_get_contents('php://input'), true);
$cardId = intval($data['card_id'] ?? 0);
$amount = floatval($data['amount'] ?? 0);
$currency = $data['currency'] ?? 'NGN';

if (!$cardId || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid card or amount']);
    exit;
}

$pdo->beginTransaction();
try {
    // Get card details
    $stmt = $pdo->prepare("SELECT * FROM giftcard_products WHERE id = ? AND stock > 0 FOR UPDATE");
    $stmt->execute([$cardId]);
    $card = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$card) {
        throw new Exception('Card not available');
    }
    $sellingPrice = $card['selling_price'];
    if ($amount != $sellingPrice) {
        // For simplicity, we allow custom amount? But usually fixed face values. Adjust as needed.
        // Here we just use the selling price.
        $amount = $sellingPrice;
    }

    // Check wallet balance (NGN)
    $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency_code = 'NGN' FOR UPDATE");
    $stmt->execute([$userId]);
    $wallet = $stmt->fetch();
    if (!$wallet || $wallet['balance'] < $amount) {
        throw new Exception('Insufficient balance');
    }

    // Deduct
    $stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND currency_code = 'NGN'");
    $stmt->execute([$amount, $userId]);

    // Reduce stock
    $stmt = $pdo->prepare("UPDATE giftcard_products SET stock = stock - 1 WHERE id = ?");
    $stmt->execute([$cardId]);

    // Generate random gift card code (12 alphanumeric)
    $code = strtoupper(substr(bin2hex(random_bytes(6)), 0, 12));

    // Insert into user_giftcards
    $stmt = $pdo->prepare("INSERT INTO user_giftcards (user_id, product_id, card_code, face_value) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $cardId, $code, $amount]);

    // Transaction log
    $ref = 'GC_' . time() . '_' . rand(1000, 9999);
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, description, reference, status) VALUES (?, 'giftcard', ?, 'NGN', ?, ?, 'success')");
    $stmt->execute([$userId, -$amount, "Purchased {$card['brand']} gift card", $ref]);

    $pdo->commit();
    echo json_encode(['success' => true, 'code' => $code, 'amount' => $amount]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
