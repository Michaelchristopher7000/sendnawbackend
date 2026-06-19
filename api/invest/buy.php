<?php
require_once '../../config/db.php';
require_once '../auth.php';
$userId = authenticate($pdo);

$data = json_decode(file_get_contents('php://input'), true);
$stockId = intval($data['stock_id'] ?? 0);
$quantity = intval($data['quantity'] ?? 0);
if (!$stockId || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid stock or quantity']);
    exit;
}

$pdo->beginTransaction();
try {
    // Get stock price
    $stmt = $pdo->prepare("SELECT symbol, company_name, current_price FROM stocks WHERE id = ? AND is_active = 1 FOR UPDATE");
    $stmt->execute([$stockId]);
    $stock = $stmt->fetch();
    if (!$stock) throw new Exception('Stock not available');
    $price = $stock['current_price'];
    $total = $price * $quantity;

    // Check wallet balance (NGN)
    $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency_code = 'NGN' FOR UPDATE");
    $stmt->execute([$userId]);
    $wallet = $stmt->fetch();
    if (!$wallet || $wallet['balance'] < $total) throw new Exception('Insufficient balance');

    // Deduct from wallet
    $stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND currency_code = 'NGN'");
    $stmt->execute([$total, $userId]);

    // Update portfolio (insert or update with average buy price)
    $stmt = $pdo->prepare("INSERT INTO user_portfolio (user_id, stock_id, quantity, average_buy_price) 
                           VALUES (?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE 
                           quantity = quantity + VALUES(quantity),
                           average_buy_price = ((average_buy_price * (quantity)) + (VALUES(quantity) * VALUES(average_buy_price))) / (quantity + VALUES(quantity))");
    $stmt->execute([$userId, $stockId, $quantity, $price]);

    // Record transaction
    $stmt = $pdo->prepare("INSERT INTO stock_transactions (user_id, stock_id, type, quantity, price, total_amount) VALUES (?, ?, 'buy', ?, ?, ?)");
    $stmt->execute([$userId, $stockId, $quantity, $price, $total]);

    // Main transaction log
    $ref = 'STOCK_BUY_' . time() . '_' . rand(1000, 9999);
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, description, reference, status) VALUES (?, 'invest', ?, 'NGN', ?, ?, 'success')");
    $stmt->execute([$userId, -$total, "Bought {$quantity} shares of {$stock['symbol']} at ₦{$price}", $ref]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "Bought {$quantity} shares of {$stock['symbol']} for ₦{$total}"]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
