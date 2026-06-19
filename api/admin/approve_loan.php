<?php
require_once '../../config/db.php';
require_once '../auth.php';

$userId = authenticate($pdo);
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userRole = $stmt->fetchColumn();
if (!in_array($userRole, ['admin','ceo'])) {
    echo json_encode(['success'=>false,'message'=>'Admin access required']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$loanId = intval($data['loan_id'] ?? 0);
$action = $data['action'] ?? '';
$rejectReason = $data['reason'] ?? '';

if (!$loanId) {
    echo json_encode(['success'=>false,'message'=>'Loan ID required']);
    exit;
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("SELECT user_id, amount, status FROM loans WHERE id = ? FOR UPDATE");
    $stmt->execute([$loanId]);
    $loan = $stmt->fetch();
    if (!$loan || $loan['status'] != 'pending') {
        throw new Exception('Loan not found or already processed');
    }
    if ($action == 'approve') {
        // Disburse to wallet
        $stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency_code = 'NGN'");
        $stmt->execute([$loan['amount'], $loan['user_id']]);
        // Update loan status to disbursed
        $stmt = $pdo->prepare("UPDATE loans SET status = 'disbursed', approved_by = ?, approved_at = NOW(), disbursed_at = NOW() WHERE id = ?");
        $stmt->execute([$userId, $loanId]);
        // Transaction log for disbursement
        $ref = 'LOAN_DISB_' . time();
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, description, reference, status) VALUES (?, 'loan_disbursement', ?, 'NGN', ?, ?, 'success')");
        $stmt->execute([$loan['user_id'], $loan['amount'], "Loan disbursement #$loanId", $ref]);
        $message = "Loan approved and disbursed";
    } else {
        $stmt = $pdo->prepare("UPDATE loans SET status = 'rejected', rejected_reason = ? WHERE id = ?");
        $stmt->execute([$rejectReason, $loanId]);
        $message = "Loan rejected";
    }
    $pdo->commit();
    echo json_encode(['success'=>true, 'message'=>$message]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
?>