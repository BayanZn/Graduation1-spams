<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $projectId = (int)$_GET['project_id'];

    if (empty($projectId)) {
        echo json_encode(['status' => 'error', 'message' => 'Project ID is required']);
        exit;
    }

    try {
        $stmt = $db->prepare("
            SELECT s.id, s.full_name, s.student_id
            FROM students s
            WHERE s.project_id = ?
            ORDER BY s.full_name
        ");
        $stmt->execute([$projectId]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'students' => $students]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}