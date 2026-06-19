<?php
require_once '../../config/db.php';
require_once '../auth.php';
$userId = authenticate($pdo);

$stmt = $pdo->prepare("SELECT card_number, expiry_month, expiry_year, cvv, balance, currency, status FROM virtual_cards WHERE user_id = ? AND status = 'active'");
$stmt->execute([$userId]);
$card = $stmt->fetch();
if (!$card) {
    echo json_encode(['success'=>false,'message'=>'No active card']);
    exit;
}
// Mask card number
$card['card_number_masked'] = '**** **** **** ' . substr($card['card_number'], -4);
echo json_encode(['success'=>true, 'card'=>$card]);
?>