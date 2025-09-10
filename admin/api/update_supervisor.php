<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $staff_id = trim($_POST['staff_id']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $department = trim($_POST['department']);
    $specialization = trim($_POST['specialization']);
    $max_projects = (int)$_POST['max_projects'];

    // Validate inputs
    if (empty($staff_id) || empty($full_name) || empty($email) || empty($department) || empty($max_projects)) {
        echo json_encode(['status' => 'error', 'message' => 'Required fields are missing']);
        exit;
    }

    try {
        // Check if staff ID or email already exists for another supervisor
        $stmt = $db->prepare("SELECT id FROM supervisors WHERE (staff_id = ? OR email = ?) AND id != ?");
        $stmt->execute([$staff_id, $email, $id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Staff ID or Email already exists']);
            exit;
        }
        
        // Update supervisor
        $stmt = $db->prepare("
            UPDATE supervisors 
            SET staff_id = ?, full_name = ?, email = ?, department = ?, 
                specialization = ?, max_projects = ?
            WHERE id = ?
        ");
        $stmt->execute([$staff_id, $full_name, $email, $department, $specialization, $max_projects, $id]);
        
        // Update user account if needed
        $stmt = $db->prepare("UPDATE users SET username = ?, email = ? WHERE related_id = ? AND role = 'Supervisor'");
        $stmt->execute([$staff_id, $email, $id]);
        
        echo json_encode(['status' => 'success', 'message' => 'Supervisor updated successfully']);
        
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}