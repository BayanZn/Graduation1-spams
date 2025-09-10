<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $programName = trim($_POST['program_name']);
    $programCode = trim($_POST['program_code']);
    $department = trim($_POST['department']);
    $duration = (int)$_POST['duration_years'];

    // Validate inputs
    if (empty($programName) || empty($programCode) || empty($department)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }

    // Check if program code already exists
    $stmt = $db->prepare("SELECT id FROM programs WHERE program_code = ?");
    $stmt->execute([$programCode]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Program code already exists']);
        exit;
    }

    try {
        $db->prepare("
            INSERT INTO programs (program_name, program_code, department, duration_years)
            VALUES (?, ?, ?, ?)
        ")->execute([$programName, $programCode, $department, $duration]);

        echo json_encode(['status' => 'success', 'message' => 'Program added successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}