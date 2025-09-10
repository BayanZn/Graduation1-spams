<?php 
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../includes/auth_check.php';
require_once '../config/db_connect.php';
require_once '../includes/messaging_functions.php';
ob_end_flush(); 
?>
<!DOCTYPE html>
<html lang="en">
<head> 
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <title><?= ($projectName ?? 'My Website') . ' | ' . ($title ?? 'Untitled'); ?></title>

  <!-- General CSS Files -->
  <link rel="stylesheet" href="../assets/css/app.min.css">
  <link rel="stylesheet" href="../assets/bundles/chocolat/dist/css/chocolat.css">
  <link rel="stylesheet" href="../assets/bundles/datatables/datatables.min.css">
  <link rel="stylesheet" href="../assets/bundles/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/bundles/bootstrap-social/bootstrap-social.css">
  <link rel="stylesheet" href="../assets/bundles/summernote/summernote-bs4.css">
  <!-- Template CSS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="../assets/css/components.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css" rel="stylesheet" />
  
  <link rel="stylesheet" href="../assets/css/style.css">
  
  <!-- Custom style CSS -->
  <link rel="stylesheet" href="../assets/css/custom.css">
  <link rel='shortcut icon' type='image/x-icon' href='../assets/img/favicon.ico' />
  <style>
    /* General navbar styles */
.main-navbar {
  background-color: #2C3E50; /* dark blue-gray background */
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  padding: 0.75rem 1.5rem;
  color: #fff;
  z-index: 1030;
}

/* Navbar items (left side) */
.navbar-nav .nav-link {
  color: #ecf0f1;
  transition: color 0.3s ease;
}

.navbar-nav .nav-link:hover,
.navbar-nav .nav-link:focus {
  color: #1abc9c; /* teal on hover */
}

/* Icons */
.navbar-nav i {
  font-size: 18px;
  vertical-align: middle;
}

/* Search box */
.search-element {
  position: relative;
}

.search-element input.form-control {
  width: 200px;
  border-radius: 20px;
  padding-left: 15px;
  padding-right: 35px;
  border: none;
  background: #ecf0f1;
  color: #2c3e50;
}

.search-element input::placeholder {
  color: #7f8c8d;
}

.search-element .btn {
  position: absolute;
  top: 50%;
  right: 5px;
  transform: translateY(-50%);
  background: transparent;
  border: none;
  color: #34495e;
}

/* Message and notification badges */
.headerBadge1 {
  background: #e74c3c;
  color: white;
  padding: 2px 6px;
  border-radius: 10px;
  font-size: 12px;
  position: absolute;
  top: 8px;
  right: 8px;
}

/* Dropdowns */
.dropdown-menu {
  background-color: #fff;
  color: #2c3e50;
  border-radius: 5px;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
  min-width: 300px;
  overflow: hidden;
}

.dropdown-item {
  padding: 12px 20px;
  color: #2c3e50;
  transition: background 0.3s ease;
}

.dropdown-item:hover {
  background-color: #f1f1f1;
}

.dropdown-item .message-user {
  font-weight: bold;
}

.dropdown-header,
.dropdown-footer {
  padding: 10px 20px;
  background-color: #f9f9f9;
  font-weight: bold;
}

.dropdown-footer a {
  color: #3498db;
  text-decoration: none;
}

.user-img-radious-style {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  object-fit: cover;
  margin-right: 8px;
}

/* User dropdown */
.nav-link-user {
  display: flex;
  align-items: center;
  color: #ecf0f1;
}

    .messages-sidebar {
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .messages-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
    }

    .messages-search {
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
    }

    .messages-list {
        flex: 1;
        overflow-y: auto;
    }

    .message-item {
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .message-item:hover {
        background-color: #f8f9fa;
    }

    .message-item.unread {
        background-color: #e8f4fd;
        font-weight: 500;
    }

    .message-sender {
        font-weight: 600;
        margin-bottom: 3px;
    }

    .message-subject {
        font-weight: 500;
        margin-bottom: 3px;
    }

    .message-preview {
        color: #6c757d;
        font-size: 0.85rem;
    }

    .no-messages {
        text-align: center;
        padding: 30px 15px;
        color: #6c757d;
    }

    .no-messages i {
        font-size: 2rem;
        margin-bottom: 10px;
        color: #adb5bd;
    }

    .message-thread {
        max-height: 600px;
        overflow-y: auto;
        padding: 15px;
    }

    .message {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        position: relative;
    }

    .message.sent {
        background-color: #e3f2fd;
        margin-left: 50px;
    }

    .message.received {
        background-color: #f8f9fa;
        margin-right: 50px;
    }

    .message-header {
        margin-bottom: 10px;
        padding-bottom: 5px;
        border-bottom: 1px solid rgba(0,0,0,0.1);
    }

    .message-subject {
        font-weight: 600;
        margin-bottom: 10px;
    }

    .message-body {
        white-space: pre-wrap;
    }

    .message-actions {
        margin-top: 15px;
        text-align: right;
    }

    .empty-messages {
        text-align: center;
        padding: 50px 20px;
        color: #6c757d;
    }

    .empty-messages i {
        font-size: 3rem;
        margin-bottom: 15px;
        color: #adb5bd;
    }

    .empty-messages h5 {
        margin-bottom: 10px;
    }

    .reply-form {
        border-top: 1px solid #eee;
        padding-top: 20px;
    }

    .dropdown-list-message .dropdown-item {
        white-space: normal;
        padding: 12px 20px;
    }

    .dropdown-item-unread .dropdown-item-desc {
        color: #343a40;
        font-weight: 500;
    }

    .avatar-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
</style>
</head>

<body>

  <!-- <div class="loader"></div> -->
  <div id="app">
