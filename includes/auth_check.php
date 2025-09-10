<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Verify user still exists in database
require_once __DIR__ . '/../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

try {
    $stmt = $db->prepare("SELECT u.*, 
                         CASE 
                             WHEN u.role = 'Student' THEN s.full_name
                             WHEN u.role = 'Supervisor' THEN sp.full_name
                             ELSE 'Administrator'
                         END as name
                         FROM users u
                         LEFT JOIN students s ON u.related_id = s.id AND u.role = 'Student'
                         LEFT JOIN supervisors sp ON u.related_id = sp.id AND u.role = 'Supervisor'
                         WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        session_unset();
        session_destroy();
        header("Location: ../login.php?error=session_expired");
        exit();
    }

    // Regenerate session ID periodically
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }

    // Store user data in session
    $_SESSION['user_role'] = $user->role;
    $_SESSION['user_name'] = $user->name;

} catch(PDOException $e) {
    die("Authentication error: " . $e->getMessage());
}
