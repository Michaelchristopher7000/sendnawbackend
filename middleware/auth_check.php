<?php
require_once __DIR__ . '/../config/db.php';

header("Content-Type: application/json");

// ---- Robust Authorization header extraction ----
$auth_header = '';

if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
} elseif (function_exists('getallheaders')) {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
    } elseif (isset($headers['authorization'])) {
        $auth_header = $headers['authorization'];
    }
}

if (!$auth_header || !str_starts_with($auth_header, 'Bearer ')) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized. Please log in"
    ]);
    exit;
}

$token = trim(substr($auth_header, 7));

try {
    // ✅ Query user_tokens instead of sessions
    $stmt = $pdo->prepare("
        SELECT ut.user_id,
               ut.expires_at,
               u.full_name,
               u.role,
               u.is_active
        FROM user_tokens ut
        JOIN users u ON ut.user_id = u.id
        WHERE ut.token = ?
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Invalid session"
        ]);
        exit;
    }

    // Check expiration only if expires_at is set
    if ($session['expires_at'] && strtotime($session['expires_at']) < time()) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Session expired"
        ]);
        exit;
    }

    if (!$session['is_active']) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Account suspended"
        ]);
        exit;
    }

    // Return user data
    return [
        "id"        => $session['user_id'],
        "full_name" => $session['full_name'],
        "role"      => $session['role']
    ];

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
    exit;
}