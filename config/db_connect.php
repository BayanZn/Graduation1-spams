<?php
if (session_status() === PHP_SESSION_NONE) { 
    session_start();
}

$projectName = "Projects Management & Allocation System";
$projectShortName = "SPMAS";
$developerName = "";
$version = "1.0";
$lastUpdated = "";

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'project_allocation_system'); 

define('BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/project-allocation-system/");
define('ADMIN_URL', BASE_URL . 'admin/');
define('SUPERVISOR_URL', BASE_URL . 'supervisor/');
define('STUDENT_URL', BASE_URL . 'student/');



try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
} catch (PDOException $e) {
    die('
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Database Connection Error</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f8f9fa;
                color: #343a40;
                margin: 0;
                padding: 20px;
            }
            .error-container {
                max-width: 600px;
                margin: 50px auto;
                padding: 30px;
                background-color: #ffffff;
                border-radius: 8px;
                box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            }
            h1 {
                color: #dc3545;
                margin-bottom: 20px;
            }
            .details {
                background-color: #f1f1f1;
                padding: 15px;
                border-radius: 4px;
                font-size: 0.95em;
            }
            .footer {
                margin-top: 30px;
                font-size: 0.9em;
                color: #6c757d;
                border-top: 1px solid #dee2e6;
                padding-top: 15px;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1>Database Connection Error</h1>
            <p>Failed to connect to the database. Please try again later.</p>
            <div class="details">
                <strong>Technical Details:</strong><br>
                ' . htmlspecialchars($e->getMessage()) . '
            </div>
            <div class="footer">
                <p><strong>' . htmlspecialchars($projectName) . ' (' . htmlspecialchars($projectShortName) . ')</strong></p>
                <p>Version: ' . htmlspecialchars($version) . ' | Last Updated: ' . htmlspecialchars($lastUpdated) . '</p>
            </div>
        </div>
    </body>
    </html>
    ');
}
