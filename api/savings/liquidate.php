<?php
require_once '../../config/db.php';
require_once '../auth.php';

$userId = authenticate($pdo);
$data = json_decode(file_get_contents('php://input'), true);
$savingId = intval($data['saving_id'] ?? 0);

if (!$savingId) {
    echo json_encode(['success' => false, 'message' => 'Invalid saving ID']);
    exit;
}

$pdo->beginTransaction();

try {
    // 1. Fetch the saving and check ownership & type
    $stmt = $pdo->prepare("
        SELECT us.id, us.user_id, us.amount, sp.type, us.status 
        FROM user_savings us 
        JOIN savings_plans sp ON us.plan_id = sp.id 
        WHERE us.id = ? FOR UPDATE
    ");
    $stmt->execute([$savingId]);
    $saving = $stmt->fetch();

    if (!$saving) {
        throw new Exception('Savings record not found');
    }
    if ($saving['user_id'] != $userId) {
        throw new Exception('Unauthorised: this saving does not belong to you');
    }
    if ($saving['type'] !== 'locked') {
        throw new Exception('Early liquidation is only available for locked savings plans');
    }
    if ($saving['status'] !== 'active') {
        throw new Exception('This saving is already closed or liquidated');
    }

    // 2. Check if a liquidation request already exists for this saving (pending)
    $stmt = $pdo->prepare("
        SELECT id FROM savings_liquidation_requests 
        WHERE saving_id = ? AND status = 'pending'
    ");
    $stmt->execute([$savingId]);
    if ($stmt->fetch()) {
        throw new Exception('A liquidation request is already pending for this saving');
    }

    // 3. Insert the liquidation request
    $stmt = $pdo->prepare("
        INSERT INTO savings_liquidation_requests 
        (saving_id, user_id, requested_at, status) 
        VALUES (?, ?, NOW(), 'pending')
    ");
    $stmt->execute([$savingId, $userId]);

    // 4. (Optional) Notify admin – insert into a notifications table
    $adminMessage = "User ID {$userId} requested early liquidation for saving ID {$savingId} (Amount: {$saving['amount']})";
    // You can insert into `admin_notifications` if you have such a table
    // $stmt = $pdo->prepare("INSERT INTO admin_notifications (message, created_at) VALUES (?, NOW())");
    // $stmt->execute([$adminMessage]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Liquidation request has been submitted. An admin will process it within 72 hours.'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>