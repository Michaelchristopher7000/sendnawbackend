<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['ORIGIN'] ?? '';
$allowed_origins = [
    'http://localhost:5173',
    'http://localhost:3000',
    'https://sendnaw.vercel.app'
];

if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With, Accept');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/db.php';
require_once '../auth.php';

function getUserId($pdo)
{
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    $token = '';
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
    }
    if (!$token) return null;
    $stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['user_id'] : null;
}

try {
    $userId = getUserId($pdo);
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $stmt = $pdo->prepare(
            "SELECT id, full_name, identifier, send_type, avatar_url, created_at
             FROM beneficiaries
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT 20"
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'beneficiaries' => $rows]);
        exit;
    }

    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true);
        $fullName   = trim($body['full_name'] ?? '');
        $identifier = trim($body['identifier'] ?? '');
        $sendType   = trim($body['send_type'] ?? 'tag');
        $avatarUrl  = $body['avatar_url'] ?? null;

        if (!$fullName || !$identifier) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'full_name and identifier required']);
            exit;
        }

        $allowed = ['tag', 'account', 'phone'];
        if (!in_array($sendType, $allowed)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid send_type']);
            exit;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO beneficiaries (user_id, full_name, identifier, send_type, avatar_url)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), avatar_url = VALUES(avatar_url), created_at = NOW()"
        );
        $stmt->execute([$userId, $fullName, $identifier, $sendType, $avatarUrl]);

        echo json_encode(['success' => true, 'message' => 'Beneficiary saved']);
        exit;
    }

    if ($method === 'DELETE') {
        $body = json_decode(file_get_contents('php://input'), true);
        $id = (int)($body['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'id required']);
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM beneficiaries WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        echo json_encode(['success' => true, 'message' => 'Beneficiary removed']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
