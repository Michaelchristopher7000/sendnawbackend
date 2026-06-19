<?php
require_once '../../config/db.php';
require_once '../auth.php';

define('VT_API_KEY', 'e0a299b5aae4d1dc6528a831e35b29a8');
define('VT_SECRET_KEY', 'SK_7148180a7400e284298afb381f0e51cd2a256b7eda8');

$userId = authenticate($pdo);

$providerId = $_GET['provider_id'] ?? 0;
if (!$providerId) {
    echo json_encode(['success' => false, 'message' => 'Provider ID required']);
    exit;
}

$stmt = $pdo->prepare("SELECT name FROM service_providers WHERE id = ?");
$stmt->execute([$providerId]);
$provider = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$provider) {
    echo json_encode(['success' => false, 'message' => 'Provider not found']);
    exit;
}

$name = strtolower($provider['name']);
$serviceID = '';
if (strpos($name, 'mtn') !== false) $serviceID = 'mtn-data';
elseif (strpos($name, 'airtel') !== false) $serviceID = 'airtel-data';
elseif (strpos($name, 'glo') !== false) $serviceID = 'glo-data';
elseif (strpos($name, '9mobile') !== false) $serviceID = '9mobile-data';
else {
    echo json_encode(['success' => false, 'message' => 'Unsupported network']);
    exit;
}

$url = "https://sandbox.vtpass.com/api/service-variations?serviceID=" . urlencode($serviceID);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'api-key: ' . VT_API_KEY,
    'secret-key: ' . VT_SECRET_KEY,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode(['success' => false, 'message' => "HTTP $httpCode"]);
    exit;
}

$data = json_decode($response, true);
$variations = [];
$seenCodes = [];

if (isset($data['content']['variations']) && is_array($data['content']['variations'])) {
    foreach ($data['content']['variations'] as $var) {
        $code = $var['variation_code'];
        if (isset($seenCodes[$code])) continue;
        $seenCodes[$code] = true;

        $amount = isset($var['variation_amount']) ? floatval($var['variation_amount']) : 0;
        if ($amount > 0) {
            $variations[] = [
                'label' => $var['name'],
                'variation_code' => $code,
                'amount' => $amount
            ];
        }
    }
}

echo json_encode(['success' => true, 'variations' => $variations]);
