<?php
header('Content-Type: application/json');
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

// Save OTP to database first
try {
    $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE identifier = ?");
    $stmt->execute([$email]);

    $stmt = $pdo->prepare("INSERT INTO otp_codes (identifier, otp_code, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$email, $otp_code, $expires_at]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to store verification code"]);
    exit();
}

// Send email (synchronous — reliable on Render)
$subject = "Your SendNaw Verification Code";
$body = "
<div style='font-family:Arial,sans-serif;max-width:480px;margin:auto;padding:32px;background:#f9f9f9;border-radius:12px;'>
  <div style='text-align:center;margin-bottom:24px;'>
    <h2 style='color:#6f42c1;margin:0;'>SendNaw Verification</h2>
  </div>
  <p style='color:#333;font-size:15px;'>Hi there,</p>
  <p style='color:#333;font-size:15px;'>Your one-time verification code is:</p>
  <div style='text-align:center;margin:28px 0;'>
    <span style='font-size:42px;font-weight:900;letter-spacing:12px;color:#6f42c1;background:#f0e6ff;padding:16px 28px;border-radius:12px;display:inline-block;'>
      {$otp_code}
    </span>
  </div>
  <p style='color:#666;font-size:13px;'>This code expires in <strong>10 minutes</strong>. Do not share it with anyone.</p>
  <hr style='border:none;border-top:1px solid #eee;margin:24px 0;'>
  <p style='color:#aaa;font-size:12px;text-align:center;'>The SendNaw Team</p>
</div>";

$sent = sendEmail($email, $subject, $body);
error_log("OTP email to $email: " . ($sent ? "SENT" : "FAILED"));

// Always respond success if OTP is in DB (user can still receive it even if email is slow)
http_response_code(200);
echo json_encode([
    "success" => true,
    "message" => "Verification code sent to " . $email,
    "email_sent" => $sent
]);