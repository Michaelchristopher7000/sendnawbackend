<?php
require_once '../../config/db.php';
require_once '../auth.php';

$userId = authenticate($pdo);

$stmt = $pdo->prepare("SELECT l.*, p.name as product_name 
                       FROM loans l 
                       JOIN loan_products p ON l.product_id = p.id 
                       WHERE l.user_id = ? 
                       ORDER BY l.created_at DESC");
$stmt->execute([$userId]);
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success'=>true, 'loans'=>$loans]);
?>