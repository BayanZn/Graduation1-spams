<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id = trim($_POST['staff_id']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $department = trim($_POST['department']);
    $specialization = trim($_POST['specialization']);
    $max_projects = (int)$_POST['max_projects'];

    // Validate inputs
    if (empty($staff_id) || empty($full_name) || empty($email) || empty($department)) {
        echo json_encode(['status' => 'error', 'message' => 'Required fields are missing']);
        exit;
    }

    try {
        // Check if staff ID or email already exists
        $stmt = $db->prepare("SELECT id FROM supervisors WHERE staff_id = ? OR email = ?");
        $stmt->execute([$staff_id, $email]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Staff ID or Email already exists']);
            exit;
        }
        
        // Insert new supervisor
        $stmt = $db->prepare("INSERT INTO supervisors 
                             (staff_id, full_name, email, department, specialization, max_projects) 
                             VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$staff_id, $full_name, $email, $department, $specialization, $max_projects]);
        
        // Create user account
        $password = password_hash($staff_id, PASSWORD_DEFAULT); // Default password is staff ID
        $stmt = $db->prepare("INSERT INTO users 
                             (username, password, email, role, related_id) 
                             VALUES (?, ?, ?, 'Supervisor', LAST_INSERT_ID())");
        $stmt->execute([$staff_id, $password, $email]);
        
        echo json_encode(['status' => 'success', 'message' => 'Supervisor added successfully']);
        
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}