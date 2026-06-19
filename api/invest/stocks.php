<?php
require_once '../../config/db.php';
header('Content-Type: application/json');

$stmt = $pdo->prepare("SELECT id, symbol, company_name, current_price, currency, change_percent, logo_url FROM stocks WHERE is_active = 1");
$stmt->execute();
$stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'stocks' => $stocks]);
?>