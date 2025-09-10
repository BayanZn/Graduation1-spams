<?php
require_once '../config/db_connect.php';
require_once '../includes/messaging_functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = (int)$_POST['receiver_id'];
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    
    // Validate inputs
    if (empty($receiver_id) {
        echo json_encode(['status' => 'error', 'message' => 'Recipient is required']);
        exit;
    }
    
    if (empty($subject)) {
        echo json_encode(['status' => 'error', 'message' => 'Subject is required']);
        exit;
    }
    
    if (empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Message is required']);
        exit;
    }
    
    // Check if receiver exists
    try {
        $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$receiver_id]);
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Recipient not found']);
            exit;
        }
        
        // Send the message
        $message_id = sendMessage($sender_id, $receiver_id, $subject, $message, $parent_id);
        
        if ($message_id) {
            echo json_encode([
                'status' => 'success', 
                'message' => 'Message sent successfully',
                'message_id' => $message_id
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to send message']);
        }
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}