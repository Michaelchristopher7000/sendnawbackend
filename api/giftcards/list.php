<?php
require_once '../../config/db.php';

$stmt = $pdo->prepare("SELECT id, brand, selling_price, stock FROM giftcard_products WHERE stock > 0");
$stmt->execute();
$cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'cards' => $cards]);
