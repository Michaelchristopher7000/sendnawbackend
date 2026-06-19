<?php
header("Content-Type: application/json");
require_once '../../config/db.php';
$authenticated_user = require_once '../../middleware/auth_check.php';

if (!$authenticated_user || !isset($authenticated_user['id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $authenticated_user['id'];
$input = json_decode(file_get_contents('php://input'), true);

$email = $input['email'] ? 1 : 0;
$push = $input['push'] ? 1 : 0;
$sms = $input['sms'] ? 1 : 0;

$stmt = $pdo->prepare("UPDATE users SET notify_email = ?, notify_push = ?, notify_sms = ? WHERE id = ?");
$stmt->execute([$email, $push, $sms, $user_id]);

echo json_encode(['status' => 'success', 'message' => 'Preferences updated']);
