<?php
header("Content-Type: application/json");
require_once '../../config/db.php';

$stmt = $pdo->prepare("SELECT id, title, category, outcome_yes, outcome_no, odds_yes, odds_no, end_date, volume, participants FROM prediction_markets WHERE status = 'active' AND end_date > CURDATE()");
$stmt->execute();
$markets = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($markets as &$m) {
    $m['outcomes'] = [$m['outcome_yes'], $m['outcome_no']];
    $m['odds'] = [$m['outcome_yes'] => $m['odds_yes'], $m['outcome_no'] => $m['odds_no']];
    unset($m['outcome_yes'], $m['outcome_no'], $m['odds_yes'], $m['odds_no']);
}
echo json_encode(['success' => true, 'data' => $markets]);
?>