<?php
header('Content-Type: application/json');
require_once '../../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

$fullName = trim($data['full_name'] ?? '');
$phone = trim($data['phone'] ?? ''); // already full with country code (e.g., 2348012345678)
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$countryCode = $data['country_code'] ?? '+234';
$accountType = $data['account_type'] ?? 'Personal';
$defaultCurrency = $data['default_currency'] ?? 'NGN';

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

$stmt = $pdo->prepare("INSERT INTO users (full_name, phone, email, password_hash, sendnaw_tag, account_type, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
$success = $stmt->execute([$fullName, $phone, $email, $hashed, $tag, $accountType]);

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

    // Create default wallets for NGN, USD, GBP, EUR
    $currencies = ['NGN', 'USD', 'GBP', 'EUR'];
    $stmt = $pdo->prepare("INSERT INTO wallets (user_id, currency_code, balance) VALUES (?, ?, 0)");
    foreach ($currencies as $cur) {
        $stmt->execute([$userId, $cur]);
    }

    // Set default currency preferences
    $pdo->prepare("UPDATE users SET default_currency = ?, display_currency = ? WHERE id = ?")->execute([$defaultCurrency, $defaultCurrency, $userId]);

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