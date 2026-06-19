<?php
require_once '../../config/db.php';
require_once '../auth.php';

// CORS and OPTIONS same as buy.php
$userId = authenticate($pdo);

$data = json_decode(file_get_contents('php://input'), true);
$stockId = intval($data['stock_id'] ?? 0);
$quantity = intval($data['quantity'] ?? 0);
if (!$stockId || $quantity <= 0) {
    echo json_encode(['success'=>false,'message'=>'Invalid stock or quantity']);
    exit;
}

$pdo->beginTransaction();
try {
    // Get stock price
    $stmt = $pdo->prepare("SELECT symbol, current_price FROM stocks WHERE id = ? AND is_active = 1 FOR UPDATE");
    $stmt->execute([$stockId]);
    $stock = $stmt->fetch();
    if (!$stock) throw new Exception('Stock not available');
    $price = $stock['current_price'];
    $total = $price * $quantity;

    // Check portfolio
    $stmt = $pdo->prepare("SELECT quantity, average_buy_price FROM user_portfolio WHERE user_id = ? AND stock_id = ? FOR UPDATE");
    $stmt->execute([$userId, $stockId]);
    $portfolio = $stmt->fetch();
    if (!$portfolio || $portfolio['quantity'] < $quantity) throw new Exception('Insufficient shares');

    // Update portfolio (reduce quantity, delete if zero)
    $newQuantity = $portfolio['quantity'] - $quantity;
    if ($newQuantity == 0) {
        $stmt = $pdo->prepare("DELETE FROM user_portfolio WHERE user_id = ? AND stock_id = ?");
        $stmt->execute([$userId, $stockId]);
    } else {
        $stmt = $pdo->prepare("UPDATE user_portfolio SET quantity = ? WHERE user_id = ? AND stock_id = ?");
        $stmt->execute([$newQuantity, $userId, $stockId]);
    }

    // Credit wallet
    $stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency_code = 'NGN'");
    $stmt->execute([$total, $userId]);

    // Record transaction
    $stmt = $pdo->prepare("INSERT INTO stock_transactions (user_id, stock_id, type, quantity, price, total_amount) VALUES (?, ?, 'sell', ?, ?, ?)");
    $stmt->execute([$userId, $stockId, $quantity, $price, $total]);

    // Main transaction log
    $ref = 'STOCK_SELL_' . time() . '_' . rand(1000,9999);
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, description, reference, status) VALUES (?, 'invest', ?, 'NGN', ?, ?, 'success')");
    $stmt->execute([$userId, $total, "Sold {$quantity} shares of {$stock['symbol']} at ₦{$price}", $ref]);

    $profit = $total - ($portfolio['average_buy_price'] * $quantity);
    $pdo->commit();
    echo json_encode(['success'=>true, 'message'=>"Sold {$quantity} shares of {$stock['symbol']} for ₦{$total}", 'profit'=>$profit]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
?>