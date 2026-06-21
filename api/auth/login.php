<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once __DIR__ . '/../../utils/mailer.php';

$data = json_decode(file_get_contents('php://input'), true);
$password = $data['password'] ?? '';

if (!$password) {
    echo json_encode(['success' => false, 'message' => 'Password required']);
    exit;
}

// Determine login type
if (isset($data['email']) && !empty($data['email'])) {
    $email = trim($data['email']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
} elseif (isset($data['username']) && !empty($data['username'])) {
    $username = trim($data['username']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE sendnaw_tag = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
} else {
    $phoneDigits = preg_replace('/\D/', '', $data['phone'] ?? '');
    $countryCode = $data['country_code'] ?? '+234';
    if (!$phoneDigits) {
        echo json_encode(['success' => false, 'message' => 'Phone number required']);
        exit;
    }
    $fullPhone = ltrim($countryCode, '+') . $phoneDigits;
    $fullPhoneWithPlus = '+' . $fullPhone;
    $localPhone = $phoneDigits;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? OR phone = ? OR phone = ?");
    $stmt->execute([$fullPhone, $fullPhoneWithPlus, $localPhone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
}

// Verify password
if (!password_verify($password, $user['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid password']);
    exit;
}

if (!$user['is_active']) {
    echo json_encode(['success' => false, 'message' => 'Account disabled']);
    exit;
}

// --- Generate token and store device info ---
$token = bin2hex(random_bytes(32));
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown device';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';

preg_match('/\((.*?)\)/', $userAgent, $matches);
$deviceName = $matches[1] ?? 'Unknown Device';

$stmt = $pdo->prepare("DELETE FROM user_tokens WHERE user_id = ?");
$stmt->execute([$user['id']]);

$stmt = $pdo->prepare("INSERT INTO user_tokens (user_id, token, user_agent, ip_address, device_name, last_activity, created_at) 
                       VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
$stmt->execute([$user['id'], $token, $userAgent, $ip, $deviceName]);

// --- Get location from IP (using ip-api.com) ---
$location = 'Unknown';
if ($ip && $ip !== 'Unknown IP' && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
    // Add a strict 2-second timeout so the login doesn't hang if the API is slow/rate-limited
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    $geo = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,city,country", false, $ctx);
    if ($geo !== false) {
        $geoData = json_decode($geo, true);
        if (isset($geoData['status']) && $geoData['status'] === 'success') {
            $location = $geoData['city'] . ', ' . $geoData['country'];
        }
    }
}

// --- Send login alert email with location ---
$loginTime = date('Y-m-d H:i:s');
$subject = "New login to your SendNaw account";
$body = "<h3>Security Alert</h3>
         <p>Your SendNaw account was just logged into.</p>
         <ul>
            <li><strong>Time:</strong> $loginTime</li>
            <li><strong>IP Address:</strong> $ip</li>
            <li><strong>Location:</strong> $location</li>
            <li><strong>Device:</strong> $deviceName</li>
            <li><strong>User Agent:</strong> $userAgent</li>
         </ul>
         <p>If this wasn't you, please contact support immediately.</p>";
// sendEmail($user['email'], $subject, $body); // Temporarily disabled: SMTP is slowing down logins

// --- Return response ---
unset($user['password_hash'], $user['pin_hash']);
echo json_encode([
    'success' => true,
    'token' => $token,
    'user' => $user,
    'redirect' => $user['role'] === 'admin' ? '/admin/dashboard' : '/dashboard'
]);
?>