<?php
header('Content-Type: application/json');
require_once '../../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

$fullName = trim($data['full_name'] ?? '');
$phone = trim($data['phone'] ?? ''); // already full with country code (e.g., 2348012345678)
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$countryCode = $data['country_code'] ?? '+234';

if (!$fullName || !$phone || !$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'All fields required']);
    exit;
}

// Check if phone or email exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? OR email = ?");
$stmt->execute([$phone, $email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Phone or email already registered']);
    exit;
}

// Generate unique sendnaw_tag
$baseTag = strtolower(preg_replace('/[^a-z0-9]/i', '', $fullName));
$tag = $baseTag;
$counter = 1;
while (true) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE sendnaw_tag = ?");
    $stmt->execute([$tag]);
    if (!$stmt->fetch()) break;
    $tag = $baseTag . '_' . $counter++;
}

$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (full_name, phone, email, password_hash, sendnaw_tag, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
$success = $stmt->execute([$fullName, $phone, $email, $hashed, $tag]);

if ($success) {
    $userId = $pdo->lastInsertId();

    // Generate account number based on user ID (guaranteed unique)
    $accountNumber = '10' . str_pad($userId, 8, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("UPDATE users SET account_number = ? WHERE id = ?");
    $stmt->execute([$accountNumber, $userId]);

    // Generate avatar URL using DiceBear with email as seed
    $avatarUrl = "https://api.dicebear.com/9.x/avataaars/svg?seed=" . urlencode($email) . "&background=6f42c1";
    $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
    $stmt->execute([$avatarUrl, $userId]);

    // Create default wallets for NGN, USD, GBP
    $currencies = ['NGN', 'USD', 'GBP'];
    $stmt = $pdo->prepare("INSERT INTO wallets (user_id, currency_code, balance) VALUES (?, ?, 0)");
    foreach ($currencies as $cur) {
        $stmt->execute([$userId, $cur]);
    }

    // (Optional) Set default currency preferences if columns exist
    // Most likely they have defaults set in DB, but safe to include:
    $pdo->prepare("UPDATE users SET default_currency = 'NGN', display_currency = 'NGN' WHERE id = ?")->execute([$userId]);

    echo json_encode([
        'success' => true,
        'sendnaw_tag' => $tag,
        'account_number' => $accountNumber,
        'message' => 'Registration successful'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed']);
}
?>