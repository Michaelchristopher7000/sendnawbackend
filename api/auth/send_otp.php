<?php
require_once '../../config/db.php';
require_once '../../utils/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

$data  = json_decode(file_get_contents("php://input"), true);
$email = !empty($data['email']) ? trim($data['email']) : null;

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Valid email address is required"]);
    exit();
}

// Generate 6 digit OTP
$otp_code   = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
$expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Send OTP via Email
$subject = "Your SendNaw Verification Code";
$body = "<h3>Verification Code</h3>
         <p>Your SendNaw verification code is: <strong>" . $otp_code . "</strong></p>
         <p>It expires in 10 minutes. Do not share this code with anyone.</p>";

$email_sent = sendEmail($email, $subject, $body);

// Log result for debugging
error_log("Email OTP Result for $email: " . ($email_sent ? "Sent" : "Failed"));

// Store OTP in database
try {
    // Delete any existing OTP for this email
    $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE identifier = ?");
    $stmt->execute([$email]);

    // Insert new OTP
    $stmt = $pdo->prepare("
        INSERT INTO otp_codes (identifier, otp_code, expires_at)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$email, $otp_code, $expires_at]);
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Verification code sent to " . $email,
        "status" => $email_sent ? "sent" : "failed"
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to store verification code"
    ]);
}