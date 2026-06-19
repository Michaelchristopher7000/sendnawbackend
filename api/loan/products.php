<?php
require_once '../../config/db.php';
header('Content-Type: application/json');

$stmt = $pdo->prepare("SELECT * FROM loan_products WHERE is_active = 1");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'products' => $products]);
?>