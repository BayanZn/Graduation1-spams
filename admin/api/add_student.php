<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        // Check if student ID or email already exists
        $stmt = $db->prepare("SELECT id FROM students WHERE student_id = ? OR email = ?");
        $stmt->execute([$student_id, $email]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Student ID or Email already exists']);
            exit;                                       
        }
        
        // Insert new student
        $stmt = $db->prepare("INSERT INTO students (student_id, full_name, email, program_id, year_level) 
                             VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $full_name, $email, $program_id, $year_level]);
        
        // Create user account
        $password = password_hash($student_id, PASSWORD_DEFAULT); // Default password is student ID
        $stmt = $db->prepare("INSERT INTO users (username, password, email, role, related_id) 
                             VALUES (?, ?, ?, 'Student', LAST_INSERT_ID())");
        $stmt->execute([$student_id, $password, $email]);
        
        echo json_encode(['status' => 'success', 'message' => 'Student added successfully']);
        
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}