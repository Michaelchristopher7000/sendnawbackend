<?php
require_once '../../config/db.php';

// Tell PDO to throw exceptions instead of silent warnings
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- Authentication ---
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

// Verify admin/CEO role
try {
    $stmt = $pdo->prepare("SELECT u.role FROM user_tokens t JOIN users u ON t.user_id = u.id WHERE t.token = ?");
    $stmt->execute([$token]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || !in_array($admin['role'], ['admin', 'ceo'])) {
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Auth error: ' . $e->getMessage()]);
    exit;
}

// --- Main Query (NO GROUP BY - fetches all pending documents) ---
try {
    $stmt = $pdo->query("
        SELECT 
            kd.id,
            kd.user_id,
            kd.document_type,
            kd.document_number,
            kd.full_name,
            kd.date_of_birth,
            kd.status,
            kd.file_path,
            kd.submitted_at,
            u.email,
            u.phone,
            u.kyc_tier,
            u.kyc_status
        FROM kyc_documents kd
        JOIN users u ON kd.user_id = u.id
        WHERE kd.status = 'pending'
        ORDER BY kd.submitted_at ASC
    ");

    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build full image URL & format date
    $baseUrl = 'https://sendnawbackend.onrender.com/uploads/kyc/';
    foreach ($records as &$record) {
        // If file_path is empty, set image_url to null
        if (!empty($record['file_path'])) {
            // If it's already a full URL, leave it; otherwise, append basename
            if (filter_var($record['file_path'], FILTER_VALIDATE_URL)) {
                $record['image_url'] = $record['file_path'];
            } else {
                $record['image_url'] = $baseUrl . basename($record['file_path']);
            }
        } else {
            $record['image_url'] = null;
        }

        // Format the date for cleaner JSON output
        if (!empty($record['submitted_at'])) {
            $record['submitted_at'] = date('c', strtotime($record['submitted_at'])); // ISO 8601 format
        }
    }
    unset($record); // Break the reference

    echo json_encode([
        'success' => true,
        'data' => ['Data' => $records]
    ]);

} catch (PDOException $e) {
    // --- CRITICAL: Catch SQL errors and return JSON instead of HTML ---
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>