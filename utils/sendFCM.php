<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;

function sendFCMPushNotification($fcmToken, $title, $body, $data = []) {
    $serviceAccountPath = __DIR__ . '/../config/service-account-key.json';
    if (!file_exists($serviceAccountPath)) {
        error_log("FCM: Service account key missing");
        return false;
    }

    $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
    $projectId = $serviceAccount['project_id'] ?? null;
    if (!$projectId) return false;

    // JWT for access token
    $now = time();
    $payload = [
        'iss' => $serviceAccount['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now,
    ];
    $jwt = JWT::encode($payload, $serviceAccount['private_key'], 'RS256');

    // Exchange for access token
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt,
    ]));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("FCM: Token exchange failed: $response");
        return false;
    }
    $accessToken = json_decode($response, true)['access_token'] ?? null;
    if (!$accessToken) return false;

    // Send notification
    $fcmUrl = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
    $payload = [
        'message' => [
            'token' => $fcmToken,
            'notification' => ['title' => $title, 'body' => $body],
        ]
    ];
    if (!empty($data)) $payload['message']['data'] = $data;

    $ch = curl_init($fcmUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) return true;
    error_log("FCM: Send failed: $response");
    return false;
}

?>