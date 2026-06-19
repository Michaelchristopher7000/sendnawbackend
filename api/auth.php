<?php
header("Content-Type: application/json");
// Handle preflight OPTIONS request
function authenticate($pdo) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    $token = '';
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) $token = $matches[1];
    if (!$token) {
        echo json_encode(['success'=>false,'message'=>'Unauthorized']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success'=>false,'message'=>'Invalid token']);
        exit;
    }
    return $row['user_id'];
}
?>