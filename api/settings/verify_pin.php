<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../settings/auth_helper.php';

function getUserIdFromToken($pdo) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    $token = '';
    if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
    }
    if (!$token) return null;

    $stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ? $row['user_id'] : null;
}

$userId = getUserIdFromToken($pdo);
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$pin = $data['pin'] ?? '';

$stmt = $pdo->prepare("SELECT transaction_pin FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !$user['transaction_pin']) {
    echo json_encode(['success' => false, 'message' => 'PIN not set up']);
    exit;
}

if (password_verify($pin, $user['transaction_pin'])) {
    echo json_encode(['success' => true, 'message' => 'PIN verified']);
} else {
    echo json_encode(['success' => false, 'message' => 'Incorrect PIN']);
}
?>
