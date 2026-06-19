<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

// MUST come before method check
require_once '../../config/db.php';
require_once '../../config/africastalking.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}


$data  = json_decode(file_get_contents("php://input"), true);
$input = !empty($data['input']) ? trim($data['input']) : null;

if (!$input) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Please provide your phone number or email"
    ]);
    exit();
}

try {
    // Check if input is email or phone
    $is_email = filter_var($input, FILTER_VALIDATE_EMAIL);

    if ($is_email) {
        $stmt = $pdo->prepare("
            SELECT id, full_name, phone, email 
            FROM users WHERE email = ? AND is_active = 1
        ");
        $stmt->execute([$input]);
    } else {
        $phone = preg_replace('/\D/', '', $input);
        $stmt  = $pdo->prepare("
            SELECT id, full_name, phone, email 
            FROM users WHERE phone = ? AND is_active = 1
        ");
        $stmt->execute([$phone]);
    }

    $user = $stmt->fetch();

    // Always return success even if user not found
    if (!$user) {
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "If this account exists, a reset code has been sent"
        ]);
        exit();
    }

    // Generate reset code
    $reset_code    = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at    = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    $reset_message = "Your SendNaw password reset code is: " . $reset_code .
        ". It expires in 15 minutes. Do not share this code.";

    // Delete existing reset codes
    $stmt = $pdo->prepare("
        DELETE FROM otp_codes WHERE phone = ?
    ");
    $stmt->execute([$user['phone']]);

    // Store reset code
    $stmt = $pdo->prepare("
        INSERT INTO otp_codes (phone, otp_code, expires_at)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$user['phone'], $reset_code, $expires_at]);

    // Send via SMS
    $phone_with_prefix = '+' . preg_replace('/\D/', '', $user['phone']);
    $sms_result = sendSMS($phone_with_prefix, $reset_message);

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "If this account exists, a reset code has been sent",
        "method"  => "sms"
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Something went wrong. Please try again"
    ]);
}