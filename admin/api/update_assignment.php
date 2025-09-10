<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assignmentId = (int)$_POST['assignment_id'];
    $status = trim($_POST['status']);

    try {
        $db->prepare("
            UPDATE project_assignments 
            SET status = ?
            WHERE id = ?
        ")->execute([$status, $assignmentId]);

        echo json_encode(['status' => 'success', 'message' => 'Assignment updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}