<?php
// require_once '../config/db_connect.php';

function sendMessage($sender_id, $receiver_id, $subject, $message, $parent_id = null) {
    global $db;
    try {
        $query = "INSERT INTO messages (sender_id, receiver_id, subject, message, parent_id, created_at)
                  VALUES (:sender_id, :receiver_id, :subject, :message, :parent_id, NOW())";

        $stmt = $db->prepare($query);

        return $stmt->execute([
            ':sender_id' => $sender_id,
            ':receiver_id' => $receiver_id,
            ':subject' => $subject,
            ':message' => $message,
            ':parent_id' => $parent_id ? $parent_id : null
        ]);
    } catch (PDOException $e) {
        error_log("sendMessage() failed: " . $e->getMessage());
        return false;
    }
}


function getMessageThread($message_id) {
    global $db;
    try {
        $user_id = $_SESSION['user_id'];
        $stmtCheck = $db->prepare("SELECT * FROM messages WHERE id = ? AND (sender_id = ? OR receiver_id = ?)");
        $stmtCheck->execute([$message_id, $user_id, $user_id]);

        if ($stmtCheck->rowCount() === 0) {
            return false;
        }

        $stmt = $db->prepare("
            WITH RECURSIVE thread_path AS (
                SELECT id, parent_id, 1 as depth
                FROM messages
                WHERE id = ?

                UNION ALL

                SELECT m.id, m.parent_id, tp.depth + 1
                FROM messages m
                JOIN thread_path tp ON m.parent_id = tp.id
            )
            SELECT m.*, 
                   sender.username as sender_username, 
                   receiver.username as receiver_username
            FROM thread_path tp
            JOIN messages m ON tp.id = m.id
            JOIN users sender ON m.sender_id = sender.id
            JOIN users receiver ON m.receiver_id = receiver.id
            ORDER BY tp.depth DESC
        ");
        $stmt->execute([$message_id]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    } catch (PDOException $e) {
        error_log("Failed to get message thread: " . $e->getMessage());
        return false;
    }
}

// ... Other functions unchanged but add PDO::FETCH_OBJ fetch style if needed

function getUserMessages($user_id, $limit = null, $unread_only = false) {
    global $db;

    try {
        $sql = "SELECT m.*, 
                       sender.username as sender_username,
                       sender.role as sender_role,
                       receiver.username as receiver_username
                FROM messages m
                JOIN users sender ON m.sender_id = sender.id
                JOIN users receiver ON m.receiver_id = receiver.id
                WHERE m.receiver_id = ?";

        if ($unread_only) {
            $sql .= " AND m.is_read = 0";
        }

        $sql .= " ORDER BY m.created_at DESC";

        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to get user messages: " . $e->getMessage());
        return false;
    }
}

function markAsRead($message_id) {
    global $db;

    try {
        $stmt = $db->prepare("
            WITH RECURSIVE thread_path AS (
                SELECT id, parent_id FROM messages WHERE id = ?

                UNION ALL

                SELECT m.id, m.parent_id FROM messages m
                JOIN thread_path tp ON m.parent_id = tp.id
            )
            SELECT id FROM thread_path
        ");
        $stmt->execute([$message_id]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($ids) {
            $inQuery = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("UPDATE messages SET is_read = 1 WHERE id IN ($inQuery)");
            return $stmt->execute($ids);
        }

        return false;
    } catch (PDOException $e) {
        error_log("Failed to mark message as read: " . $e->getMessage());
        return false;
    }
}

function getUnreadCount($user_id) {
    global $db;

    try {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return $stmt->fetch()->count;
    } catch (PDOException $e) {
        error_log("Failed to get unread count: " . $e->getMessage());
        return 0;
    }
}


function getSentMessages($user_id, $limit = null, $page = 1) {
    global $db;
    try {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT m.*, r.username AS receiver_username
                FROM messages m
                JOIN users r ON m.receiver_id = r.id
                WHERE m.sender_id = ? AND deleted_by_sender = 0
                ORDER BY m.created_at DESC";
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
            $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(3, (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $db->prepare($sql);
            $stmt->execute([$user_id]);
        }
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    } catch (PDOException $e) {
        error_log("getSentMessages() failed: " . $e->getMessage());
        return false;
    }
}

function deleteMessageForUser($message_id, $user_role) {
    global $db;
    try {
        if ($user_role === 'sender') {
            $stmt = $db->prepare("UPDATE messages SET deleted_by_sender = 1 WHERE id = ?");
        } else { // receiver
            $stmt = $db->prepare("UPDATE messages SET deleted_by_receiver = 1 WHERE id = ?");
        }
        return $stmt->execute([$message_id]);
    } catch (PDOException $e) {
        error_log("deleteMessageForUser() failed: " . $e->getMessage());
        return false;
    }
}
