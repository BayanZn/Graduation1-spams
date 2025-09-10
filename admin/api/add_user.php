<?php
require_once '../../config/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }

    // Check if user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Username/Email already exists']);
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $db->prepare("
            INSERT INTO users (username, password, email, role)
            VALUES (?, ?, ?, ?)
        ")->execute([$username, $hashedPassword, $email, $role]);

        echo json_encode(['status' => 'success', 'message' => 'User added successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}