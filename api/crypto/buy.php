<?php
header("Content-Type: application/json");
require_once '../../config/db.php';
require_once '../../utils/auth.php';

$auth = authenticate();
if (!$auth) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$user_id = $auth['user_id'];

$input = json_decode(file_get_contents('php://input'), true);
$currency = $input['currency'] ?? '';
$amount_usd = floatval($input['amount_usd'] ?? 0);
$price_usd = floatval($input['price_usd'] ?? 0);
if (!$currency || $amount_usd <= 0 || $price_usd <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Check USD wallet balance (assuming main wallet table 'wallets' with currency 'USD')
    $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency = 'USD' FOR UPDATE");
    $stmt->execute([$user_id]);
    $usdWallet = $stmt->fetch();
    if (!$usdWallet || $usdWallet['balance'] < $amount_usd) {
        throw new Exception("Insufficient USD balance");
    }

    // Deduct USD
    $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND currency = 'USD'")
        ->execute([$amount_usd, $user_id]);

    // Add crypto
    $cryptoAmount = $amount_usd / $price_usd;
    $pdo->prepare("INSERT INTO crypto_wallets (user_id, currency, balance) VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE balance = balance + ?")
        ->execute([$user_id, $currency, $cryptoAmount, $cryptoAmount]);

    // Log transaction
    $pdo->prepare("INSERT INTO crypto_transactions (user_id, type, currency, amount, price_usd, total_usd, status)
                    VALUES (?, 'buy', ?, ?, ?, ?, 'completed')")
        ->execute([$user_id, $currency, $cryptoAmount, $price_usd, $amount_usd]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "Bought $cryptoAmount $currency"]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>