<?php
require_once '../../config/db.php';
require_once '../auth.php';

function authenticateAdmin($pdo) {
    $userId = authenticate($pdo);
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    // FIXED: Check if user doesn't exist OR is not admin
    if (!$user || !$user['is_admin']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
    return $userId;
}

$adminId = authenticateAdmin($pdo);
$data = json_decode(file_get_contents('php://input'), true);

$requestId = intval($data['request_id'] ?? 0);
$action = $data['action'] ?? '';
$penaltyPercent = floatval($data['penalty_percent'] ?? 10);

if (!$requestId || !in_array($action, ['approve', 'deny'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID or action']);
    exit;
}

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("
        SELECT lr.*, us.user_id, us.amount, us.plan_id, sp.name AS plan_name
        FROM savings_liquidation_requests lr
        JOIN user_savings us ON lr.saving_id = us.id
        JOIN savings_plans sp ON us.plan_id = sp.id
        WHERE lr.id = ? AND lr.status = 'pending'
        FOR UPDATE
    ");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();

    if (!$request) {
        throw new Exception('Liquidation request not found or already processed');
    }

    if ($action === 'deny') {
        $stmt = $pdo->prepare("
            UPDATE savings_liquidation_requests 
            SET status = 'denied', processed_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$requestId]);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Liquidation request denied.']);
        exit;
    }

    // Approve: calculate penalty and refund
    $penaltyAmount = $request['amount'] * ($penaltyPercent / 100);
    $refundAmount = $request['amount'] - $penaltyAmount;

    $stmt = $pdo->prepare("
        UPDATE wallets 
        SET balance = balance + ? 
        WHERE user_id = ? AND currency_code = 'NGN'
    ");
    $stmt->execute([$refundAmount, $request['user_id']]);

    $stmt = $pdo->prepare("
        UPDATE user_savings 
        SET status = 'liquidated' 
        WHERE id = ?
    ");
    $stmt->execute([$request['saving_id']]);

    $stmt = $pdo->prepare("
        UPDATE savings_liquidation_requests 
        SET status = 'approved', processed_at = NOW(), penalty_fee = ? 
        WHERE id = ?
    ");
    $stmt->execute([$penaltyAmount, $requestId]);

    $ref = 'LIQUIDATE_' . time() . '_' . rand(1000, 9999);
    $stmt = $pdo->prepare("
        INSERT INTO transactions (user_id, type, amount, currency, description, reference, status) 
        VALUES (?, 'savings_liquidation', ?, 'NGN', ?, ?, 'success')
    ");
    $stmt->execute([
        $request['user_id'],
        $refundAmount,
        "Early liquidation of '{$request['plan_name']}' (penalty {$penaltyPercent}%)",
        $ref
    ]);

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'message' => "Liquidation approved. Refunded: " . number_format($refundAmount, 2)
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>