<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $project_name = trim($_POST['project_name']);
    $project_case = trim($_POST['project_case']);
    $project_level = trim($_POST['project_level']);

    // Validate inputs
    if (empty($project_name) || empty($project_case) || empty($project_level)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }

    try {
        // Check if project name already exists for another project
        $stmt = $db->prepare("SELECT id FROM projects WHERE project_name = ? AND id != ?");
        $stmt->execute([$project_name, $id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Project with this name already exists']);
            exit;
        }
        
        // Update project
        $stmt = $db->prepare("
            UPDATE projects 
            SET project_name = ?, project_case = ?, project_level = ?
            WHERE id = ?
        ");
        $stmt->execute([$project_name, $project_case, $project_level, $id]);
        
        echo json_encode(['status' => 'success', 'message' => 'Project updated successfully']);
        
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}