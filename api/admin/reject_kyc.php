<?php
require_once '../../config/db.php';

// Admin authentication (same as above)
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

$data = json_decode(file_get_contents('php://input'), true);
$userId = $data['kyc_id'] ?? 0;   // C# sends kyc_id (user ID)
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

$pdo->beginTransaction();
try {
    // Find the pending KYC document for this user
    $stmt = $pdo->prepare("SELECT id FROM kyc_documents WHERE user_id = ? AND status = 'pending' LIMIT 1");
    $stmt->execute([$userId]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$doc) {
        throw new Exception('No pending KYC record found for this user');
    }
    $kycId = $doc['id'];

    // Update user KYC status
    $stmt = $pdo->prepare("
        UPDATE users 
        SET kyc_status = 'unverified', 
            kyc_reviewed_at = NOW(),
            id_verified = 0,
            address_verified = 0,
            selfie_verified = 0,
            kyc_tier = 0
        WHERE id = ?
    ");
    $stmt->execute([$userId]);

    // Update kyc_documents status
    $stmt = $pdo->prepare("
        UPDATE kyc_documents 
        SET status = 'rejected', 
            reviewed_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$kycId]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'KYC rejected successfully']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
