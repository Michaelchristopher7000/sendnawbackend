<?php
require_once __DIR__ . "/../config/db.php";

function updateUserProfile($user_id, $data)
{
    global $pdo;
    try {
        $allowedFields = ['full_name', 'email', 'phone'];
        $updates = [];
        $params = [];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        if (empty($updates)) {
            return ['status' => 'error', 'message' => 'No fields to update'];
        }
        $params[] = $user_id;
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Fetch updated user
        $stmt = $pdo->prepare("SELECT id, full_name, email, phone, sendnaw_tag, role, is_active, kyc_status, avatar_url FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return ['status' => 'success', 'data' => $user];
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}
?>