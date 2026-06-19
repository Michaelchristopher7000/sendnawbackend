<?php
require_once '../../config/db.php';
require_once '../../config/africastalking.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

$data  = json_decode(file_get_contents("php://input"), true);
$phone = !empty($data['phone']) ? trim($data['phone']) : null;

if (!$phone) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Phone number is required"]);
    exit();
}

// Format phone for Africa's Talking
// Must start with + e.g +2348012345678
if (!str_starts_with($phone, '+')) {
    $phone = '+' . $phone;
}

// Generate 6 digit OTP
$otp_code   = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
$expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Send OTP via Africa's Talking
$message = "Your SendNaw verification code is: " . $otp_code .
    ". It expires in 10 minutes. Do not share this code with anyone.";

$sms_result = sendSMS($phone, $message);



// Log result for debugging
error_log("SMS Result: " . json_encode($sms_result));
// Continue even if SMS has issues
// Store OTP in database regardless

// Store OTP in database
try {
    // Delete any existing OTP for this phone
    $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE phone = ?");
    $stmt->execute([$phone]);

    // Insert new OTP
    $stmt = $pdo->prepare("
        INSERT INTO otp_codes (phone, otp_code, expires_at)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$phone, $otp_code, $expires_at]);
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Verification code sent to " . $phone,
        "sms_status" => $sms_result['success'] ? "sent" : "failed"
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to store verification code"
    ]);
}