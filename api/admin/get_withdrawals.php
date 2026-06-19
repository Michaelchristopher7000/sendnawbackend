<?php
require_once '../../config/db.php';

// Verify admin role
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

$stmt = $pdo->prepare("SELECT u.id, u.role FROM user_tokens t JOIN users u ON t.user_id = u.id WHERE t.token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || !in_array($user['role'], ['admin', 'ceo'])) {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

$status = $_GET['status'] ?? 'pending'; // pending, completed, failed, all

$sql = "SELECT w.*, u.full_name, u.email, u.phone 
        FROM withdrawals w 
        JOIN users u ON w.user_id = u.id";
if ($status !== 'all') {
    $sql .= " WHERE w.status = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$status]);
} else {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}
$withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'withdrawals' => $withdrawals]);
