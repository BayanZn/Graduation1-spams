<?php
require_once 'config/db_connect.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['user_role'] === 'Admin' ? 'admin/dashboard.php' : strtolower($_SESSION['user_role']) . '/dashboard.php'));
    exit();
}

// Handle login attempt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validate inputs
    if (empty($username) || empty($password)) {
        header("Location: login.php?error=empty_fields");
        exit();
    }


    try {
        // Fetch user with role-specific data
        $stmt = $db->prepare("
            SELECT u.*, 
                   CASE 
                       WHEN u.role = 'Student' THEN s.full_name
                       WHEN u.role = 'Supervisor' THEN sp.full_name
                       ELSE 'Administrator'
                   END as name,
                   CASE 
                       WHEN u.role = 'Student' THEN s.id
                       WHEN u.role = 'Supervisor' THEN sp.id
                       ELSE NULL
                   END as related_id
            FROM users u
            LEFT JOIN students s ON u.related_id = s.id AND u.role = 'Student'
            LEFT JOIN supervisors sp ON u.related_id = sp.id AND u.role = 'Supervisor'
            WHERE u.username = ? OR u.email = ?
            LIMIT 1
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

       // if ($user && $password === $user->password) {
        if ($user && password_verify($password, $user->password)) {

            // Successful login
            session_regenerate_id(true); // Prevent session fixation
            
            // Store user data in session
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_role'] = $user->role;
            $_SESSION['user_name'] = $user->name;
            $_SESSION['profile_picture'] = $user->profile_picture;
            $_SESSION['related_id'] = $user->related_id; 
            
            // Clear login attempts
            // $db->prepare("DELETE FROM login_attempts WHERE ip = ?")->execute([$ip]);

            // Redirect based on role
            $redirectPath = strtolower($user->role) . '/dashboard.php';
            header("Location: $redirectPath");
            exit();
        } else {
            // Failed login
            // $db->prepare("INSERT INTO login_attempts (ip, username) VALUES (?, ?)")->execute([$ip, $username]);
            header("Location: login.php?error=invalid_credentials");
            exit();
        }
    } catch(PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        header("Location: login.php?error=server_error");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

 
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <title>Login | <?php echo $projectName; ?></title>
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
          <div class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">
            <div class="card card-primary">
              <div class="card-header">
                <h2 class="text-center mb-4"><?php echo $projectName; ?></h2>
                
              </div>
              <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger">Invalid username or password</div>
                <?php endif; ?>
              <div class="card-body">
                <form method="POST" action="#" class="needs-validation" novalidate="">
                  <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" tabindex="1" required autofocus>
                    <div class="invalid-feedback">
                      Please fill in your Username
                    </div>
                  </div>
                  <div class="form-group">
                    <div class="d-block">
                      <label for="password" class="control-label">Password</label>
                      <div class="float-right">
                        <a href="auth-forgot-password.php" class="text-small">
                          Forgot Password?
                        </a>
                      </div>
                    </div>
                    <input type="password" class="form-control" id="password" name="password" tabindex="2" required>
                    <div class="invalid-feedback">
                      please fill in your password
                    </div>
                  </div>
                  <div class="form-group">
                    <div class="custom-control custom-checkbox">
                      <input type="checkbox" name="remember" class="custom-control-input" tabindex="3" id="remember-me">
                      <label class="custom-control-label" for="remember-me">Remember Me</label>
                    </div>
                  </div>
                  <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-lg btn-block" tabindex="4">
                      Login
                    </button>
                  </div>
                </form>
                <div class="mt-3 text-center">
                    <p class="text-muted">New here? <a href="register.php">Create account</a></p>
                </div>
                
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
</body>


</html>