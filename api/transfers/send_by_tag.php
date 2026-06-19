<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once __DIR__ . '/../../utils/mailer.php';

// Load FCM helper only if exists
$fcmFile = __DIR__ . '/../utils/sendFCM.php';
if (file_exists($fcmFile)) {
    require_once $fcmFile;
    $fcmAvailable = true;
} else {
    $fcmAvailable = false;
}

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
$senderId = $tokenRow['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$tag = trim($data['tag'] ?? '');
$amount = floatval($data['amount'] ?? 0);
$currency = strtoupper(trim($data['currency'] ?? 'NGN'));

if (!$tag || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Tag and amount required']);
    exit;
}

// Limit check
$stmt = $pdo->prepare("SELECT u.kyc_tier, ul.single_transfer_limit 
                       FROM users u 
                       JOIN user_limits ul ON u.kyc_tier = ul.tier 
                       WHERE u.id = ?");
$stmt->execute([$senderId]);
$limitData = $stmt->fetch(PDO::FETCH_ASSOC);
$singleLimit = $limitData ? $limitData['single_transfer_limit'] : 10000;

if ($amount > $singleLimit) {
    echo json_encode([
        'success' => false,
        'message' => "Single transfer limit is " . number_format($singleLimit, 2) . " {$currency}. " .
                     "Your transfer of " . number_format($amount, 2) . " {$currency} exceeds this limit. " .
                     "Please upgrade your KYC tier to increase limits."
    ]);
    exit;
}

// Find receiver
$stmt = $pdo->prepare("SELECT id, email, full_name FROM users WHERE sendnaw_tag = ?");
$stmt->execute([$tag]);
$receiver = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$receiver) {
    echo json_encode(['success' => false, 'message' => 'Recipient not found']);
    exit;
}
$receiverId = $receiver['id'];
$receiverEmail = $receiver['email'];
$receiverName = $receiver['full_name'];

if ($senderId == $receiverId) {
    echo json_encode(['success' => false, 'message' => 'Cannot send to yourself']);
    exit;
}

// Get sender details
$stmt = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
$stmt->execute([$senderId]);
$sender = $stmt->fetch(PDO::FETCH_ASSOC);
$senderEmail = $sender['email'];
$senderName = $sender['full_name'];

$pdo->beginTransaction();
try {
    // Check sender balance
    $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency_code = ? FOR UPDATE");
    $stmt->execute([$senderId, $currency]);
    $senderWallet = $stmt->fetch();
    if (!$senderWallet || $senderWallet['balance'] < $amount) {
        throw new Exception('Insufficient balance');
    }

    // Debit sender
    $stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND currency_code = ?");
    $stmt->execute([$amount, $senderId, $currency]);

    // Credit receiver
    $stmt = $pdo->prepare("INSERT INTO wallets (user_id, currency_code, balance) VALUES (?, ?, 0)
                           ON DUPLICATE KEY UPDATE balance = balance + ?");
    $stmt->execute([$receiverId, $currency, $amount]);

    // Transaction record
    $ref = 'TXN_' . time() . '_' . rand(1000, 9999);
    $description = "Transfer to $tag";
    $stmt = $pdo->prepare("INSERT INTO transactions (sender_id, receiver_id, amount, currency, type, status, description, created_at)
                           VALUES (?, ?, ?, ?, 'transfer', 'success', ?, NOW())");
    $stmt->execute([$senderId, $receiverId, $amount, $currency, $description]);

    // Email alerts
    $date = date('Y-m-d H:i:s');
    sendEmail($senderEmail, "SendNaw: You sent $currency $amount to $receiverName",
        "<h3>Transfer Sent</h3><p>You sent <strong>$currency $amount</strong> to $receiverName (Tag: $tag).</p>
         <p><strong>Reference:</strong> $ref</p><p><strong>Date:</strong> $date</p>");
    sendEmail($receiverEmail, "SendNaw: You received $currency $amount from $senderName",
        "<h3>Transfer Received</h3><p>You received <strong>$currency $amount</strong> from $senderName (Tag: $tag).</p>
         <p><strong>Reference:</strong> $ref</p><p><strong>Date:</strong> $date</p>");

    // Push notification (if FCM is set up)
    if ($fcmAvailable && function_exists('sendFCMPushNotification')) {
        $stmt = $pdo->prepare("SELECT fcm_token FROM users WHERE id = ?");
        $stmt->execute([$receiverId]);
        $receiverToken = $stmt->fetchColumn();
        if ($receiverToken) {
            sendFCMPushNotification($receiverToken, "New Transfer", "You received $currency $amount from $senderName");
        }
    }

    // --- In-app notifications (insert into `notifications` table) ---
    // 1. For receiver
    $titleRecv = "Money Received";
    $messageRecv = "You received $currency $amount from $senderName.";
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'transaction', ?, ?)");
    $stmt->execute([$receiverId, $titleRecv, $messageRecv]);

    // 2. For sender (optional – good for history)
    $titleSend = "Money Sent";
    $messageSend = "You sent $currency $amount to $receiverName.";
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'transaction', ?, ?)");
    $stmt->execute([$senderId, $titleSend, $messageSend]);

    $pdo->commit();
    echo json_encode(['success' => true, 'reference' => $ref]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>