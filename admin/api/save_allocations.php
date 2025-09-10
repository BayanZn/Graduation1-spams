<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $allocations = json_decode($_POST['allocations'], true);

    if (empty($allocations)) {
        echo json_encode(['status' => 'error', 'message' => 'No allocations provided']);
        exit;
    }

    try {
        $db->beginTransaction();
        
        foreach ($allocations as $alloc) {
            // Update student's project
            $stmt = $db->prepare("UPDATE students SET project_id = ? WHERE id = ?");
            $stmt->execute([$alloc['projectId'], $alloc['studentId']]);
            
            // Update project allocation status
            $stmt = $db->prepare("UPDATE projects SET allocation = 1 WHERE id = ?");
            $stmt->execute([$alloc['projectId']]);
            
            // Create defense schedule record (pending status)
            $stmt = $db->prepare("INSERT INTO defense_schedule 
                                 (student_id, project_id, scheduled_date, location, panel_chair_id, status) 
                                 VALUES (?, ?, NULL, 'To be scheduled', 0, 'Pending')");
            $stmt->execute([$alloc['studentId'], $alloc['projectId']]);
        }
        
        $db->commit();
        echo json_encode(['status' => 'success', 'message' => 'Allocations saved successfully']);
        
    } catch(PDOException $e) {
        $db->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}