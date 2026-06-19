<?php
require_once '../../config/db.php';
require_once '../auth.php';

$userId = authenticate($pdo);
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userRole = $stmt->fetchColumn();
if (!in_array($userRole, ['admin','ceo'])) {
    echo json_encode(['success'=>false,'message'=>'Admin access required']);
    exit;
}

$stmt = $pdo->prepare("SELECT l.*, u.full_name, u.email, p.name as product_name 
                       FROM loans l 
                       JOIN users u ON l.user_id = u.id 
                       JOIN loan_products p ON l.product_id = p.id 
                       WHERE l.status = 'pending' 
                       ORDER BY l.created_at ASC");
$stmt->execute();
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['success'=>true, 'loans'=>$loans]);
?>