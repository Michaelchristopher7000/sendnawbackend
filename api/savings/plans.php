<?php
require_once '../../config/db.php';
$stmt = $pdo->prepare("SELECT * FROM savings_plans WHERE is_active = 1");
$stmt->execute();
echo json_encode(['success'=>true, 'plans'=>$stmt->fetchAll()]);
?>