<?php
session_start();
require_once '../../config/db_connect.php';
require_once '../../includes/messaging_functions.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['message_status'] = ['type' => 'error', 'text' => 'Unauthorized access.'];
    header('Location: ../messages.php');
    exit;
}

$sender_id = $_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

if (!$receiver_id || $subject === '' || $message === '') {
    $_SESSION['message_status'] = ['type' => 'error', 'text' => 'All fields are required.'];
    header('Location: ../messages.php');
    exit;
}

try {
    $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$receiver_id]);

    if ($stmt->rowCount() === 0) {
        $_SESSION['message_status'] = ['type' => 'error', 'text' => 'Recipient does not exist.'];
        header('Location: ../messages.php');
        exit;
    }

    $query = "INSERT INTO messages (sender_id, receiver_id, subject, message, parent_id, created_at)
              VALUES (:sender_id, :receiver_id, :subject, :message, :parent_id, NOW())";

    $stmt = $db->prepare($query);
    $success = $stmt->execute([
        ':sender_id'   => $sender_id,
        ':receiver_id' => $receiver_id,
        ':subject'     => $subject,
        ':message'     => $message,
        ':parent_id'   => $parent_id ?: null
    ]);

    if ($success) {
        $_SESSION['message_status'] = ['type' => 'success', 'text' => 'Message sent successfully.'];
    } else {
        $_SESSION['message_status'] = ['type' => 'error', 'text' => 'Failed to send message. Try again.'];
    }

} catch (PDOException $e) {
    $_SESSION['message_status'] = ['type' => 'error', 'text' => 'Database error: ' . $e->getMessage()];
}

header('Location: ../messages.php');
exit;
