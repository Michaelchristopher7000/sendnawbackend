<?php
header("Content-Type: application/json");
require_once '../../config/db.php';
require_once '../../utils/auth.php';

$auth = authenticate();
if (!$auth) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$user_id = $auth['user_id'];

try {
    $stmt = $pdo->prepare("SELECT currency, balance FROM crypto_wallets WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $wallets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch live prices from CoinGecko or your own rates API
    $prices = [];
    $rates = file_get_contents("https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum,solana,binancecoin&vs_currencies=usd");
    $ratesData = json_decode($rates, true);
    $map = ['BTC' => 'bitcoin', 'ETH' => 'ethereum', 'SOL' => 'solana', 'BNB' => 'binancecoin'];
    foreach ($wallets as &$w) {
        $coin = $map[$w['currency']] ?? null;
        $w['price_usd'] = $coin ? $ratesData[$coin]['usd'] : 0;
        $w['value_usd'] = $w['balance'] * $w['price_usd'];
    }

    echo json_encode(['success' => true, 'data' => $wallets]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>