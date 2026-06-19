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

$uploadDir = __DIR__ . '/../../uploads/avatars/';
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

$ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
$fileName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
$targetPath = $uploadDir . $fileName;

if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit;
}

$avatarUrl = 'https://sendnawtechnologies.infinityfree.io/uploads/avatars/' . $fileName;
$stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
$stmt->execute([$avatarUrl, $userId]);

echo json_encode(['success' => true, 'avatar_url' => $avatarUrl, 'message' => 'Avatar updated']);
