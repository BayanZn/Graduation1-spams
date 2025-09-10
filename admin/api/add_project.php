<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_name = trim($_POST['project_name']);
    $project_case = trim($_POST['project_case']);
    $project_level = trim($_POST['project_level']);

    // Validate inputs
    if (empty($project_name) || empty($project_case) || empty($project_level)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }

    try {
        // Check if project already exists
        $stmt = $db->prepare("SELECT id FROM projects WHERE project_name = ?");
        $stmt->execute([$project_name]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Project with this name already exists']);
            exit;
        }
        
        // Insert new project
        $stmt = $db->prepare("INSERT INTO projects 
                             (project_name, project_case, project_level) 
                             VALUES (?, ?, ?)");
        $stmt->execute([$project_name, $project_case, $project_level]);
        
        echo json_encode(['status' => 'success', 'message' => 'Project added successfully']);
        
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}