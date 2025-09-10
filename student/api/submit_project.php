<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_SESSION['related_id'];
    $is_resubmission = isset($_POST['is_resubmission']) ? 1 : 0;
    
    // Check if student has a project
    $stmt = $db->prepare("SELECT project_id FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if (!$student || !$student->project_id) {
        echo json_encode(['status' => 'error', 'message' => 'No project assigned']);
        exit;
    }
    
    // File upload handling
    $uploadDir = '../../uploads/projects/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $allowedTypes = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    if (!isset($_FILES['project_file']) || $_FILES['project_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'File upload error']);
        exit;
    }
    
    $file = $_FILES['project_file'];
    $fileType = mime_content_type($file['tmp_name']);
    $fileSize = $file['size'];
    
    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(['status' => 'error', 'message' => 'Only PDF and DOCX files are allowed']);
        exit;
    }
    
    if ($fileSize > $maxSize) {
        echo json_encode(['status' => 'error', 'message' => 'File size exceeds 10MB limit']);
        exit;
    }
    
    $fileName = 'project_' . $student_id . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $filePath = $uploadDir . $fileName;
    
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save file']);
        exit;
    }
    
    try {
        if ($is_resubmission) {
            // Update existing submission
            $stmt = $db->prepare("UPDATE project_submissions 
                                 SET file_path = ?, comments = ?, response = ?, status = 'Submitted', submitted_at = NOW()
                                 WHERE student_id = ?");
            $stmt->execute([
                $filePath, 
                $_POST['comments'] ?? null, 
                $_POST['response'],
                $student_id
            ]);
        } else {
            // Create new submission
            $stmt = $db->prepare("INSERT INTO project_submissions 
                                 (student_id, project_id, file_path, comments, status) 
                                 VALUES (?, ?, ?, ?, 'Submitted')");
            $stmt->execute([
                $student_id, 
                $student->project_id, 
                $filePath, 
                $_POST['comments'] ?? null
            ]);
        }
        
        echo json_encode(['status' => 'success', 'message' => 'Project submitted successfully']);
        
    } catch(PDOException $e) {
        // Delete uploaded file if database operation failed
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}