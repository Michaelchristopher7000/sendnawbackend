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

$imageData = file_get_contents($file['tmp_name']);
if ($imageData === false) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to read uploaded file']);
    exit;
}

$avatarUrl = 'data:' . $file['type'] . ';base64,' . base64_encode($imageData);

$stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
$stmt->execute([$avatarUrl, $user_id]);

echo json_encode(['status' => 'success', 'avatar_url' => $avatarUrl]);
