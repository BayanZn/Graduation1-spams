<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = (int)$_POST['student_id'];
    $projectId = $db->query("SELECT project_id FROM students WHERE id = $studentId")->fetchColumn();
    $scheduledDate = $_POST['scheduled_date'];
    $venueId = (int)$_POST['venue_id'];
    $supervisorId = (int)$_POST['supervisor_id'];
    $panelMembers = $_POST['panel_members'];
    $notes = trim($_POST['notes'] ?? '');

    // Validate inputs
    if (empty($studentId) || empty($projectId) || empty($scheduledDate) || 
       empty($venueId) || empty($supervisorId) || count($panelMembers) < 2) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required with at least 2 panel members']);
        exit;
    }

    // Check venue availability
    $venueBooked = $db->query("
        SELECT id FROM defense_schedule 
        WHERE venue_id = $venueId 
        AND scheduled_date BETWEEN DATE_SUB('$scheduledDate', INTERVAL 2 HOUR) 
        AND DATE_ADD('$scheduledDate', INTERVAL 2 HOUR)
    ")->rowCount();

    if ($venueBooked > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Venue already booked for this time slot']);
        exit;
    }

    // Check panel availability
    $conflicts = [];
    foreach ($panelMembers as $memberId) {
        $conflict = $db->query("
            SELECT ds.id FROM defense_schedule ds
            JOIN defense_panel dp ON ds.id = dp.defense_id
            WHERE dp.supervisor_id = $memberId
            AND ds.scheduled_date BETWEEN DATE_SUB('$scheduledDate', INTERVAL 2 HOUR) 
            AND DATE_ADD('$scheduledDate', INTERVAL 2 HOUR)
        ")->fetchColumn();

        if ($conflict) {
            $supervisorName = $db->query("SELECT full_name FROM supervisors WHERE id = $memberId")->fetchColumn();
            $conflicts[] = $supervisorName;
        }
    }

    if (!empty($conflicts)) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Panel members have scheduling conflicts: ' . implode(', ', $conflicts)
        ]);
        exit;
    }

    try {
        $db->beginTransaction();

        // Schedule defense
        $db->prepare("
            INSERT INTO defense_schedule 
            (student_id, project_id, scheduled_date, venue_id, supervisor_id, notes, status)
            VALUES (?, ?, ?, ?, ?, ?, 'Scheduled')
        ")->execute([$studentId, $projectId, $scheduledDate, $venueId, $supervisorId, $notes]);

        $defenseId = $db->lastInsertId();

        // Add panel members
        foreach ($panelMembers as $memberId) {
            $db->prepare("
                INSERT INTO defense_panel (defense_id, supervisor_id)
                VALUES (?, ?)
            ")->execute([$defenseId, $memberId]);
        }

        $db->commit();
        echo json_encode(['status' => 'success', 'message' => 'Defense scheduled successfully']);
    } catch (PDOException $e) {
        $db->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}