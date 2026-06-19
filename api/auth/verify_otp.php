<?php
require_once '../../config/db.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

$data     = json_decode(file_get_contents("php://input"), true);
$phone    = !empty($data['phone']) ? trim($data['phone']) : null;
$otp_code = !empty($data['otp'])   ? trim($data['otp'])   : null;

if (!$phone || !$otp_code) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Phone number and OTP code are required"
    ]);
    exit();
}

// Add + prefix to match how it was stored in send_otp.php
if (!str_starts_with($phone, '+')) {
    $phone = '+' . $phone;
}

try {
    // Find valid OTP record
    $stmt = $pdo->prepare("
        SELECT * FROM otp_codes
        WHERE phone = ?
        AND otp_code = ?
        AND expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([$phone, $otp_code]);
    $record = $stmt->fetch();

    if (!$record) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid or expired verification code"
        ]);
        exit();
    }

    // Delete OTP after successful verification
    $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE phone = ?");
    $stmt->execute([$phone]);

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Phone number verified successfully"
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Verification failed. Please try again"
    ]);
}
?>