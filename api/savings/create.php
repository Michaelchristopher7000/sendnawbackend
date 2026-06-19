<?php
require_once '../../config/db.php';
require_once '../auth.php';

$userId = authenticate($pdo);
$data = json_decode(file_get_contents('php://input'), true);
$planId = intval($data['plan_id'] ?? 0);
$amount = floatval($data['amount'] ?? 0);
$customDuration = isset($data['duration_days']) ? intval($data['duration_days']) : null;

if (!$planId || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$pdo->beginTransaction();
try {
    // Fetch plan with lock
    $stmt = $pdo->prepare("SELECT * FROM savings_plans WHERE id = ? AND is_active = 1 FOR UPDATE");
    $stmt->execute([$planId]);
    $plan = $stmt->fetch();
    if (!$plan) throw new Exception('Plan not found');
    if ($amount < $plan['min_amount']) {
        throw new Exception("Minimum amount is {$plan['min_amount']}");
    }

    // Check wallet balance
    $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency_code = 'NGN' FOR UPDATE");
    $stmt->execute([$userId]);
    $wallet = $stmt->fetch();
    if (!$wallet || $wallet['balance'] < $amount) {
        throw new Exception('Insufficient balance');
    }

    // Deduct from wallet
    $stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND currency_code = 'NGN'");
    $stmt->execute([$amount, $userId]);

    // Determine end date
    $endDate = null;
    // For flexible plans, always no end date (null)
    if ($plan['type'] !== 'flexible') {
        // Use custom duration if provided and >0, otherwise fallback to plan's duration_days
        $duration = ($customDuration && $customDuration > 0) ? $customDuration : $plan['duration_days'];
        if ($duration && $duration > 0) {
            $endDate = date('Y-m-d H:i:s', strtotime("+{$duration} days"));
        }
    }

    // Insert savings record
    $stmt = $pdo->prepare("INSERT INTO user_savings (user_id, plan_id, amount, end_date) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $planId, $amount, $endDate]);

    // Record transaction
    $ref = 'SAV_' . time() . '_' . rand(1000, 9999);
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, description, reference, status) VALUES (?, 'savings', ?, 'NGN', ?, ?, 'success')");
    $stmt->execute([$userId, -$amount, "Savings plan: {$plan['name']}", $ref]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Savings created']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>