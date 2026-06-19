<?php
require_once '../../config/db.php';
require_once '../auth.php';

$userId = authenticate($pdo);

$stmt = $pdo->prepare("SELECT ug.id, ug.card_code, ug.face_value, ug.purchased_at, ug.status, gp.brand 
                       FROM user_giftcards ug 
                       JOIN giftcard_products gp ON ug.product_id = gp.id 
                       WHERE ug.user_id = ? 
                       ORDER BY ug.purchased_at DESC");
$stmt->execute([$userId]);
$cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'cards' => $cards]);
