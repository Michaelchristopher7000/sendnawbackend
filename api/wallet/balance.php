<?php
header("Content-Type: application/json");

require_once '../../../config/db.php';
require_once '../../../middleware/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

try {
    // Ensure $authenticated_user is set and has an 'id' key
    if (!isset($authenticated_user['id'])) {
        // Try alternative key name if needed
        $userId = $authenticated_user['user_id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Invalid authentication"]);
            exit();
        }
    } else {
        $userId = $authenticated_user['id'];
    }

    // Get wallet balance for authenticated user
    $stmt = $pdo->prepare("
    SELECT balance, usd_balance, eur_balance, gbp_balance, btc_balance,
           account_number, bank_name, sendnaw_tag, kyc_status
    FROM wallets WHERE user_id = ?
");
    $stmt->execute([$userId]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => [
            "ngn_balance" => $wallet['balance'],
            "usd_balance" => $wallet['usd_balance'],
            "eur_balance" => $wallet['eur_balance'],
            "gbp_balance" => $wallet['gbp_balance'],
            "btc_balance" => $wallet['btc_balance'],
            "account_number" => $wallet['account_number'],
            "bank_name" => $wallet['bank_name'],
            "sendnaw_tag" => $wallet['sendnaw_tag'],
            "kyc_status" => $wallet['kyc_status']
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch balance"
    ]);
}
