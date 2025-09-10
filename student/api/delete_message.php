<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $message_id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];

    try {
        // Verify message belongs to user
        $stmt = $db->prepare("SELECT id FROM messages WHERE id = ? AND receiver_id = ?");
        $stmt->execute([$message_id, $user_id]);
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Message not found or unauthorized']);
            exit;
        }
        
        // Delete message
        $stmt = $db->prepare("DELETE FROM messages WHERE id = ?");
        $stmt->execute([$message_id]);
        
        echo json_encode(['status' => 'success', 'message' => 'Message deleted successfully']);
        
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Message ID not provided']);
}