<?php
require_once '../../config/db.php';
require_once '../auth.php';
$userId = authenticate($pdo);
$stmt = $pdo->prepare("SELECT us.*, sp.name, sp.type, sp.interest_rate, sp.duration_days 
                       FROM user_savings us 
                       JOIN savings_plans sp ON us.plan_id = sp.id 
                       WHERE us.user_id = ? AND us.status = 'active'");
$stmt->execute([$userId]);
$savings = $stmt->fetchAll();
echo json_encode(['success'=>true, 'savings'=>$savings]);
?>