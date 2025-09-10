<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $assignmentId = (int)$_GET['id'];

    try {
        $db->beginTransaction();

        // Get assignment details
        $assignment = $db->query("SELECT * FROM project_assignments WHERE id = $assignmentId")->fetch();

        if (!$assignment) {
            echo json_encode(['status' => 'error', 'message' => 'Assignment not found']);
            exit;
        }

        // Delete assignment
        $db->prepare("DELETE FROM project_assignments WHERE id = ?")->execute([$assignmentId]);

        // Update project status if no other assignments
        $stmt = $db->prepare("SELECT id FROM project_assignments WHERE project_id = ?");
        $stmt->execute([$assignment->project_id]);
        
        if ($stmt->rowCount() === 0) {
            $db->prepare("UPDATE projects SET status = 'Available' WHERE id = ?")->execute([$assignment->project_id]);
        }

        // Clear student's project_id
        $db->prepare("UPDATE students SET project_id = NULL WHERE id = ?")->execute([$assignment->student_id]);

        $db->commit();
        echo json_encode(['status' => 'success', 'message' => 'Assignment deleted successfully']);
    } catch (PDOException $e) {
        $db->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}