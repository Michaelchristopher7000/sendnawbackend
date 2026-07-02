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

// Save uploaded file to uploads/avatars and store a path in the DB instead of storing the
// full base64 data (avoids "Data too long for column 'avatar_url'").
$uploadsDir = realpath(__DIR__ . '/../../uploads/avatars');
if ($uploadsDir === false) {
    // Try to create the directory if it doesn't exist
    $uploadsDir = __DIR__ . '/../../uploads/avatars';
    if (!is_dir($uploadsDir)) {
        if (!mkdir($uploadsDir, 0755, true)) {
            echo json_encode(['success' => false, 'message' => 'Failed to create uploads directory']);
            exit;
        }
    }
}

$ext = '';
switch ($mime) {
    case 'image/png':
        $ext = 'png';
        break;
    case 'image/jpeg':
    case 'image/jpg':
        $ext = 'jpg';
        break;
    default:
        $ext = 'img';
}

$filename = sprintf('avatar_%s_%s.%s', $userId, time(), $ext);
$targetPath = rtrim($uploadsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
    exit;
}

// Store a web-accessible path (relative to the server root). The frontend can
// resolve it using the API base URL. Example: /uploads/avatars/avatar_123_1600000000.jpg
$avatarUrl = '/uploads/avatars/' . $filename;

$stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
if (!$stmt->execute([$avatarUrl, $userId])) {
    // Attempt to remove the saved file on DB failure
    if (file_exists($targetPath)) {
        @unlink($targetPath);
    }
    echo json_encode(['success' => false, 'message' => 'Failed to update avatar in database']);
    exit;
}

echo json_encode(['success' => true, 'avatar_url' => $avatarUrl, 'message' => 'Avatar updated']);
