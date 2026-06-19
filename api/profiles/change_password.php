<?php
// Backend/api/profiles/change_password.php

header("Content-Type: application/json");
require_once "../../config/db.php";

$headers = getallheaders();
$authHeader = $headers["Authorization"] ?? "";

if (!preg_match('/Bearer\s(\S+)/', $authHeader, $m)) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

$stmt = $pdo->prepare("SELECT id, password FROM users WHERE session_token = ? AND token_expires_at > NOW()");
$stmt->execute([$m[1]]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => "error", "message" => "Invalid session"]);
    exit();
}

$body = json_decode(file_get_contents("php://input"), true);
$current = $body["current_password"] ?? "";
$new     = $body["new_password"] ?? "";

if (!$current || !$new) {
    echo json_encode(["status" => "error", "message" => "Both fields are required"]);
    exit();
}

if (strlen($new) < 8) {
    echo json_encode(["status" => "error", "message" => "New password must be at least 8 characters"]);
    exit();
}

// Verify current password against stored hash
if (!password_verify($current, $user["password"])) {
    echo json_encode(["status" => "error", "message" => "Current password is incorrect"]);
    exit();
}

// Hash the new password
$hashed = password_hash($new, PASSWORD_BCRYPT, ["cost" => 12]);

$update = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
$success = $update->execute([$hashed, $user["id"]]);

if ($success) {
    echo json_encode(["status" => "success", "message" => "Password updated successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error"]);
}