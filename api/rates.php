<?php
require_once __DIR__ . '/cors.php';
require_once '../../../config/db.php';
require_once '../../../middleware/auth_check.php';
// ... rest of your code

// Your existing code to fetch exchange rates goes here
// For example, a simple mock response:
$rates = [
    "NGN" => 1,
    "USD" => 0.00065,
    "EUR" => 0.00060,
    "GBP" => 0.00051,
    "CAD" => 0.00088,
    "GHS" => 0.0097,
    "AED" => 0.00239,
    "KES" => 0.084,
    "ZAR" => 0.012
];
echo json_encode($rates);
?>