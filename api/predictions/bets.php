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

$input = json_decode(file_get_contents('php://input'), true);
$market_id = intval($input['market_id'] ?? 0);
$outcome = $input['outcome'] ?? '';
$amount = floatval($input['amount'] ?? 0);

if (!$market_id || !$outcome || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Get market odds
    $stmt = $pdo->prepare("SELECT odds_yes, odds_no FROM prediction_markets WHERE id = ? AND status = 'active'");
    $stmt->execute([$market_id]);
    $market = $stmt->fetch();
    if (!$market) throw new Exception("Market not available");

    $odds = ($outcome === 'Yes') ? $market['odds_yes'] : $market['odds_no'];
    $potential_win = $amount * $odds;

    // Deduct from user's USD wallet
    $stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND currency = 'USD' AND balance >= ?");
    $stmt->execute([$amount, $user_id, $amount]);
    if ($stmt->rowCount() === 0) throw new Exception("Insufficient balance");

    // Insert bet
    $stmt = $pdo->prepare("INSERT INTO prediction_bets (user_id, market_id, outcome, amount_usd, odds, potential_win)
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $market_id, $outcome, $amount, $odds, $potential_win]);

    // Update market volume and participants (optional)
    $pdo->prepare("UPDATE prediction_markets SET volume = volume + ?, participants = participants + 1 WHERE id = ?")
        ->execute([$amount, $market_id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Bet placed', 'bet_id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>