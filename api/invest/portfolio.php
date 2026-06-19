<?php
require_once '../../config/db.php';
require_once '../auth.php';

$userId = authenticate($pdo);

$stmt = $pdo->prepare("SELECT s.id, s.symbol, s.company_name, s.current_price, s.logo_url,
                              up.quantity, up.average_buy_price,
                              (s.current_price * up.quantity) as current_value,
                              ((s.current_price - up.average_buy_price) * up.quantity) as profit_loss
                       FROM user_portfolio up 
                       JOIN stocks s ON up.stock_id = s.id 
                       WHERE up.user_id = ?");
$stmt->execute([$userId]);
$portfolio = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success'=>true, 'portfolio'=>$portfolio]);
?>