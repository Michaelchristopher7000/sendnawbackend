<?php
header("Content-Type: application/json");
require_once '../config/db.php';
$authenticated_user = require '../middleware/auth_check.php';

if (!$authenticated_user || !isset($authenticated_user['id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $authenticated_user['id'];

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['avatar'];
$allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
if (!in_array($file['type'], $allowed)) {
    echo json_encode(['status' => 'error', 'message' => 'Only JPG, PNG, WEBP allowed']);
    exit;
}

// Create the uploads folder if it doesn't exist
$uploadDir = __DIR__ . '/../uploads/avatars/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
$filePath = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $filePath)) {
    // ✅ Correct URL without the extra 'api' folder
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $baseUrl = $protocol . "://localhost/sendnaw/Backend/api/uploads/avatars/";
    $avatarUrl = $baseUrl . $filename;

    $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
    $stmt->execute([$avatarUrl, $user_id]);

    echo json_encode(['status' => 'success', 'avatar_url' => $avatarUrl]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save file']);
}
