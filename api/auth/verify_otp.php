<?php
require_once '../../config/db.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

$data     = json_decode(file_get_contents("php://input"), true);
$email    = !empty($data['email']) ? trim($data['email']) : null;
$otp_code = !empty($data['otp'])   ? trim($data['otp'])   : null;

if (!$email || !$otp_code) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Email address and OTP code are required"
    ]);
    exit();
}

try {
    // Find valid OTP record
    $stmt = $pdo->prepare("
        SELECT * FROM otp_codes
        WHERE identifier = ?
        AND otp_code = ?
        AND expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([$email, $otp_code]);
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
    $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE identifier = ?");
    $stmt->execute([$email]);

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Email verified successfully"
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Verification failed. Please try again"
    ]);
}
?>