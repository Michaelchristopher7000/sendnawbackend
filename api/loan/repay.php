<?php
require_once '../../config/db.php';
require_once '../auth.php';

$userId = authenticate($pdo);

$data = json_decode(file_get_contents('php://input'), true);
$loanId = intval($data['loan_id'] ?? 0);
$amount = floatval($data['amount'] ?? 0);

if (!$loanId || $amount <= 0) {
    echo json_encode(['success'=>false,'message'=>'Invalid loan or amount']);
    exit;
}

$pdo->beginTransaction();
try {
    // Get loan details and check status
    $stmt = $pdo->prepare("SELECT * FROM loans WHERE id = ? AND user_id = ? FOR UPDATE");
    $stmt->execute([$loanId, $userId]);
    $loan = $stmt->fetch();
    if (!$loan || !in_array($loan['status'], ['active', 'disbursed'])) {
        throw new Exception('Loan not active or not found');
    }

    // Get next unpaid repayment
    $stmt = $pdo->prepare("SELECT id, amount_due, due_date FROM loan_repayments WHERE loan_id = ? AND status = 'pending' ORDER BY due_date ASC LIMIT 1 FOR UPDATE");
    $stmt->execute([$loanId]);
    $repayment = $stmt->fetch();
    if (!$repayment) {
        throw new Exception('All payments already made or loan is fully repaid');
    }
    if ($amount < $repayment['amount_due']) {
        throw new Exception("Minimum payment is {$repayment['amount_due']}");
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

    // Record repayment
    $overpaid = $amount - $repayment['amount_due'];
    $stmt = $pdo->prepare("UPDATE loan_repayments SET amount_paid = ?, paid_at = NOW(), status = 'paid' WHERE id = ?");
    $stmt->execute([$repayment['amount_due'], $repayment['id']]);

    // If this was the last repayment, set loan status to 'repaid'
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM loan_repayments WHERE loan_id = ? AND status != 'paid'");
    $stmt->execute([$loanId]);
    $remaining = $stmt->fetchColumn();
    if ($remaining == 0) {
        $stmt = $pdo->prepare("UPDATE loans SET status = 'repaid' WHERE id = ?");
        $stmt->execute([$loanId]);
    }

    // Transaction log
    $ref = 'LOAN_REPAY_' . time() . '_' . rand(1000,9999);
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, description, reference, status) VALUES (?, 'loan_repayment', ?, 'NGN', ?, ?, 'success')");
    $stmt->execute([$userId, -$amount, "Loan repayment for loan #$loanId", $ref]);

    $pdo->commit();
    echo json_encode(['success'=>true, 'message'=>"Payment of ₦{$repayment['amount_due']} recorded. Overpayment: ₦{$overpaid}"]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
?>