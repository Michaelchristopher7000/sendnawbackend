<?php
require_once '../../config/db.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

$data        = json_decode(file_get_contents("php://input"), true);
$phone       = !empty($data['phone'])    ? trim(preg_replace('/\D/', '', $data['phone'])) : null;
$reset_code  = !empty($data['code'])     ? trim($data['code'])     : null;
$new_password = !empty($data['password']) ? $data['password']       : null;

if (!$phone || !$reset_code || !$new_password) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Phone number, reset code, and new password are required"
    ]);
    exit();
}

// Validate new password
if (strlen($new_password) < 6) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Password must be at least 6 characters"
    ]);
    exit();
}

if (!preg_match('/[A-Z]/', $new_password)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Password must contain at least one uppercase letter"
    ]);
    exit();
}

if (!preg_match('/[0-9]/', $new_password)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Password must contain at least one number"
    ]);
    exit();
}

try {
    // Verify reset code
    $stmt = $pdo->prepare("
        SELECT * FROM otp_codes
        WHERE phone = ?
        AND otp_code = ?
        AND expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([$phone, $reset_code]);
    $record = $stmt->fetch();

    if (!$record) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid or expired reset code"
        ]);
        exit();
    }

    // Hash new password
    $password_hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);

    // Update password
    $stmt = $pdo->prepare("
        UPDATE users SET password_hash = ? WHERE phone = ?
    ");
    $stmt->execute([$password_hash, $phone]);

    // Delete used reset code
    $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE phone = ?");
    $stmt->execute([$phone]);

    // Delete all existing sessions for security
    $stmt = $pdo->prepare("
        DELETE FROM sessions WHERE user_id = (
            SELECT id FROM users WHERE phone = ?
        )
    ");
    $stmt->execute([$phone]);

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Password reset successfully. Please sign in with your new password"
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Password reset failed. Please try again"
    ]);
}
?>