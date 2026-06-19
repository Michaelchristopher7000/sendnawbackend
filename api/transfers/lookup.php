<?php
require_once '../../config/db.php';
require_once '../auth.php';

$userId = authenticate($pdo);

$identifier = trim($_GET['identifier'] ?? '');
$type = $_GET['type'] ?? 'tag';

if (!$identifier) {
    echo json_encode(['success' => false, 'message' => 'Identifier required']);
    exit;
}

$column = '';
if ($type === 'tag') $column = 'sendnaw_tag';
elseif ($type === 'account') $column = 'account_number';
elseif ($type === 'phone') $column = 'phone';
else {
    echo json_encode(['success' => false, 'message' => 'Invalid lookup type']);
    exit;
}

// Include avatar_url in the SELECT
$stmt = $pdo->prepare("SELECT id, full_name, email, avatar_url FROM users WHERE $column = ?");
$stmt->execute([$identifier]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Recipient not found']);
    exit;
}

if ($user['id'] == $userId) {
    echo json_encode(['success' => false, 'message' => 'You cannot send money to yourself']);
    exit;
}

// Use stored avatar if available, otherwise generate a DiceBear avatar
$avatar = $user['avatar_url'];
if (empty($avatar)) {
    $avatar = "https://api.dicebear.com/9.x/avataaars/svg?seed=" . urlencode($user['email']) . "&background=6f42c1";
}

echo json_encode([
    'success' => true,
    'full_name' => $user['full_name'],
    'avatar_url' => $avatar
]);
