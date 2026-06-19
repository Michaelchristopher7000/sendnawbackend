<?php
// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(200);
    exit;
}
header("Content-Type: application/json");

require_once __DIR__ . "/../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(["success" => false, "message" => "Email and password required"]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, full_name, email, password_hash, role, is_active FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password_hash'])) {
    // Check if user is active
    if (!$user['is_active']) {
        echo json_encode(["success" => false, "message" => "Account suspended"]);
        exit;
    }

    // Generate a new session token
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+7 days')); // 7 days validity

    // Delete any old sessions for this user (optional but clean)
    $stmt = $pdo->prepare("DELETE FROM sessions WHERE user_id = ?");
    $stmt->execute([$user['id']]);

    // Insert new session
    $stmt = $pdo->prepare("INSERT INTO sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$user['id'], $token, $expires_at]);

    echo json_encode([
        "success" => true,
        "message" => "Login successful",
        "token" => $token,
        "user" => [
            "id" => $user['id'],
            "full_name" => $user['full_name'],
            "email" => $user['email'],
            "role" => $user['role']
        ]
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid credentials"]);
}
