<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $programId = (int)$_GET['id'];

    try {
        // Check if program has students
        $stmt = $db->prepare("SELECT id FROM students WHERE program_id = ?");
        $stmt->execute([$programId]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Cannot delete: Program has students']);
            exit;
        }

        // Delete program
        $db->prepare("DELETE FROM programs WHERE id = ?")->execute([$programId]);

        echo json_encode(['status' => 'success', 'message' => 'Program deleted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}