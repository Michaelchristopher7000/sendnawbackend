<?php
require_once '../../config/db.php';
require_once '../auth.php';
$userId = authenticate($pdo);

// Check if user already has an active card
$stmt = $pdo->prepare("SELECT id FROM virtual_cards WHERE user_id = ? AND status = 'active'");
$stmt->execute([$userId]);
if ($stmt->fetch()) {
    echo json_encode(['success'=>false,'message'=>'You already have an active virtual card']);
    exit;
}

// Generate mock card data
$cardNumber = '4' . str_pad(rand(0,9999999999), 10, '0', STR_PAD_LEFT);
$expiryMonth = date('m', strtotime('+3 years'));
$expiryYear = date('Y', strtotime('+3 years'));
$cvv = rand(100,999);

$stmt = $pdo->prepare("INSERT INTO virtual_cards (user_id, card_number, expiry_month, expiry_year, cvv) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$userId, $cardNumber, $expiryMonth, $expiryYear, $cvv]);

echo json_encode(['success'=>true, 'card_number'=>$cardNumber, 'expiry'=>"$expiryMonth/$expiryYear", 'cvv'=>$cvv]);
?>