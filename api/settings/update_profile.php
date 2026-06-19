<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../config/db.php';  // absolute path from this file

// Get token
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
$token = '';
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}
if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tokenRow) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}
$userId = $tokenRow['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
// Only allow email and phone to be updated (full_name is read-only)
$email = trim($data['email'] ?? '');
$phone = trim($data['phone'] ?? '');

if (!$email || !$phone) {
    echo json_encode(['success' => false, 'message' => 'Email and phone are required']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email']);
    exit;
}
// Basic phone validation (at least 10 digits)
if (strlen(preg_replace('/\D/', '', $phone)) < 10) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
    exit;
}

// Check if email/phone already used by another user
$stmt = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR phone = ?) AND id != ?");
$stmt->execute([$email, $phone, $userId]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email or phone already in use']);
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET email = ?, phone = ? WHERE id = ?");
$stmt->execute([$email, $phone, $userId]);

echo json_encode(['success' => true, 'message' => 'Profile updated']);
