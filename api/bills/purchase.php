<?php
require_once '../../config/db.php';
require_once '../auth.php';

define('VT_API_KEY', 'e0a299b5aae4d1dc6528a831e35b29a8');
define('VT_SECRET_KEY', 'SK_3144378752ff108a82cf2ee8cbcc2eb449c903f5ce6');
define('VT_USERNAME', 'mikec9613@gmail.com');
define('VT_PASSWORD', 'Lastborn23@');
define('VT_SANDBOX_URL', 'https://sandbox.vtpass.com/api');

$userId = authenticate($pdo);

$input = json_decode(file_get_contents('php://input'), true);
$type = $input['type'] ?? '';
$providerId = intval($input['provider_id'] ?? 0);
$phoneNumber = trim($input['phone_number'] ?? '');
$amount = floatval($input['amount'] ?? 0);
$variationCode = $input['variation_code'] ?? '';

if (!$type || !$providerId || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
    exit;
}

$stmt = $pdo->prepare("SELECT name FROM service_providers WHERE id = ?");
$stmt->execute([$providerId]);
$provider = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$provider) {
    echo json_encode(['success' => false, 'message' => 'Provider not found']);
    exit;
}
$providerName = strtolower($provider['name']);

function resolveServiceID(string $name, string $type): string
{
    $suffix = $type === 'data' ? '-data' : '';
    if (str_contains($name, 'mtn'))     return 'mtn' . $suffix;
    if (str_contains($name, 'glo'))     return 'glo' . $suffix;
    if (str_contains($name, 'airtel'))  return 'airtel' . $suffix;
    if (str_contains($name, '9mobile')) return '9mobile' . $suffix;
    return '';
}

$serviceID = resolveServiceID($providerName, $type);
if (!$serviceID) {
    echo json_encode(['success' => false, 'message' => 'Unsupported network: ' . $provider['name']]);
    exit;
}

$cleanPhone = $phoneNumber;
if (str_starts_with($cleanPhone, '+234'))
    $cleanPhone = '0' . substr($cleanPhone, 4);
elseif (str_starts_with($cleanPhone, '234') && strlen($cleanPhone) === 13)
    $cleanPhone = '0' . substr($cleanPhone, 3);

if ($type === 'airtime') {
    if (!$cleanPhone) {
        echo json_encode(['success' => false, 'message' => 'Phone number required']);
        exit;
    }
    $requestData = [
        'request_id' => substr('AIR_' . date('YmdHis') . '_' . mt_rand(100, 999), 0, 30),
        'serviceID'  => $serviceID,
        'amount'     => $amount,
        'phone'      => $cleanPhone,
    ];
} elseif ($type === 'data') {
    if (!$cleanPhone || !$variationCode) {
        echo json_encode(['success' => false, 'message' => 'Phone number and variation code required']);
        exit;
    }
    $requestData = [
        'request_id'        => substr('DAT_' . date('YmdHis') . '_' . mt_rand(100, 999), 0, 30),
        'serviceID'         => $serviceID,
        'variation_code'    => $variationCode,
        'phone'             => $cleanPhone,
        'amount'            => $amount,
        'billersCode'       => $cleanPhone,
        'subscription_type' => 'change',
    ];
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid bill type']);
    exit;
}

$basicAuth = base64_encode(VT_USERNAME . ':' . VT_PASSWORD);
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => VT_SANDBOX_URL . '/pay',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'api-key: '             . VT_API_KEY,
        'secret-key: '          . VT_SECRET_KEY,
        'Authorization: Basic ' . $basicAuth,
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS     => json_encode($requestData),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode(['success' => false, 'message' => "VTpass API error (HTTP $httpCode)"]);
    exit;
}

$vtData = json_decode($response, true);
if (!isset($vtData['code']) || $vtData['code'] !== '000') {
    $errMsg = $vtData['response_description'] ?? $vtData['message'] ?? 'VTpass transaction failed';
    echo json_encode(['success' => false, 'message' => $errMsg]);
    exit;
}

$externalRef = $vtData['requestId'] ?? '';

$pdo->beginTransaction();
try {
    // Check balance
    $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency_code = 'NGN' FOR UPDATE");
    $stmt->execute([$userId]);
    $wallet = $stmt->fetch();
    if (!$wallet || $wallet['balance'] < $amount) {
        throw new Exception('Insufficient balance');
    }

    // Deduct
    $stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND currency_code = 'NGN'");
    $stmt->execute([$amount, $userId]);

    // Insert into purchases – 8 placeholders
    $ref = 'BILL_' . date('YmdHis') . '_' . mt_rand(1000, 9999);
    $sql = "INSERT INTO purchases 
            (user_id, type, provider_id, phone_number, amount, ref_code, status, external_reference)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $userId,
        $type,
        $providerId,
        $cleanPhone,
        $amount,
        $ref,
        'success',
        $externalRef
    ]);

    // Insert into transactions – 5 placeholders
    $desc = ucfirst($type) . ' recharge — ' . $provider['name'] . ' (' . $cleanPhone . ')';
    $sqlTx = "INSERT INTO transactions 
              (sender_id, receiver_id, type, amount, currency, description, reference, status)
              VALUES (?, NULL, ?, ?, 'NGN', ?, ?, 'success')";
    $stmt = $pdo->prepare($sqlTx);
    $stmt->execute([
        $userId,
        $type,
        -$amount,
        $desc,
        $ref
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'reference' => $ref]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
