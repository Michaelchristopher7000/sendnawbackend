<?php
function sendEmail($to, $subject, $body)
{
    $apiKey = getenv('BREVO_API_KEY');

    $data = [
        'sender' => [
            'name'  => 'SendNaw',
            'email' => 'sendnawt@gmail.com'
        ],
        'to'          => [['email' => $to]],
        'subject'     => $subject,
        'htmlContent' => $body
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'api-key: ' . $apiKey
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 201) {
        return true;
    } else {
        error_log("Brevo email failed: " . $response);
        return false;
    }
}
?>