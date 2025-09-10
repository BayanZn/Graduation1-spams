<?php
require_once 'config/db_connect.php';

// Ensure reset session is valid
if (!isset($_SESSION['reset_user_id'])) {
    header("Location: auth-forgot-password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($newPassword) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $_SESSION['reset_user_id']]);

        // Clear session
        unset($_SESSION['reset_user_id']);
        $success = "Password reset successful. <a href='login.php'>Click here to login</a>";
        header("Location: login.php");
    }
}
?>

<!DOCTYPE html>
<html lang="en">


<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <title>Reset Password | <?php echo $projectName; ?></title>
  <!-- General CSS Files -->
  <link rel="stylesheet" href="assets/css/app.min.css">
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
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php elseif (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php else: ?>
            <div class="row">
              <div class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">
                <div class="card card-primary">
                  <div class="card-header">
                    <h4>Reset Password</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">Enter Your New Password</p>
                    <form method="POST">
                      <div class="form-group">
                        <label for="email">Email</label>
                        <input id="email" type="email" class="form-control" name="email" tabindex="1" required autofocus>
                    </div>
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                        <div id="pwindicator" class="pwindicator">
                          <div class="bar"></div>
                          <div class="label"></div>
                      </div>
                  </div>
                  <div class="form-group">
                    <label for="password-confirm">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-lg btn-block" tabindex="4">
                      Reset Password
                  </button>
              </div>
          </form>
      <?php endif; ?>
      <div class="mt-3 text-center">
        <p class="text-muted">Want to go back? <a href="login.php">Login</a></p>
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


<!-- auth-reset-password.html  21 Nov 2019 04:05:02 GMT -->
</html>