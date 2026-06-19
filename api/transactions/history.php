<?php
require_once '../../config/db.php';

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

$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tokenRow) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}
$userId = $tokenRow['user_id'];

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;
$type = $_GET['type'] ?? null;
$minAmount = $_GET['min_amount'] ?? null;
$maxAmount = $_GET['max_amount'] ?? null;

// Transactions where user is sender OR receiver
$sql = "SELECT * FROM transactions WHERE (sender_id = ? OR receiver_id = ?)";
$params = [$userId, $userId];

if ($startDate) {
    $sql .= " AND created_at >= ?";
    $params[] = $startDate;
}
if ($endDate) {
    $sql .= " AND created_at <= ?";
    $params[] = $endDate . ' 23:59:59';
}
if ($type && in_array($type, ['send', 'receive', 'deposit', 'withdraw', 'airtime', 'bill', 'transfer'])) {
    $sql .= " AND type = ?";
    $params[] = $type;
}
if ($minAmount !== null && is_numeric($minAmount)) {
    $sql .= " AND amount >= ?";
    $params[] = floatval($minAmount);
}
if ($maxAmount !== null && is_numeric($maxAmount)) {
    $sql .= " AND amount <= ?";
    $params[] = floatval($maxAmount);
}
$sql .= " ORDER BY created_at DESC LIMIT " . $limit;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'transactions' => $transactions]);
