<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");


require_once "../../config/db.php";
// auth_check.php returns user array on success, or exits with error
// Capture returned user array into $user
$user = require_once "../../middleware/auth_check.php";

// Allow only admin or ceo
if (!in_array($user['role'], ['admin', 'ceo'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Forbidden – insufficient role"]);
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT id, full_name, email, phone, sendnaw_tag, role, is_active, kyc_status, created_at
        FROM users
        ORDER BY id DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => ["Users" => $users]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>