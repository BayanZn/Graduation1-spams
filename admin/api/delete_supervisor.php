<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $supervisor_id = (int)$_GET['id'];

    try {
        // Check if supervisor has any projects assigned
        $stmt = $db->prepare("SELECT COUNT(*) as project_count FROM project_supervision WHERE supervisor_id = ?");
        $stmt->execute([$supervisor_id]);
        $result = $stmt->fetch();
        
        if ($result->project_count > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Cannot delete supervisor with assigned projects']);
            exit;
        }
        
        // Get supervisor details to delete user account
        $stmt = $db->prepare("SELECT staff_id FROM supervisors WHERE id = ?");
        $stmt->execute([$supervisor_id]);
        $supervisor = $stmt->fetch();
        
        if (!$supervisor) {
            echo json_encode(['status' => 'error', 'message' => 'Supervisor not found']);
            exit;
        }
        
        // Delete user account first
        $stmt = $db->prepare("DELETE FROM users WHERE username = ? AND role = 'Supervisor'");
        $stmt->execute([$supervisor->staff_id]);
        
        // Then delete supervisor
        $stmt = $db->prepare("DELETE FROM supervisors WHERE id = ?");
        $stmt->execute([$supervisor_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Supervisor deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete supervisor']);
        }
        
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Supervisor ID not provided']);
}