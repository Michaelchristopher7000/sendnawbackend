<?php
require_once '../../config/db.php';
// Manually include Paystack library (since Composer is not used)
require_once '../../vendor/paystack/src/autoload.php'; // adjust path if needed

use Yabacon\Paystack;

// Authenticate user via Bearer token
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
$token = '';
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}
if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tokenRow) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}
$userId = $tokenRow['user_id'];

// Get user email
$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userEmail = $stmt->fetchColumn();
if (!$userEmail) {
    echo json_encode(['success' => false, 'message' => 'User email not found']);
    exit;
}

// Get deposit amount
$data = json_decode(file_get_contents('php://input'), true);
$amount = floatval($data['amount'] ?? 0);
if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit;
}

// 🔴 Replace with your actual Paystack test secret key
$paystack = new Paystack('sk_test_dd2dcedfde736333ae471f18ac32ba83e9b4b89f');

$reference = 'DEP_' . $userId . '_' . time();

try {
    $transaction = $paystack->transaction->initialize([
        'amount' => $amount * 100, // in kobo
        'email' => $userEmail,
        'reference' => $reference,
        'currency' => 'NGN',
        'callback_url' => FRONTEND_URL . '/dashboard?payment_status=success'
    ]);

    if ($transaction->status) {
        // Store pending deposit
        $stmt = $pdo->prepare("INSERT INTO pending_deposits (user_id, reference, amount) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $reference, $amount]);

        echo json_encode(['success' => true, 'authorization_url' => $transaction->data->authorization_url]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not initialize payment']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
