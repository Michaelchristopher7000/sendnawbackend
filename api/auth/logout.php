<?php
session_start();
require_once '../../config/db.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
    exit();
}

// Get the token from the Authorization header
// React will send it as: Authorization: Bearer <token>
$headers = getallheaders();
$auth_header = $headers['Authorization'] ?? '';

if (empty($auth_header) || !str_starts_with($auth_header, 'Bearer ')) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "No session token provided"
    ]);
    exit();
}

// Extract the token
$token = trim(str_replace('Bearer ', '', $auth_header));

try {
    // Delete the session from the database
    $stmt = $pdo->prepare("
        DELETE FROM sessions WHERE session_token = ?
    ");
    $stmt->execute([$token]);

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Logged out successfully"
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Logout failed. Please try again"
    ]);
}
?>