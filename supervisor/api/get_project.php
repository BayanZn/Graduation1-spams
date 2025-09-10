<?php
require_once '../../config/db_connect.php';
require_once '../../includes/auth_check.php';
header('Content-Type: application/json');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Check if project ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Project ID is required']);
    exit;
}

$projectId = (int)$_GET['id'];

try {
    // Verify the supervisor has access to this project
    $stmt = $db->prepare("
        SELECT ps.supervisor_id 
        FROM project_supervision ps
        WHERE ps.project_id = ? AND ps.supervisor_id = ?
        LIMIT 1
    ");
    $stmt->execute([$projectId, $_SESSION['related_id']]);
    
    if (!$stmt->fetch()) {
        throw new Exception('You are not authorized to view this project');
    }

    // Get basic project details
    $stmt = $db->prepare("
        SELECT p.id, p.project_name, p.description, p.case_study, p.level
        FROM projects p
        WHERE p.id = ?
        LIMIT 1
    ");
    $stmt->execute([$projectId]);
    $project = $stmt->fetch();

    if (!$project) {
        throw new Exception('Project not found');
    }

    // Get student assignment if exists
    $stmt = $db->prepare("
        SELECT s.id as student_id, s.full_name, s.email
        FROM students s
        WHERE s.project_id = ?
        LIMIT 1
    ");
    $stmt->execute([$projectId]);
    $student = $stmt->fetch();

    // Get defense info if exists
    $stmt = $db->prepare("
        SELECT status, scheduled_date 
        FROM defense_schedule
        WHERE project_id = ?
        LIMIT 1
    ");
    $stmt->execute([$projectId]);
    $defense = $stmt->fetch();

    // Get submission info if exists
    $submission = null;
    $files = [];
    if ($student) {
        $stmt = $db->prepare("
            SELECT status, feedback, submitted_at, reviewed_at, file_path
            FROM project_submissions
            WHERE project_id = ? AND student_id = ?
            ORDER BY submitted_at DESC
            LIMIT 1
        ");
        $stmt->execute([$projectId, $student->student_id]);
        $submission = $stmt->fetch();

        if ($submission && $submission->file_path) {
            $files = [['path' => $submission->file_path, 'submitted_at' => $submission->submitted_at]];
        }
    }

    // Prepare response
    $response = [
        'status' => 'success',
        'data' => [
            'project' => [
                'id' => $project->id,
                'name' => $project->project_name,
                'description' => $project->description,
                'case_study' => $project->case_study,
                'level' => $project->level
            ],
            'student' => $student ? [
                'id' => $student->student_id,
                'name' => $student->full_name,
                'email' => $student->email
            ] : null,
            'defense' => $defense ? [
                'status' => $defense->status,
                'scheduled_date' => $defense->scheduled_date
            ] : null,
            'submission' => $submission ? [
                'status' => $submission->status,
                'feedback' => $submission->feedback,
                'submitted_at' => $submission->submitted_at,
                'reviewed_at' => $submission->reviewed_at
            ] : null,
            'files' => $files
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage(),
        'debug' => ['project_id' => $projectId, 'supervisor_id' => $_SESSION['related_id']]
    ]);
}