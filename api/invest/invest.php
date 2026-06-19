<?php
require_once '../../config/db.php';
require_once '../auth.php';
$userId = authenticate($pdo);
$data = json_decode(file_get_contents('php://input'), true);
$productId = intval($data['product_id'] ?? 0);
$amount = floatval($data['amount'] ?? 0);
// Similar logic as savings: check wallet, deduct, insert into user_investments, set end_date = NOW() + duration_days.
?>