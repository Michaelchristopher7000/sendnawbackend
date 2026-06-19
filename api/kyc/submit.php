<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../../config/db.php';

// --- Auth ---
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
$token = '';
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}
if (!$token) {
    echo json_encode(['success' => false, 'message' => 'No token provided']);
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

// --- Get current user info ---
$stmt = $pdo->prepare("SELECT kyc_status, kyc_tier FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}
if ($user['kyc_status'] === 'pending') {
    echo json_encode(['success' => false, 'message' => 'KYC already pending']);
    exit;
}

// --- Parse input ---
$data = json_decode(file_get_contents('php://input'), true);
$documentType = $data['document_type'] ?? '';
$fileBase64 = $data['file'] ?? '';
$gender = $data['gender'] ?? null;
$dob = $data['dob'] ?? null;
$bvn = $data['bvn'] ?? null;
$nin = $data['nin'] ?? null;
$address = $data['address'] ?? null;

if (!$documentType || !$fileBase64) {
    echo json_encode(['success' => false, 'message' => 'Missing document data']);
    exit;
}

// --- Build update fields from user-provided data ---
$updateFields = [];
if ($gender && in_array($gender, ['male', 'female', 'other'])) {
    $updateFields[] = "gender = " . $pdo->quote($gender);
}
if ($dob) {
    $updateFields[] = "dob = " . $pdo->quote($dob);
}
if ($bvn) {
    $updateFields[] = "bvn = " . $pdo->quote($bvn);
}
if ($nin) {
    $updateFields[] = "nin = " . $pdo->quote($nin);
}
if ($address) {
    $updateFields[] = "address = " . $pdo->quote($address);
}

// --- 🧠 AUTO-VERIFY NIN (if provided, with DOB) ---
if (!empty($nin) && !empty($dob)) {
    $dobParts = explode('-', $dob);
    if (count($dobParts) === 3) {
        $year = $dobParts[0];
        $month = date('M', mktime(0, 0, 0, (int)$dobParts[1], 1));
        $day = ltrim($dobParts[2], '0');

        $apiUrl = 'http://localhost:8000/get_validation/';
        $payload = json_encode([
            'nin' => $nin,
            'day' => $day,
            'month' => $month,
            'year' => $year
        ]);

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response !== false) {
            $result = json_decode($response, true);
            if (isset($result['status']) && $result['status'] === 'success') {
                $ninData = $result['data'] ?? $result;
                if (!empty($ninData['gender'])) {
                    $updateFields[] = "gender = " . $pdo->quote(strtolower($ninData['gender']));
                }
                if (!empty($ninData['date_of_birth'])) {
                    $updateFields[] = "dob = " . $pdo->quote($ninData['date_of_birth']);
                }
                if (!empty($ninData['full_name'])) {
                    $updateFields[] = "full_name = " . $pdo->quote($ninData['full_name']);
                }
                if (!empty($ninData['address'])) {
                    $updateFields[] = "address = " . $pdo->quote($ninData['address']);
                }
                if (!empty($ninData['phone'])) {
                    $updateFields[] = "phone = " . $pdo->quote($ninData['phone']);
                }
                // Ensure NIN is stored
                $updateFields[] = "nin = " . $pdo->quote($nin);
            }
        }
    }
}

// --- Upload file ---
$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
$fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $fileBase64));
if (!$fileData) {
    echo json_encode(['success' => false, 'message' => 'Invalid image data']);
    exit;
}
$fileName = 'kyc_' . $userId . '_' . time() . '_' . rand(1000, 9999) . '.jpg';
$fullPath = $uploadDir . $fileName;
if (file_put_contents($fullPath, $fileData) === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit;
}
$relativePath = 'api/kyc/uploads/' . $fileName;

// --- Insert into kyc_documents ---
$stmt = $pdo->prepare("INSERT INTO kyc_documents (user_id, document_type, file_path) VALUES (?, ?, ?)");
$stmt->execute([$userId, $documentType, $relativePath]);

// --- Update user fields (including KYC status and submission timestamp) ---
$updateFields[] = "kyc_status = 'pending'";
$updateFields[] = "kyc_submitted_at = NOW()";

$sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);

echo json_encode(['success' => true, 'message' => 'KYC submitted']);
