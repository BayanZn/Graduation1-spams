<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $message_id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];

    try {
        // Get message and verify ownership
        $stmt = $db->prepare("
            SELECT m.*, u.username as sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.id = ? AND m.receiver_id = ?
        ");
        $stmt->execute([$message_id, $user_id]);
        $message = $stmt->fetch();
        
        if (!$message) {
            echo json_encode(['status' => 'error', 'message' => 'Message not found or unauthorized']);
            exit;
        }
        
        // Format date
        $message->created_at = date('M j, Y H:i', strtotime($message->created_at));
        
        echo json_encode(['status' => 'success', 'data' => $message]);
        
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Message ID not provided']);
}