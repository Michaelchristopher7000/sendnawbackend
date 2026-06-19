<?php
require_once '../../config/db.php';

// Admin auth (same as above)
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
$admin = $stmt->fetch();
if (!$admin || !in_array($admin['role'], ['admin', 'ceo'])) {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$requestId = intval($data['request_id'] ?? 0);
$action = $data['action'] ?? ''; // 'approve' or 'reject'

if (!$requestId || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$pdo->beginTransaction();
try {
    // Get request details
    $stmt = $pdo->prepare("SELECT user_id, requested_tier FROM kyc_upgrade_requests WHERE id = ? AND status = 'pending' FOR UPDATE");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    if (!$request) throw new Exception('Request not found or already processed');

    if ($action === 'approve') {
        // Update user's tier
        $tier = $request['requested_tier'];
        $stmt = $pdo->prepare("UPDATE users SET kyc_tier = ?, tier{$tier}_verified_at = NOW() WHERE id = ?");
        $stmt->execute([$tier, $request['user_id']]);
        $status = 'approved';
    } else {
        $status = 'rejected';
    }

    // Update request status
    $stmt = $pdo->prepare("UPDATE kyc_upgrade_requests SET status = ?, processed_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $requestId]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "Request {$action}d"]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
