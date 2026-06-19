<?php
require_once '../../config/db.php';

// Authenticate via Bearer token
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
$token = '';
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}
if (!$token && isset($_GET['token'])) {
    $token = $_GET['token']; // fallback for direct link
}
if (!$token) {
    die('Unauthorized');
}

$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tokenRow) {
    die('Invalid token');
}
$userId = $tokenRow['user_id'];

// Get transaction ID from query string
$txnId = isset($_GET['txn_id']) ? intval($_GET['txn_id']) : 0;
if (!$txnId) {
    die('Transaction ID required');
}

// Fetch transaction where user is either sender or receiver (no user_id column)
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND (sender_id = ? OR receiver_id = ?)");
$stmt->execute([$txnId, $userId, $userId]);
$tx = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tx) {
    die('Transaction not found');
}

// Generate PDF receipt
require_once '../../vendor/dompdf/autoload.inc.php';

use Dompdf\Dompdf;

$html = "
<!DOCTYPE html>
<html>
<head><title>SendNaw Receipt</title></head>
<body style='font-family: sans-serif;'>
    <h2>SendNaw Transaction Receipt</h2>
    <p><strong>Transaction ID:</strong> {$tx['id']}</p>
    <p><strong>Date:</strong> {$tx['created_at']}</p>
    <p><strong>Type:</strong> {$tx['type']}</p>
    <p><strong>Amount:</strong> {$tx['amount']} {$tx['currency']}</p>
    <p><strong>Description:</strong> {$tx['description']}</p>
    <p><strong>Status:</strong> {$tx['status']}</p>
</body>
</html>";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("receipt_{$txnId}.pdf");
