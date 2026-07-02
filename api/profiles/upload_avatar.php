<?php
require_once '../../config/db.php';
require_once '../auth.php';

$userId = authenticate($pdo);

// Check if user has KYC Tier 2 or higher
$stmt = $pdo->prepare("SELECT kyc_tier FROM users WHERE id = ?");
$stmt->execute([$userId]);
$tier = $stmt->fetchColumn();
if ($tier < 2) {
    echo json_encode(['success' => false, 'message' => 'KYC Tier 2 required to upload custom avatar']);
    exit;
}

// Handle file upload
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$allowed = ['image/jpeg', 'image/png', 'image/jpg'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Only JPG and PNG allowed']);
    exit;
}

$imageData = file_get_contents($_FILES['avatar']['tmp_name']);
if ($imageData === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to read uploaded file']);
    exit;
}

$avatarUrl = 'data:' . $mime . ';base64,' . base64_encode($imageData);

$stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
$stmt->execute([$avatarUrl, $userId]);

echo json_encode(['success' => true, 'avatar_url' => $avatarUrl, 'message' => 'Avatar updated']);
