<?php
// Backend/api/profiles/upload_kyc.php
// Accepts a document file + type, stores it, and creates/updates a kyc_documents record.
// Admin must then review and approve/reject via admin/kyc_review.php.

header("Content-Type: application/json");
require_once "../../config/db.php";

// ── 1. Authenticate the requesting user ─────────────────────────────────────
$headers = getallheaders();
$authHeader = $headers["Authorization"] ?? "";

if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

$token = $matches[1];

$stmt = $pdo->prepare("SELECT id FROM users WHERE session_token = ? AND token_expires_at > NOW()");
if (!$stmt->execute([$token])) {
    echo json_encode(["status" => "error", "message" => "Database error during authentication"]);
    exit();
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => "error", "message" => "Invalid or expired session"]);
    exit();
}

$user_id = $user["id"];

// ── 2. Validate document type ────────────────────────────────────────────────
$allowed_types = ["id", "address", "selfie"];
$doc_type = $_POST["type"] ?? "";

if (!in_array($doc_type, $allowed_types)) {
    echo json_encode(["status" => "error", "message" => "Invalid document type"]);
    exit();
}

// ── 3. Validate uploaded file ────────────────────────────────────────────────
if (!isset($_FILES["document"]) || $_FILES["document"]["error"] !== UPLOAD_ERR_OK) {
    echo json_encode(["status" => "error", "message" => "No file uploaded or upload error"]);
    exit();
}

$file = $_FILES["document"];
$max_size = 5 * 1024 * 1024; // 5MB

if ($file["size"] > $max_size) {
    echo json_encode(["status" => "error", "message" => "File exceeds 5MB limit"]);
    exit();
}

// Allow images and PDFs
$allowed_mime = ["image/jpeg", "image/png", "image/webp", "application/pdf"];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file["tmp_name"]);
finfo_close($finfo);

if (!in_array($mime, $allowed_mime)) {
    echo json_encode(["status" => "error", "message" => "Only JPEG, PNG, WEBP, or PDF files are accepted"]);
    exit();
}

// ── 4. Save file to server ───────────────────────────────────────────────────
// Path: uploads/kyc/{user_id}/{type}_{timestamp}.ext
$ext = pathinfo($file["name"], PATHINFO_EXTENSION);
$upload_dir = __DIR__ . "/../../uploads/kyc/{$user_id}/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$filename = "{$doc_type}_" . time() . "." . strtolower($ext);
$full_path = $upload_dir . $filename;
$relative_path = "uploads/kyc/{$user_id}/{$filename}";

if (!move_uploaded_file($file["tmp_name"], $full_path)) {
    echo json_encode(["status" => "error", "message" => "Failed to save file on server"]);
    exit();
}

// ── 5. Upsert record in kyc_documents ───────────────────────────────────────
// If a record already exists for this user+type, replace it (re-upload).
$stmt = $pdo->prepare(
    "INSERT INTO kyc_documents (user_id, document_type, file_path, status, uploaded_at)\n"
    . "VALUES (?, ?, ?, 'pending', NOW())\n"
    . "ON DUPLICATE KEY UPDATE\n"
    . "    file_path = VALUES(file_path),\n"
    . "    status    = 'pending',\n"
    . "    uploaded_at = NOW(),\n"
    . "    reviewed_by = NULL,\n"
    . "    review_note = NULL,\n"
    . "    reviewed_at = NULL"
);

if (!$stmt->execute([$user_id, $doc_type, $relative_path])) {
    $errorInfo = $stmt->errorInfo();
    echo json_encode(["status" => "error", "message" => "Database error: " . ($errorInfo[2] ?? 'Unknown error')]);
    exit();
}

// ── 6. Update user's KYC tier based on how many documents are approved ───────
// (Tier is recalculated in kyc_review.php upon admin approval, not here)

echo json_encode([
    "status"  => "success",
    "message" => "Document submitted for admin review",
    "type"    => $doc_type,
    "path"    => $relative_path,
]);