<?php
function getEnvValue($key, $default = null)
{
    $value = getenv($key);
    if ($value === false || $value === null || trim($value) === '') {
        $envFile = __DIR__ . '/../config/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    [$envKey, $envValue] = explode('=', $line, 2);
                    if (trim($envKey) === $key) {
                        $value = trim($envValue);
                        break;
                    }
                }
            }
        }
    }

    if (is_string($value)) {
        $value = trim($value);
        if ((strlen($value) >= 2) && ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') || (substr($value, 0, 1) === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }
    }

    return ($value === false || $value === null || trim($value) === '') ? $default : $value;
}

function sendEmail($to, $subject, $body)
{
    $apiKey = getEnvValue('BREVO_API_KEY');
    if (empty($apiKey)) {
        error_log('Brevo email failed: missing BREVO_API_KEY');
        return false;
    }

    $senderName = getEnvValue('BREVO_SENDER_NAME', 'SendNaw');
    $senderEmail = getEnvValue('BREVO_SENDER_EMAIL', 'sendnawt@gmail.com');

    $data = [
        'sender' => [
            'name'  => $senderName,
            'email' => $senderEmail
        ],
        'to'          => [['email' => $to]],
        'subject'     => $subject,
        'htmlContent' => $body
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'api-key: ' . $apiKey
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 201 || $httpCode === 202) {
        return true;
    }

    error_log('Brevo email failed. HTTP ' . $httpCode . ': ' . $response);
    return false;
}
