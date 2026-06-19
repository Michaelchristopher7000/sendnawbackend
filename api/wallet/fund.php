<?php
require_once '../../../config/db.php';
require_once '../../../middleware/auth_check.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

$data   = json_decode(file_get_contents("php://input"), true);
$amount = !empty($data['amount']) ? (float)$data['amount'] : 0;

if ($amount <= 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid amount"
    ]);
    exit();
}

if ($amount > 1000000) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Maximum funding amount is ₦1,000,000 per transaction"
    ]);
    exit();
}

try {
    // Update wallet balance
    $stmt = $pdo->prepare("
        UPDATE wallets 
        SET balance = balance + ? 
        WHERE user_id = ?
    ");
    $stmt->execute([$amount, $authenticated_user['id']]);

    // Get new balance
    $stmt = $pdo->prepare("
        SELECT balance FROM wallets WHERE user_id = ?
    ");
    $stmt->execute([$authenticated_user['id']]);
    $wallet = $stmt->fetch();

    // Record deposit in transactions database
    $stmt = $pdo->prepare("
        INSERT INTO deposits (user_id, amount, method, status)
        VALUES (?, ?, 'simulation', 'success')
    ");
    $stmt->execute([$authenticated_user['id'], $amount]);

    // Record in transactions table
    $stmt = $pdo->prepare("
        INSERT INTO transactions (sender_id, receiver_id, amount, type, status, description)
        VALUES (?, ?, ?, 'deposit', 'success', 'Wallet funding')
    ");
    $stmt->execute([
        $authenticated_user['id'],
        $authenticated_user['id'],
        $amount
    ]);

    http_response_code(200);
    echo json_encode([
        "success"     => true,
        "message"     => "Wallet funded successfully",
        "new_balance" => number_format((float)$wallet['balance'], 2)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to fund wallet. Please try again"
    ]);
}
?>