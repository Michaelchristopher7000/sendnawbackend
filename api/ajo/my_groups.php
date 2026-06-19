<?php
require_once '../../config/db.php';
require_once '../auth.php';
$userId = authenticate($pdo);

$stmt = $pdo->prepare("SELECT g.*, 
                       (SELECT COUNT(*) FROM ajo_contributions WHERE group_id = g.id AND user_id = ? AND cycle = g.current_cycle) as contributed 
                       FROM ajo_groups g 
                       JOIN ajo_members m ON g.id = m.group_id 
                       WHERE m.user_id = ? AND g.status = 'active'");
$stmt->execute([$userId, $userId]);
$groups = $stmt->fetchAll();
echo json_encode(['success' => true, 'groups' => $groups]);
?>