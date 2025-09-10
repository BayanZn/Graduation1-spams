<?php
require_once 'config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username']);
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);
  $role = trim($_POST['role']);
  $student_id = isset($_POST['student_id']) ? trim($_POST['student_id']) : null;
  $staff_id = isset($_POST['staff_id']) ? trim($_POST['staff_id']) : null;

    // Validate inputs
  if (empty($username) || empty($email) || empty($password) || empty($role)) {
    header("Location: register.php?error=empty_fields");
    exit();
  }

    // Check if username/email already exists
  $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
  $stmt->execute([$username, $email]);

  if ($stmt->rowCount() > 0) {
    header("Location: register.php?error=user_exists");
    exit();
  }

    // Hash password
  $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

  try {
    $db->beginTransaction();

        // Insert into users table
    $stmt = $db->prepare("
      INSERT INTO users (username, password, email, role, related_id)
      VALUES (?, ?, ?, ?, ?)
      ");

    $related_id = null;

        // If student, insert into students table first
    if ($role === 'Student' && $student_id) {
      $stmtStudent = $db->prepare("
        INSERT INTO students (student_id, full_name, email, program_id, year_level)
        VALUES (?, ?, ?, 1, '1st Year')
        ");
      $stmtStudent->execute([$student_id, $username, $email]);
      $related_id = $db->lastInsertId();
    }

        // If supervisor, insert into supervisors table first
    if ($role === 'Supervisor' && $staff_id) {
      $stmtSupervisor = $db->prepare("
        INSERT INTO supervisors (staff_id, full_name, email, department)
        VALUES (?, ?, ?, 'Computing')
        ");
      $stmtSupervisor->execute([$staff_id, $username, $email]);
      $related_id = $db->lastInsertId();
    }

        // Insert into users table
    $stmt->execute([$username, $hashedPassword, $email, $role, $related_id]);

    $db->commit();
    header("Location: login.php?success=registration_complete");
    exit();
  } catch (PDOException $e) {
    $db->rollBack();
    header("Location: register.php?error=server_error");
    exit();
  }
}
?>

<!DOCTYPE html>
<html lang="en">


<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <title>Register | <?php echo $projectName; ?></title>
  <!-- General CSS Files -->
  <link rel="stylesheet" href="assets/css/app.min.css">
  <link rel="stylesheet" href="assets/bundles/bootstrap-social/bootstrap-social.css">
  <!-- Template CSS -->
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <!-- Custom style CSS -->
  <link rel="stylesheet" href="assets/css/custom.css">
  <link rel='shortcut icon' type='image/x-icon' href='assets/img/favicon.ico' />
</head>

<body>
  <div class="loader"></div>
  <div id="app">
    <section class="section">
      <div class="container mt-5">
        <div class="row">
          <div class="col-12 col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-8 offset-lg-2 col-xl-8 offset-xl-2">
            <div class="card card-primary">
              <div class="card-header">
                <h4>Register</h4>
              </div>
              <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                  <?php
                  $errors = [
                    'empty_fields' => 'Please fill all fields',
                    'user_exists' => 'Username/Email already exists',
                    'server_error' => 'Server error. Try again.'
                  ];
                  echo $errors[$_GET['error']] ?? 'Registration failed';
                  ?>
                </div>
              <?php endif; ?>

              <?php if (isset($_GET['success']) && $_GET['success'] === 'registration_complete'): ?>
                <div class="alert alert-success">
                  Registration successful! <a href="login.php">Login here</a>.
                </div>
              <?php endif; ?>
              <div class="card-body">
                <form method="POST">
                  <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                  </div>
                  <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                  </div>
                  <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                  </div>
                  <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-control" id="role" name="role" required>
                      <option value="">Select Role</option>
                      <option value="Student">Student</option>
                      <option value="Supervisor">Supervisor</option>
                    </select>
                  </div>
                  <div class="mb-3" id="studentIdField" style="display: none;">
                    <label for="student_id" class="form-label">Student ID</label>
                    <input type="text" class="form-control" id="student_id" name="student_id">
                  </div>
                  <div class="mb-3" id="staffIdField" style="display: none;">
                    <label for="staff_id" class="form-label">Staff ID</label>
                    <input type="text" class="form-control" id="staff_id" name="staff_id">
                  </div>
                  <button type="submit" class="btn btn-primary w-100">Register</button>
                </form>
              </div>
              <div class="mb-4 text-muted text-center">
                Already Registered? <a href="login.php">Login</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
  <!-- General JS Scripts -->
  <script src="assets/js/app.min.js"></script>
  <!-- JS Libraies -->
  <!-- Page Specific JS File -->
  <!-- Template JS File -->
  <script src="assets/js/scripts.js"></script>
  <!-- Custom JS File -->
  <script src="assets/js/custom.js"></script>
  <script>
        // Show/hide ID fields based on role
    document.getElementById('role').addEventListener('change', function() {
      const role = this.value;
      document.getElementById('studentIdField').style.display = role === 'Student' ? 'block' : 'none';
      document.getElementById('staffIdField').style.display = role === 'Supervisor' ? 'block' : 'none';
    });
  </script>
</body>


</html>