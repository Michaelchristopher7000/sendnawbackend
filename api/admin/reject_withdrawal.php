<?php
require_once '../../config/db.php';

// Admin authentication (same as approve)
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

$stmt = $pdo->prepare("SELECT u.id, u.role FROM user_tokens t JOIN users u ON t.user_id = u.id WHERE t.token = ?");
$stmt->execute([$token]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$admin || !in_array($admin['role'], ['admin', 'ceo'])) {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$withdrawalId = $data['withdrawal_id'] ?? 0;
if (!$withdrawalId) {
    echo json_encode(['success' => false, 'message' => 'Withdrawal ID required']);
    exit;
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("SELECT user_id, amount, currency, status FROM withdrawals WHERE id = ? FOR UPDATE");
    $stmt->execute([$withdrawalId]);
    $withdrawal = $stmt->fetch();
    if (!$withdrawal || $withdrawal['status'] !== 'pending') {
        throw new Exception('Withdrawal not found or already processed');
    }

    // Refund wallet
    $stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency_code = ?");
    $stmt->execute([$withdrawal['amount'], $withdrawal['user_id'], $withdrawal['currency']]);

    // Update withdrawal status
    $stmt = $pdo->prepare("UPDATE withdrawals SET status = 'failed', processed_at = NOW(), notes = 'Rejected by admin' WHERE id = ?");
    $stmt->execute([$withdrawalId]);

    // Optional: log refund transaction
    $stmt = $pdo->prepare("INSERT INTO transactions (sender_id, receiver_id, amount, type, status, description) VALUES (?, NULL, ?, 'refund', 'success', 'Withdrawal rejected – refund')");
    $stmt->execute([$withdrawal['user_id'], $withdrawal['amount']]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Withdrawal rejected and refunded']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
