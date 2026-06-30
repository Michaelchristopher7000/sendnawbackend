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
$password = $data['password'] ?? '';

if (strlen($pin) !== 4 || !is_numeric($pin)) {
    echo json_encode(['success' => false, 'message' => 'PIN must be exactly 4 digits']);
    exit;
}

// Verify password
$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Incorrect password']);
    exit;
}

$hashedPin = password_hash($pin, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET transaction_pin = ? WHERE id = ?");
if ($stmt->execute([$hashedPin, $userId])) {
    echo json_encode(['success' => true, 'message' => 'PIN updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update PIN']);
}
?>
