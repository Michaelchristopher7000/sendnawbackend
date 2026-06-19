<?php
// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(200);
    exit;
}

ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

require_once "../../config/db.php";
$user = require "../../middleware/auth_check.php"; // returns user array or exits with error

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    // Admin/CEO trying to fetch a specific user
    if (!in_array($user['role'], ['admin', 'ceo'])) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Forbidden"]);
        exit;
    }
    $stmt = $pdo->prepare("
        SELECT id, full_name, email, phone, sendnaw_tag, role, is_active, kyc_status, kyc_tier, created_at
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // Authenticated user fetching their own profile (no admin needed)
    $stmt = $pdo->prepare("
        SELECT id, full_name, email, phone, sendnaw_tag, role, is_active, kyc_status, kyc_tier, created_at
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$userData) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit;
}

echo json_encode(["status" => "success", "data" => $userData]);
