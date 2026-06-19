<?php
require_once '../../config/db.php';
$stmt = $pdo->prepare("SELECT * FROM invest_products WHERE is_active = 1");
$stmt->execute();
echo json_encode(['success'=>true, 'products'=>$stmt->fetchAll()]);
?>