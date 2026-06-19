<?php
require_once '../../config/db.php';

// Admin authentication
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

$stmt = $pdo->prepare("SELECT u.role FROM user_tokens t JOIN users u ON t.user_id = u.id WHERE t.token = ?");
$stmt->execute([$token]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$admin || !in_array($admin['role'], ['admin', 'ceo'])) {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.email, u.phone, u.kyc_status, 
           COALESCE(u.kyc_submitted_at, kd.submitted_at) AS kyc_submitted_at
    FROM users u
    LEFT JOIN kyc_documents kd ON u.id = kd.user_id
    WHERE u.kyc_status = 'pending'
    GROUP BY u.id
    ORDER BY kyc_submitted_at ASC
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as &$user) {
    $stmt = $pdo->prepare("
        SELECT document_type, 
               file_path, 
               submitted_at AS created_at
        FROM kyc_documents 
        WHERE user_id = ?
    ");
    $stmt->execute([$user['id']]);
    $user['documents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode([
    'success' => true,
    'data' => [
        'Data' => $users
    ]
]);
