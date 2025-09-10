<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $defenseId = (int)$_GET['id'];

    try {
        $db->beginTransaction();

        // Delete panel assignments
        $db->prepare("DELETE FROM defense_panel WHERE defense_id = ?")->execute([$defenseId]);

        // Delete defense
        $db->prepare("DELETE FROM defense_schedule WHERE id = ?")->execute([$defenseId]);

        $db->commit();
        echo json_encode(['status' => 'success', 'message' => 'Defense cancelled successfully']);
    } catch (PDOException $e) {
        $db->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}