<?php
require_once '../../config/db.php';

// --- User authentication ---
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

$data = json_decode(file_get_contents('php://input'), true);
$nin = trim($data['nin'] ?? '');
$dob = trim($data['dob'] ?? ''); // Expects YYYY-MM-DD

if (!$nin) {
    echo json_encode(['success' => false, 'message' => 'NIN is required']);
    exit;
}

if (!$dob) {
    echo json_encode(['success' => false, 'message' => 'Date of Birth is required for verification']);
    exit;
}

// --- Extract day, month, year from DOB ---
$dobParts = explode('-', $dob);
if (count($dobParts) !== 3) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD']);
    exit;
}
$year = $dobParts[0];
$month = date('M', mktime(0, 0, 0, (int)$dobParts[1], 1));
$day = ltrim($dobParts[2], '0'); // Remove leading zero

// --- Call NINverify using POST to /get_validation/ ---
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

if ($httpCode !== 200 || $response === false) {
    echo json_encode(['success' => false, 'message' => 'NIN verification service error']);
    exit;
}

$result = json_decode($response, true);

if (isset($result['status']) && $result['status'] !== 'success') {
    echo json_encode(['success' => false, 'message' => $result['message'] ?? 'NIN verification failed']);
    exit;
}

$ninData = $result['data'] ?? $result;

// --- Update user profile with fetched data ---
$updateFields = [];
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
$updateFields[] = "nin = " . $pdo->quote($nin);

if (empty($updateFields)) {
    echo json_encode(['success' => false, 'message' => 'No data to update']);
    exit;
}

$sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);

// Return updated user data
$stmt = $pdo->prepare("SELECT gender, dob, full_name, address, phone, nin FROM users WHERE id = ?");
$stmt->execute([$userId]);
$updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'message' => 'NIN verified and profile updated',
    'data' => $updatedUser
]);
