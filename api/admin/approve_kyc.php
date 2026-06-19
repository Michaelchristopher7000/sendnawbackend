<?php
require_once '../../config/db.php';

// Admin auth
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
$userId = $data['kyc_id'] ?? 0;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

$pdo->beginTransaction();
try {
    // Find pending KYC document
    $stmt = $pdo->prepare("SELECT id FROM kyc_documents WHERE user_id = ? AND status = 'pending' LIMIT 1");
    $stmt->execute([$userId]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$doc) {
        throw new Exception('No pending KYC document found');
    }
    $kycId = $doc['id'];

    // Find pending upgrade request
    $stmt = $pdo->prepare("
        SELECT requested_tier 
        FROM kyc_upgrade_requests 
        WHERE user_id = ? AND status = 'pending' 
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([$userId]);
    $upgrade = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($upgrade) {
        $newTier = (int)$upgrade['requested_tier'];
    } else {
        // Fallback: increment current tier
        $stmt = $pdo->prepare("SELECT kyc_tier FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $current = (int)$stmt->fetchColumn();
        $newTier = min(3, $current + 1);
    }

    // Update user: set kyc_status = 'verified', kyc_tier, reviewed_at
    $stmt = $pdo->prepare("
        UPDATE users 
        SET kyc_status = 'verified', 
            kyc_tier = ?,
            kyc_reviewed_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$newTier, $userId]);

    // Update kyc_documents
    $stmt = $pdo->prepare("UPDATE kyc_documents SET status = 'approved', reviewed_at = NOW() WHERE id = ?");
    $stmt->execute([$kycId]);

    // Update upgrade request status
    if ($upgrade) {
        $stmt = $pdo->prepare("UPDATE kyc_upgrade_requests SET status = 'approved', processed_at = NOW() WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$userId]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "KYC approved – Tier $newTier"]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
