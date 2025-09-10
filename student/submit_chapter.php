<?php
require_once '../config/db_connect.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

$student_id = $_SESSION['related_id'] ?? null;

// Validate required fields
if (!$student_id || empty($_POST['chapter_name']) || !isset($_FILES['project_file'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

// Check if student has a project assigned
$stmt = $db->prepare("SELECT project_id FROM students WHERE id = ?");
$stmt->execute([$student_id]);
if (!$stmt->fetchColumn()) {
    echo json_encode(['status' => 'error', 'message' => 'No project assigned']);
    exit;
}

$chapter = trim($_POST['chapter_name']);
$comments = $_POST['comments'] ?? '';

// File validation
$file = $_FILES['project_file'];
$allowedTypes = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$allowedExtensions = ['pdf', 'docx'];
$maxSize = 10 * 1024 * 1024; // 10MB

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'File upload error: ' . $file['error']]);
    exit;
}

// Verify file type and extension
$fileType = mime_content_type($file['tmp_name']);
$fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($fileType, $allowedTypes) || !in_array($fileExt, $allowedExtensions)) {
    echo json_encode(['status' => 'error', 'message' => 'Only PDF and DOCX files are allowed']);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(['status' => 'error', 'message' => 'File size exceeds 10MB limit']);
    exit;
}

// Prepare upload directory
$uploadDir = '../uploads/chapters/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create upload directory']);
        exit;
    }
}

// Sanitize file name
$safeChapter = preg_replace('/[^a-zA-Z0-9]/', '_', $chapter);
$fileName = 'chapter_' . $student_id . '_' . $safeChapter . '_' . time() . '.' . $fileExt;
$filePath = $uploadDir . $fileName;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save uploaded file']);
    exit;
}

try {
    // Check for existing submission
    $stmt = $db->prepare("SELECT id FROM project_chapter_submissions WHERE student_id = ? AND chapter_name = ?");
    $stmt->execute([$student_id, $chapter]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        // Update existing submission
        $stmt = $db->prepare("
            UPDATE project_chapter_submissions 
            SET file_path = ?, comments = ?, status = 'Pending', updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$filePath, $comments, $exists]);
    } else {
        // Insert new submission
        $stmt = $db->prepare("
            INSERT INTO project_chapter_submissions 
            (student_id, chapter_name, file_path, comments) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$student_id, $chapter, $filePath, $comments]);
    }

    echo json_encode([
        'status' => 'success', 
        'message' => 'Chapter submitted successfully'
    ]);
} catch (PDOException $e) {
    // Clean up uploaded file if database operation failed
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    error_log("Chapter submission error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error occurred. Please try again.'
    ]);
}