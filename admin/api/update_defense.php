<?php
require_once '../../config/db_connect.php';
require_once '../../includes/auth_check.php';
session_start();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Invalid request method'];
    header("Location: ../defenses.php");
    exit;
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Invalid CSRF token'];
    header("Location: ../defenses.php");
    exit;
}

// Authorization check
if (!in_array($_SESSION['user_role'], ['Admin', 'Coordinator'])) {
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Unauthorized'];
    header("Location: ../defenses.php");
    exit;
}

// Get and validate input
$defenseId = (int)($_POST['defense_id'] ?? 0);
$scheduledDate = $_POST['scheduled_date'] ?? '';
$venue = trim($_POST['venue'] ?? '');
$supervisorId = (int)($_POST['supervisor_id'] ?? 0);
$panelChairId = (int)($_POST['panel_chair_id'] ?? 0);
$status = $_POST['status'] ?? '';
$notes = trim($_POST['notes'] ?? '');

// Validate inputs
$errors = [];
if ($defenseId <= 0) $errors[] = 'Invalid defense ID';
if (empty($scheduledDate)) $errors[] = 'Date and time is required';
if (empty($venue)) $errors[] = 'Venue is required';
if (strlen($venue) > 255) $errors[] = 'Venue name is too long (max 255 characters)';
if ($supervisorId <= 0) $errors[] = 'Supervisor is required';
if (!in_array($status, ['Scheduled', 'Pending', 'Completed', 'Cancelled', 'Postponed'])) {
    $errors[] = 'Invalid status selected';
}

if (!empty($errors)) {
    $_SESSION['alert'] = ['type' => 'danger', 'message' => implode(', ', $errors)];
    header("Location: ../edit_defense.php?id=$defenseId");
    exit;
}

try {
    $db->beginTransaction();

    // Check if supervisor exists
    $stmt = $db->prepare("SELECT id FROM supervisors WHERE id = ?");
    $stmt->execute([$supervisorId]);
    if (!$stmt->fetch()) {
        throw new Exception('Selected supervisor does not exist');
    }

    // Check if panel chair exists (if provided)
    if ($panelChairId > 0) {
        $stmt = $db->prepare("SELECT id FROM supervisors WHERE id = ?");
        $stmt->execute([$panelChairId]);
        if (!$stmt->fetch()) {
            throw new Exception('Selected panel chair does not exist');
        }
    }

    // Update defense schedule
    $stmt = $db->prepare("
        UPDATE defense_schedule 
        SET scheduled_date = ?,
            venue = ?,
            supervisor_id = ?,
            panel_chair_id = ?,
            status = ?,
            notes = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $params = [
        $scheduledDate, 
        $venue, 
        $supervisorId,
        $panelChairId > 0 ? $panelChairId : null,
        $status,
        $notes,
        $defenseId
    ];
    
    $stmt->execute($params);

    $db->commit();

    $_SESSION['alert'] = [
        'type' => 'success',
        'message' => 'Defense schedule updated successfully'
    ];
    header("Location: ../defenses.php");
    exit;

} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['alert'] = ['type' => 'danger', 'message' => $e->getMessage()];
    header("Location: ../edit_defense.php?id=$defenseId");
    exit;
}