<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $programId = (int)$_POST['program_id'];
    $programName = trim($_POST['program_name']);
    $programCode = trim($_POST['program_code']);
    $department = trim($_POST['department']);
    $duration = (int)$_POST['duration_years'];

    // Validate inputs
    if (empty($programName) || empty($programCode) || empty($department)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }

    // Check if program code already exists (excluding current program)
    $stmt = $db->prepare("SELECT id FROM programs WHERE program_code = ? AND id != ?");
    $stmt->execute([$programCode, $programId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Program code already exists']);
        exit;
    }

    try {
        $db->prepare("
            UPDATE programs 
            SET program_name = ?, program_code = ?, department = ?, duration_years = ?
            WHERE id = ?
        ")->execute([$programName, $programCode, $department, $duration, $programId]);

        echo json_encode(['status' => 'success', 'message' => 'Program updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}