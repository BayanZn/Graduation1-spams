<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Submission ID required']);
    exit;
}

$submission_id = (int)$_GET['id'];
$supervisor_id = $_SESSION['related_id'];

try {
    // Verify supervisor has access to this submission
    $stmt = $db->prepare("
        SELECT 
            ps.*, 
            pr.project_name, 
            s.full_name as student_name
        FROM project_submissions ps
        JOIN students s ON ps.student_id = s.id
        JOIN projects pr ON ps.project_id = pr.id
        JOIN project_supervision psv ON pr.id = psv.project_id
        WHERE ps.id = ? AND psv.supervisor_id = ?
    ");
    $stmt->execute([$submission_id, $supervisor_id]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Submission not found or unauthorized']);
        exit;
    }
    
    $submission = $stmt->fetch();
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'id' => $submission->id,
            'project_name' => $submission->project_name,
            'student_name' => $submission->student_name,
            'file_path' => $submission->file_path,
            'comments' => $submission->comments,
            'response' => $submission->response,
            'status' => $submission->status,
            'feedback' => $submission->feedback,
            'submitted_at' => $submission->submitted_at
        ]
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}