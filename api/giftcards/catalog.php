<?php
header('Content-Type: application/json');
// Prestmit API credentials (register at prestmit.io to get your key)
define('PRESTMIT_API_KEY', 'your-api-key');

function fetchFromPrestmit($endpoint)
{
    $ch = curl_init('https://api.prestmit.io/v1/' . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . PRESTMIT_API_KEY,
        'Content-Type: application/json'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Get list of available gift cards
$cards = fetchFromPrestmit('gift-cards'); // adjust endpoint based on Prestmit docs
if (!$cards) {
    // fallback to mock data while testing
    $cards = [
        ['id' => 1, 'brand' => 'Amazon', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/a/a9/Amazon_logo.svg', 'min_amount' => 10, 'max_amount' => 1000],
        ['id' => 2, 'brand' => 'Apple',   'logo' => 'https://upload.wikimedia.org/wikipedia/commons/f/fa/Apple_logo_black.svg', 'min_amount' => 10, 'max_amount' => 500],
        // ... more cards
    ];
}

echo json_encode(['success' => true, 'cards' => $cards]);
