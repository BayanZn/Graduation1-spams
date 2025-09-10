<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $userId = (int)$_GET['id'];

    try {
        // Get user role first
        $stmt = $db->prepare("SELECT role, related_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit;
        }

        // Delete from related table if exists
        if ($user->related_id) {
            if ($user->role === 'Student') {
                $db->prepare("DELETE FROM students WHERE id = ?")->execute([$user->related_id]);
            } elseif ($user->role === 'Supervisor') {
                $db->prepare("DELETE FROM supervisors WHERE id = ?")->execute([$user->related_id]);
            }
        }

        // Delete from users table
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);

        echo json_encode(['status' => 'success', 'message' => 'User deleted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}