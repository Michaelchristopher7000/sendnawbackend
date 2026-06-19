<?php
require_once '../../config/db.php';

// Get user from token
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

$data = json_decode(file_get_contents('php://input'), true);
$amount = floatval($data['amount'] ?? 0);
$currency = strtoupper($data['currency'] ?? 'NGN');
$bankCode = $data['bank_code'] ?? '';
$accountNumber = $data['account_number'] ?? '';
$accountName = $data['account_name'] ?? '';

if ($amount <= 0 || !$bankCode || !$accountNumber || !$accountName) {
    echo json_encode(['success' => false, 'message' => 'Invalid withdrawal details']);
    exit;
}

// Check balance (wallets table uses currency_code)
$stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency_code = ? FOR UPDATE");
$stmt->execute([$userId, $currency]);
$wallet = $stmt->fetch();
if (!$wallet || $wallet['balance'] < $amount) {
    echo json_encode(['success' => false, 'message' => 'Insufficient balance']);
    exit;
}

// ----- DAILY WITHDRAWAL LIMIT CHECK (based on KYC tier) -----
// Get user's tier and daily withdrawal limit
$stmt = $pdo->prepare("SELECT u.kyc_tier, ul.daily_withdraw_limit 
                       FROM users u 
                       JOIN user_limits ul ON u.kyc_tier = ul.tier 
                       WHERE u.id = ?");
$stmt->execute([$userId]);
$limitData = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$limitData) {
    // Fallback default limit if no row found (should not happen)
    $dailyLimit = 20000;
} else {
    $dailyLimit = $limitData['daily_withdraw_limit'];
}

// Sum today's withdrawals (pending + completed)
$stmt = $pdo->prepare("SELECT IFNULL(SUM(amount), 0) as total 
                       FROM withdrawals 
                       WHERE user_id = ? 
                       AND DATE(created_at) = CURDATE() 
                       AND status IN ('pending', 'completed')");
$stmt->execute([$userId]);
$todayWithdrawn = $stmt->fetchColumn();

if ($todayWithdrawn + $amount > $dailyLimit) {
    echo json_encode([
        'success' => false,
        'message' => "Daily withdrawal limit of " . number_format($dailyLimit, 2) . " {$currency} exceeded. " .
            "You have already withdrawn " . number_format($todayWithdrawn, 2) . " {$currency} today."
    ]);
    exit;
}
// ----- END LIMIT CHECK -----

$reference = 'WDL_' . $userId . '_' . time() . '_' . rand(1000, 9999);

$pdo->beginTransaction();
try {
    // Deduct from wallet
    $stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND currency_code = ?");
    $stmt->execute([$amount, $userId, $currency]);

    // Insert withdrawal request (withdrawals table)
    $stmt = $pdo->prepare("INSERT INTO withdrawals (user_id, amount, currency, bank_code, account_number, account_name, reference, status)
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$userId, $amount, $currency, $bankCode, $accountNumber, $accountName, $reference]);

    // Insert into transactions table (using sender_id for the user, receiver_id = NULL)
    $stmt = $pdo->prepare("INSERT INTO transactions (sender_id, receiver_id, amount, type, status, description, created_at)
                           VALUES (?, NULL, ?, 'withdraw', 'pending', ?, NOW())");
    $stmt->execute([$userId, $amount, "Withdrawal request to $accountNumber"]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Withdrawal request submitted', 'reference' => $reference]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Failed: ' . $e->getMessage()]);
}
