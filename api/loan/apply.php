<?php
require_once '../../config/db.php';
require_once '../auth.php';
$userId = authenticate($pdo);

$data = json_decode(file_get_contents('php://input'), true);
$productId = intval($data['product_id'] ?? 0);
$amount = floatval($data['amount'] ?? 0);

if (!$productId || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product or amount']);
    exit;
}

// Get product details
$stmt = $pdo->prepare("SELECT * FROM loan_products WHERE id = ? AND is_active = 1");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Loan product not found']);
    exit;
}
if ($amount < $product['min_amount'] || $amount > $product['max_amount']) {
    echo json_encode(['success' => false, 'message' => "Amount must be between {$product['min_amount']} and {$product['max_amount']}"]);
    exit;
}

// Calculate monthly installment (simple interest)
$interest = $amount * ($product['interest_rate'] / 100);
$totalDue = $amount + ($interest * $product['duration_months']);
$monthlyInstallment = $totalDue / $product['duration_months'];

// Insert loan application
$stmt = $pdo->prepare("INSERT INTO loans (user_id, product_id, amount, interest_rate, duration_months, monthly_installment, total_due, status) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
$stmt->execute([$userId, $productId, $amount, $product['interest_rate'], $product['duration_months'], $monthlyInstallment, $totalDue]);

$loanId = $pdo->lastInsertId();

// Create repayment schedule
$dueDate = date('Y-m-d', strtotime('+1 month'));
for ($i = 1; $i <= $product['duration_months']; $i++) {
    $stmt = $pdo->prepare("INSERT INTO loan_repayments (loan_id, due_date, amount_due) VALUES (?, ?, ?)");
    $stmt->execute([$loanId, $dueDate, $monthlyInstallment]);
    $dueDate = date('Y-m-d', strtotime($dueDate . ' +1 month'));
}

echo json_encode(['success' => true, 'message' => 'Loan application submitted', 'loan_id' => $loanId]);
