<?php
// Africa's Talking Credentials
define('AT_API_KEY',  'atsk_3509da19bf7336d0ed01fe260bc07d15606b695259d8d6149d91972256fca5164cdd60b2');
define('AT_USERNAME', 'sandbox');
define('AT_SMS_URL',  'https://api.sandbox.africastalking.com/version1/messaging');

/**
 * Sends an SMS via Africa's Talking Sandbox
 * Returns true on success, false on failure
 */
function sendSMS($phone, $message) {
    $data = [
        "username" => AT_USERNAME,
        "to"       => $phone,
        "message"  => $message,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, AT_SMS_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apiKey: " . AT_API_KEY,
        "Accept: application/json",
        "Content-Type: application/x-www-form-urlencoded"
    ]);

    $response = curl_exec($ch);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        return ["success" => false, "error" => $curl_err];
    }

    $result = json_decode($response, true);

    // Check if Africa's Talking accepted the message
    if (
        isset($result['SMSMessageData']['Recipients'][0]['status']) &&
        $result['SMSMessageData']['Recipients'][0]['status'] === 'Success'
    ) {
        return ["success" => true];
    }

    return [
        "success"  => false,
        "error"    => $result['SMSMessageData']['Message'] ?? 'Unknown error'
    ];
}
?>