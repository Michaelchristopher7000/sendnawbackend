<?php
header('Content-Type: text/plain');

$url = "https://sandbox.vtpass.com/api/service-variations?serviceID=mtn-data";

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'api-key: e0a299b5aae4d1dc6528a831e35b29a8',
    'secret-key: SK_7148180a7400e284298afb381f0e51cd2a256b7eda8',
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For sandbox testing
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: " . $httpCode . "\n";
if ($curlError) {
    echo "cURL Error: " . $curlError . "\n";
} else {
    echo "Response (first 500 chars):\n" . substr($response, 0, 500) . "\n";
}
?>