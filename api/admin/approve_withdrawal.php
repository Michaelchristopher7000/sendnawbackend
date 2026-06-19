<?php
require_once '../../config/db.php';

// Authenticate admin
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
    // Get withdrawal details
    $stmt = $pdo->prepare("SELECT user_id, amount, currency, status FROM withdrawals WHERE id = ? FOR UPDATE");
    $stmt->execute([$withdrawalId]);
    $withdrawal = $stmt->fetch();
    if (!$withdrawal || $withdrawal['status'] !== 'pending') {
        throw new Exception('Withdrawal not found or already processed');
    }

    // Update withdrawal status
    $stmt = $pdo->prepare("UPDATE withdrawals SET status = 'completed', processed_at = NOW() WHERE id = ?");
    $stmt->execute([$withdrawalId]);

    // Optionally update transaction record (if you linked it)
    // You may have a transaction with reference linking to this withdrawal

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Withdrawal approved']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
