<?php
require_once '../../../config/db.php';
require_once '../../../middleware/auth_check.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

$data        = json_decode(file_get_contents("php://input"), true);
$amount      = !empty($data['amount'])      ? (float)$data['amount']          : 0;
$receiver_tag = !empty($data['sendnaw_tag']) ? trim($data['sendnaw_tag'])      : null;
$note        = !empty($data['note'])        ? trim($data['note'])              : 'Transfer';

// Validate amount
if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Please enter a valid amount"]);
    exit();
}

if ($amount < 10) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Minimum transfer amount is ₦10"]);
    exit();
}

// Validate receiver tag
if (!$receiver_tag) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Please enter a SendNaw tag"]);
    exit();
}

// Add @ if not present
if (!str_starts_with($receiver_tag, '@')) {
    $receiver_tag = '@' . $receiver_tag;
}

try {
    // Find receiver
    $stmt = $pdo->prepare("
        SELECT id, full_name, sendnaw_tag 
        FROM users 
        WHERE sendnaw_tag = ? AND is_active = 1
    ");
    $stmt->execute([$receiver_tag]);
    $receiver = $stmt->fetch();

    if (!$receiver) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "SendNaw tag not found. Please check and try again"
        ]);
        exit();
    }

    // Prevent self transfer
    if ($receiver['id'] === $authenticated_user['id']) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "You cannot transfer money to yourself"
        ]);
        exit();
    }

    // Check sender balance
    $stmt = $pdo->prepare("
        SELECT balance FROM wallets WHERE user_id = ?
    ");
    $stmt->execute([$authenticated_user['id']]);
    $sender_wallet = $stmt->fetch();

    if ((float)$sender_wallet['balance'] < $amount) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Insufficient balance"
        ]);
        exit();
    }

    // Deduct from sender
    $stmt = $pdo->prepare("
        UPDATE wallets SET balance = balance - ? WHERE user_id = ?
    ");
    $stmt->execute([$amount, $authenticated_user['id']]);

    // Add to receiver
    $stmt = $pdo->prepare("
        UPDATE wallets SET balance = balance + ? WHERE user_id = ?
    ");
    $stmt->execute([$amount, $receiver['id']]);

    // Record transfer in transactions database
    $stmt = $pdo->prepare("
        INSERT INTO transfers (sender_id, receiver_id, amount, sendnaw_tag, note, status)
        VALUES (?, ?, ?, ?, ?, 'success')
    ");
    $stmt->execute([
        $authenticated_user['id'],
        $receiver['id'],
        $amount,
        $receiver_tag,
        $note
    ]);

    // Record in transactions table
    $stmt = $pdo->prepare("
        INSERT INTO transactions (sender_id, receiver_id, amount, type, status, description)
        VALUES (?, ?, ?, 'transfer', 'success', ?)
    ");
    $stmt->execute([
        $authenticated_user['id'],
        $receiver['id'],
        $amount,
        'Transfer to ' . $receiver_tag
    ]);

    // Get updated sender balance
    $stmt = $pdo->prepare("
        SELECT balance FROM wallets WHERE user_id = ?
    ");
    $stmt->execute([$authenticated_user['id']]);
    $updated_wallet = $stmt->fetch();

    http_response_code(200);
    echo json_encode([
        "success"       => true,
        "message"       => "Transfer successful",
        "receiver_name" => $receiver['full_name'],
        "new_balance"   => number_format((float)$updated_wallet['balance'], 2)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Transfer failed. Please try again"
    ]);
}
?>