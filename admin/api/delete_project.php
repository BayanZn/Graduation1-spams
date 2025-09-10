<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $project_id = (int)$_GET['id'];

    try {
        // Check if project is assigned to any student
        $stmt = $db->prepare("SELECT allocation FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();
        
        if (!$project) {
            echo json_encode(['status' => 'error', 'message' => 'Project not found']);
            exit;
        }
        
        if ($project->allocation) {
            echo json_encode(['status' => 'error', 'message' => 'Cannot delete assigned project']);
            exit;
        }
        
        // First delete supervision records
        $stmt = $db->prepare("DELETE FROM project_supervision WHERE project_id = ?");
        $stmt->execute([$project_id]);
        
        // Then delete the project
        $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Project deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete project']);
        }
        
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Project ID not provided']);
}