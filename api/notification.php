<?php
require_once __DIR__ . '/cors.php';
require_once '../../../config/db.php';
require_once '../../../middleware/auth_check.php';
// ... rest of your code

// auth_check.php should define $authenticated_user
// @var array $authenticated_user
if (!isset($authenticated_user) || empty($authenticated_user['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $authenticated_user['id'];

// Prepare statement – adjust table/column names to match your database
$stmt = $pdo->prepare("SELECT id, title, message, type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'notifications' => $notifications]);   

?>