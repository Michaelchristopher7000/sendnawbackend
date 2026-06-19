<?php
require_once '../../config/db.php';
require_once '../auth.php';

$userId = authenticate($pdo);
$data = json_decode(file_get_contents('php://input'), true);
$groupId = intval($data['group_id'] ?? 0);

if (!$groupId) {
    echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
    exit;
}

$pdo->beginTransaction();
try {
    // Check if group exists, is active, and user is creator
    $stmt = $pdo->prepare("SELECT created_by, status FROM ajo_groups WHERE id = ? FOR UPDATE");
    $stmt->execute([$groupId]);
    $group = $stmt->fetch();
    if (!$group) throw new Exception('Group not found');
    if ($group['status'] !== 'active') throw new Exception('Group is already closed or inactive');
    if ($group['created_by'] != $userId) throw new Exception('Only the group creator can close the group');

    // Optional: verify all cycles completed? You can add checks here if needed.

    // Update status to 'completed'
    $stmt = $pdo->prepare("UPDATE ajo_groups SET status = 'completed' WHERE id = ?");
    $stmt->execute([$groupId]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Group closed successfully']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>