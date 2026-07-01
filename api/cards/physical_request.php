<?php
require_once '../../config/db.php';
require_once '../auth.php';

$userId = authenticate($pdo);

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

$fullName = $data['fullName'] ?? '';
$phone = $data['phone'] ?? '';
$address = $data['address'] ?? '';
$city = $data['city'] ?? '';
$state = $data['state'] ?? '';
$country = $data['country'] ?? '';
$idType = $data['idType'] ?? '';
$idNumber = $data['idNumber'] ?? '';
$dob = $data['dob'] ?? '';
$design = $data['design'] ?? 'primary';

if (!$fullName || !$phone || !$address || !$city || !$state || !$idNumber || !$dob) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// In a real app, insert into a physical_card_requests table.
// For now, we simulate success.
echo json_encode(['success' => true, 'message' => 'Physical card request submitted successfully']);
?>
