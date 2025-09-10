<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_id = (int)$_POST['submission_id'];
    $feedback = trim($_POST['feedback']);
    $status = trim($_POST['status']);
    $supervisor_id = $_SESSION['related_id'];

    // Validate inputs
    if (empty($submission_id) || empty($feedback) || empty($status)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }

    try {
        // Verify supervisor has access to this submission
        $stmt = $db->prepare("
            SELECT ps.id 
            FROM project_submissions ps
            JOIN students s ON ps.student_id = s.id
            JOIN project_supervision psv ON s.project_id = psv.project_id
            WHERE ps.id = ? AND psv.supervisor_id = ?
        ");
        $stmt->execute([$submission_id, $supervisor_id]);
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Submission not found or unauthorized']);
            exit;
        }
        
        // Update submission
        $stmt = $db->prepare("
            UPDATE project_submissions 
            SET feedback = ?, status = ?, reviewed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$feedback, $status, $submission_id]);
        
        // Notify student if approved/rejected
        if (in_array($status, ['Approved', 'Rejected'])) {
            // You could add email notification here
        }
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Review submitted successfully',
            'new_status' => $status
        ]);
        
    } catch(PDOException $e) {
        error_log("Review submission error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}