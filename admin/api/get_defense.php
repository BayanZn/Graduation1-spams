<?php
require_once '../../config/db_connect.php';
require_once '../../includes/auth_check.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Defense ID required']);
    exit;
}

$defenseId = (int)$_GET['id'];

try {
    // Get defense details
    $stmt = $db->prepare("
        SELECT d.*, s.full_name as student_name, p.project_name,
               v.name as venue_name, sp.full_name as supervisor_name
        FROM defense_schedule d
        JOIN students s ON d.student_id = s.id
        JOIN projects p ON d.project_id = p.id
        JOIN venues v ON d.venue_id = v.id
        JOIN supervisors sp ON d.supervisor_id = sp.id
        WHERE d.id = ?
    ");
    $stmt->execute([$defenseId]);
    $defense = $stmt->fetch();

    if (!$defense) {
        throw new Exception('Defense not found');
    }

    // Get panel members
    $stmt = $db->prepare("
        SELECT s.id, s.full_name 
        FROM defense_panel dp
        JOIN supervisors s ON dp.supervisor_id = s.id
        WHERE dp.defense_id = ?
    ");
    $stmt->execute([$defenseId]);
    $panelMembers = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'data' => [
            'defense' => $defense,
            'panel_members' => $panelMembers
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}