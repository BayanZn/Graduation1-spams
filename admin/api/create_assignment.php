<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentIds = $_POST['student_ids'] ?? [];
    $projectId = (int)$_POST['project_id'];
    $supervisorId = (int)$_POST['supervisor_id'];

    // Validate inputs
    if (empty($studentIds) || empty($projectId) || empty($supervisorId)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }

    try {
    $db->beginTransaction();

    $alreadyAssigned = [];
    $assignedCount = 0;

    // Get project and supervisor names for the message
    $projectStmt = $db->prepare("SELECT project_name FROM projects WHERE id = ?");
    $projectStmt->execute([$projectId]);
    $projectName = $projectStmt->fetchColumn();

    $supervisorStmt = $db->prepare("SELECT full_name FROM supervisors WHERE id = ?");
    $supervisorStmt->execute([$supervisorId]);
    $supervisorName = $supervisorStmt->fetchColumn();

    foreach ($studentIds as $studentId) {
        $studentId = (int)$studentId;

        // Check if student already has an assignment
        $stmt = $db->prepare("SELECT id FROM project_assignments WHERE student_id = ?");
        $stmt->execute([$studentId]);

        if ($stmt->rowCount() > 0) {
            $alreadyAssigned[] = $studentId;
            continue;
        }

        // Create assignment
        $db->prepare("
            INSERT INTO project_assignments (student_id, project_id, supervisor_id, status)
            VALUES (?, ?, ?, 'Proposed')
        ")->execute([$studentId, $projectId, $supervisorId]);

        // Update student's project_id
        $db->prepare("UPDATE students SET project_id = ? WHERE id = ?")->execute([$projectId, $studentId]);

        // Send notification to student
        $notifMsg = "You have been assigned to the project '<strong>$projectName</strong>' under Supervisor <strong>$supervisorName</strong>.";
        $db->prepare("
            INSERT INTO notifications (user_id, user_role, message)
            VALUES (?, 'Student', ?)
        ")->execute([$studentId, $notifMsg]);

        $assignedCount++;
    }

    // Update project status if at least one student was assigned
    if ($assignedCount > 0) {
        $db->prepare("UPDATE projects SET status = 'Assigned' WHERE id = ?")->execute([$projectId]);
    }

    // Notify supervisor once
    if ($assignedCount > 0) {
        $supervisorNotification = "You have been assigned as supervisor for the project '<strong>$projectName</strong>' with $assignedCount student(s).";
        $db->prepare("
            INSERT INTO notifications (user_id, user_role, message)
            VALUES (?, 'Supervisor', ?)
        ")->execute([$supervisorId, $supervisorNotification]);
    }

    $db->commit();

    if ($assignedCount === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No students were assigned. They may already have projects.'
        ]);
    } else {
        $message = "$assignedCount student(s) assigned successfully.";
        if (!empty($alreadyAssigned)) {
            $message .= " Some students were already assigned and skipped.";
        }
        echo json_encode(['status' => 'success', 'message' => $message]);
    }
} catch (PDOException $e) {
    $db->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
