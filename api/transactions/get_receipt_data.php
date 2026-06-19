<?php
require_once '../../config/db.php';

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
$token = '';
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}
if (!$token && isset($_GET['token'])) {
    $token = $_GET['token'];
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

$txnId = isset($_GET['txn_id']) ? intval($_GET['txn_id']) : 0;
if (!$txnId) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
    exit;
}

$sql = "SELECT t.*, 
        s.full_name as sender_name, 
        r.full_name as receiver_name,
        s.avatar_url as sender_avatar,
        r.avatar_url as receiver_avatar,
        s.account_number as sender_account,
        r.account_number as receiver_account
        FROM transactions t 
        LEFT JOIN users s ON t.sender_id = s.id 
        LEFT JOIN users r ON t.receiver_id = r.id 
        WHERE t.id = ? AND (t.sender_id = ? OR t.receiver_id = ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$txnId, $userId, $userId]);
$tx = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tx) {
    echo json_encode(['success' => false, 'message' => 'Transaction not found']);
    exit;
}

echo json_encode(['success' => true, 'transaction' => $tx]);
