<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'CSRF token validation failed']);
        exit;
    }
    
    $studentIds = $_POST['student_ids'] ?? [];
    $projectId = (int)$_POST['project_id'];
    $scheduledDate = $_POST['scheduled_date'];
    $venue = $_POST['venue'];
    $supervisorId = (int)$_POST['supervisor_id'];
    $defenseType = $_POST['defense_type'];
    $notes = $_POST['notes'] ?? '';
    
    // Validate inputs
    if (empty($studentIds) || empty($projectId) || empty($scheduledDate) || 
        empty($venue) || empty($supervisorId) || empty($defenseType)) {
        echo json_encode(['status' => 'error', 'message' => 'All required fields must be filled']);
        exit;
    }
    
    try {
        $db->beginTransaction();
        
        // Get project and supervisor names for notifications
        $projectStmt = $db->prepare("SELECT project_name FROM projects WHERE id = ?");
        $projectStmt->execute([$projectId]);
        $projectName = $projectStmt->fetchColumn();
        
        $supervisorStmt = $db->prepare("SELECT full_name FROM supervisors WHERE id = ?");
        $supervisorStmt->execute([$supervisorId]);
        $supervisorName = $supervisorStmt->fetchColumn();
        
        $scheduledCount = 0;
        $alreadyScheduled = [];
        
        foreach ($studentIds as $studentId) {
            $studentId = (int)$studentId;
            
            // Check if student already has a scheduled defense
            $checkStmt = $db->prepare("
                SELECT id FROM defense_schedule 
                WHERE student_id = ? AND status NOT IN ('Completed', 'Cancelled')
            ");
            $checkStmt->execute([$studentId]);
            
            if ($checkStmt->rowCount() > 0) {
                $alreadyScheduled[] = $studentId;
                continue;
            }
            
            // Schedule defense
            $stmt = $db->prepare("
                INSERT INTO defense_schedule 
                (student_id, project_id, scheduled_date, venue, supervisor_id, defense_type, notes, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Scheduled')
            ");
            $stmt->execute([$studentId, $projectId, $scheduledDate, $venue, $supervisorId, $defenseType, $notes]);
            
            // Send notification to student
            $notifMsg = "Your $defenseType defense for '<strong>$projectName</strong>' has been scheduled for " . 
                       date('M j, Y H:i', strtotime($scheduledDate)) . " at <strong>$venue</strong>. Supervisor: <strong>$supervisorName</strong>.";
            
            $notifStmt = $db->prepare("
                INSERT INTO notifications (user_id, user_role, message)
                VALUES (?, 'Student', ?)
            ");
            $notifStmt->execute([$studentId, $notifMsg]);
            
            $scheduledCount++;
        }
        
        // Notify supervisor
        if ($scheduledCount > 0) {
            $studentCountText = $scheduledCount > 1 ? "$scheduledCount students" : "a student";
            $supervisorNotification = "You have been scheduled to supervise the $defenseType defense for '$projectName' with $studentCountText on " . 
                                    date('M j, Y H:i', strtotime($scheduledDate)) . " at $venue.";
            
            $db->prepare("
                INSERT INTO notifications (user_id, user_role, message)
                VALUES (?, 'Supervisor', ?)
            ")->execute([$supervisorId, $supervisorNotification]);
        }
        
        $db->commit();
        
        if ($scheduledCount === 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No defenses were scheduled. Students may already have defenses scheduled.'
            ]);
        } else {
            $message = "$scheduledCount defense(s) scheduled successfully.";
            if (!empty($alreadyScheduled)) {
                $message .= " Some students were already scheduled and skipped.";
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