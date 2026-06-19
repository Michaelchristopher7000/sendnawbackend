<?php
require_once '../../config/db.php';
require_once __DIR__ . '/../../vendor/paystack/src/autoload.php';
use Yabacon\Paystack;

// Get raw input and signature
$input = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

// Verify webhook signature (security)
$secret = 'sk_test_dd2dcedfde736333ae471f18ac32ba83e9b4b89f';
$computed = hash_hmac('sha512', $input, $secret);
if ($signature !== $computed) {
    http_response_code(401);
    exit('Invalid signature');
}

$event = json_decode($input);

if ($event->event === 'charge.success') {
    $reference = $event->data->reference;
    $amountInKobo = $event->data->amount;
    $amount = $amountInKobo / 100;
    $currency = $event->data->currency;

    // Find pending deposit
    $stmt = $pdo->prepare("SELECT user_id FROM pending_deposits WHERE reference = ? AND status = 'pending'");
    $stmt->execute([$reference]);
    $deposit = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($deposit) {
        $userId = $deposit['user_id'];

        // Credit user's wallet
        $stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency_code = ?");
        $stmt->execute([$amount, $userId, $currency]);

        // Update pending deposit status
        $stmt = $pdo->prepare("UPDATE pending_deposits SET status = 'completed' WHERE reference = ?");
        $stmt->execute([$reference]);

        // Log transaction
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, status, description, created_at)
                               VALUES (?, 'deposit', ?, ?, 'success', 'Paystack deposit', NOW())");
        $stmt->execute([$userId, $amount, $currency]);

        http_response_code(200);
        echo 'Webhook processed';
    } else {
        http_response_code(404);
        echo 'Deposit not found';
    }
} else {
    http_response_code(200);
    echo 'Event not handled';
}
?>