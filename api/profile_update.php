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
$input = json_decode(file_get_contents('php://input'), true);

$full_name = trim($input['full_name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$address = trim($input['address'] ?? '');
$dob = $input['dob'] ?? null;

if (empty($full_name) || empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Name and email are required']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, dob = ? WHERE id = ?");
    $stmt->execute([$full_name, $email, $phone, $address, $dob, $user_id]);

    $stmt = $pdo->prepare("SELECT id, full_name, email, phone, address, dob, sendnaw_tag, role, avatar_url FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'message' => 'Profile updated', 'data' => $user]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
