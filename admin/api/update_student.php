<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $student_id = trim($_POST['student_id']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $program_id = (int)$_POST['program_id'];
    $year_level = trim($_POST['year_level']);

    // Validate inputs
    if (empty($student_id) || empty($full_name) || empty($email) || empty($program_id) || empty($year_level)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }

    try {
        // Check if student ID or email already exists for another student
        $stmt = $db->prepare("SELECT id FROM students WHERE (student_id = ? OR email = ?) AND id != ?");
        $stmt->execute([$student_id, $email, $id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Student ID or Email already exists']);
            exit;
        }
        
        // Update student
        $stmt = $db->prepare("
            UPDATE students 
            SET student_id = ?, full_name = ?, email = ?, program_id = ?, year_level = ?
            WHERE id = ?
        ");
        $stmt->execute([$student_id, $full_name, $email, $program_id, $year_level, $id]);
        
        // Update user account if needed
        $stmt = $db->prepare("UPDATE users SET username = ?, email = ? WHERE related_id = ? AND role = 'Student'");
        $stmt->execute([$student_id, $email, $id]);
        
        echo json_encode(['status' => 'success', 'message' => 'Student updated successfully']);
        
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}