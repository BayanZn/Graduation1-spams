<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Authorization check
    session_start();
    if (!in_array($_SESSION['user_role'], ['Admin', 'Coordinator'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $defenseIds = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];
    
    if (empty($defenseIds)) {
        echo json_encode(['status' => 'error', 'message' => 'No defense IDs provided']);
        exit;
    }

    try {
        $db->beginTransaction();
        
        // Prepare statement
        $stmt = $db->prepare("
            UPDATE defense_schedule 
            SET status = 'Cancelled', cancelled_at = NOW(), cancelled_by = ?
            WHERE id = ? AND status NOT IN ('Completed', 'Cancelled')
        ");
        
        $cancelledCount = 0;
        foreach ($defenseIds as $id) {
            $stmt->execute([$_SESSION['user_id'], $id]);
            $cancelledCount += $stmt->rowCount();
        }
        
        $db->commit();
        
        if ($cancelledCount > 0) {
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => $cancelledCount . ' defense(s) cancelled successfully'
            ];
        } else {
            $_SESSION['alert'] = [
                'type' => 'warning',
                'message' => 'No defenses were cancelled (they may already be completed or cancelled)'
            ];
        }
        
        header("Location: ../defenses.php");
        exit;
        
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Error cancelling defense: ' . $e->getMessage()
        ];
        header("Location: ../defenses.php");
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}