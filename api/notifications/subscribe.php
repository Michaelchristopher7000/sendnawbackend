<?php
// Same headers and auth
$data = json_decode(file_get_contents('php://input'), true);
$endpoint = $data['endpoint'] ?? '';
$publicKey = $data['keys']['p256dh'] ?? '';
$authToken = $data['keys']['auth'] ?? '';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userId = $_SESSION['user_id'] ?? null;

if (!$endpoint || !$publicKey || !$authToken || !$userId) {
    exit(json_encode(['success'=>false,'message'=>'Missing subscription data']));
}

$stmt = $pdo->prepare("INSERT INTO push_subscriptions (user_id, endpoint, public_key, auth_token) VALUES (?, ?, ?, ?)
                       ON DUPLICATE KEY UPDATE endpoint = VALUES(endpoint), public_key = VALUES(public_key), auth_token = VALUES(auth_token)");
$stmt->execute([$userId, $endpoint, $publicKey, $authToken]);

echo json_encode(['success'=>true]);
?>