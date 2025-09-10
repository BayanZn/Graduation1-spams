<?php
require_once '../includes/auth_check.php';
require_once '../components/head.php'; 

// Fetch account info from users table
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$accountInfo = $stmt->fetch();
$user = $accountInfo; // For convenience in template rendering

// Fetch additional user details
$userDetails = null;
if ($_SESSION['user_role'] === 'Student') {
    $stmt = $db->prepare("
        SELECT s.*, p.program_name 
        FROM students s 
        JOIN programs p ON s.program_id = p.id 
        WHERE s.id = ?
        ");
    $stmt->execute([$_SESSION['related_id']]);
    $userDetails = $stmt->fetch();
} elseif ($_SESSION['user_role'] === 'Supervisor') {
    $stmt = $db->prepare("SELECT * FROM supervisors WHERE id = ?");
    $stmt->execute([$_SESSION['related_id']]);
    $userDetails = $stmt->fetch();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $bio = trim($_POST['bio'] ?? '');



        // Handle file upload
        $profilePicPath = null;
        if (!empty($_FILES['profile_picture']['name'])) {
            $targetDir = "../uploads/profile_pictures/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $fileName = basename($_FILES["profile_picture"]["name"]);
            $targetFilePath = $targetDir . time() . "_" . $fileName;

            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFilePath)) {
                $profilePicPath = $targetFilePath;
            }
        }

        $updateQuery = "UPDATE users SET username = ?, email = ?, phone = ?, address = ?, bio = ?" .
        ($profilePicPath ? ", profile_picture = ?" : "") .
        " WHERE id = ?";

        $params = [$username, $email, $phone, $address, $bio];
        if ($profilePicPath) {
            $params[] = $profilePicPath;
        }
        $params[] = $_SESSION['user_id'];

        $stmt = $db->prepare($updateQuery);
        $stmt->execute($params);

        echo "<script>alert('Profile updated successfully.');window.location.href='profile.php';</script>";
        exit;
    }

    // Password change (from form POST, not AJAX)
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        if ($new !== $confirm) {
            echo "<script>alert('New passwords do not match.');</script>";
        } elseif (!password_verify($current, $accountInfo->password)) {
            echo "<script>alert('Current password is incorrect.');</script>";
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $_SESSION['user_id']]);

            echo "<script>alert('Password changed successfully.');window.location.href='profile.php';</script>";
            exit;
        }
    }
}
?>

<div class="main-wrapper main-wrapper-1">
    <div class="navbar-bg"></div>
    <?php include '../components/navbar.php'; ?>
    <?php include '../components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>My Profile</h1>
            </div>
            
            <div class="section-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card profile-widget">
                            <div class="profile-widget-header">
                                <img 
                                alt="image" 
                                src="<?= $accountInfo->profile_picture ? '' . htmlspecialchars($accountInfo->profile_picture) : '../assets/img/avatar/avatar-1.png' ?>" 
                                class="rounded-circle profile-widget-picture"
                                style="object-fit: cover;"
                                >
                                <div class="profile-widget-items">
                                    <div class="profile-widget-item">
                                        <div class="profile-widget-item-label">Role</div>
                                        <div class="profile-widget-item-value"><?= htmlspecialchars($_SESSION['user_role']) ?></div>
                                    </div>
                                    <div class="profile-widget-item">
                                        <div class="profile-widget-item-label">Member Since</div>
                                        <div class="profile-widget-item-value">
                                            <?= date('M Y', strtotime($accountInfo->created_at)) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="profile-widget-description">
                                <div class="profile-widget-name">
                                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                                    <div class="text-muted d-inline font-weight-normal">
                                        <div class="slash"></div> <?= htmlspecialchars($accountInfo->username) ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        
                        <!-- <div class="card mt-4">
                            <div class="card-header">
                                <h4>Account Settings</h4>
                            </div>
                            <div class="card-body">
                                <form id="passwordForm">
                                    <div class="form-group">
                                        <label>Current Password</label>
                                        <input type="password" name="current_password" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>New Password</label>
                                        <input type="password" name="new_password" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Confirm New Password</label>
                                        <input type="password" name="confirm_password" class="form-control" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-block">
                                        Change Password
                                    </button>
                                </form>
                            </div>
                        </div> -->
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h4>Edit Profile</h4>
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="profile-tab" data-toggle="tab" href="#profile" role="tab">Profile</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="password-tab" data-toggle="tab" href="#password" role="tab">Password</a>
                                    </li>
                                </ul>

                                <div class="tab-content" id="profileTabsContent">
                                    <!-- Profile Tab -->
                                    <div class="tab-pane fade show active" id="profile" role="tabpanel">
                                        <form method="POST" enctype="multipart/form-data" class="mt-4">
                                            <input type="hidden" name="update_profile" value="1">
                                            
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Profile Picture</label>
                                                <div class="col-sm-9">
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="profile_picture" name="profile_picture">
                                                        <label class="custom-file-label" for="profile_picture">Choose file</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Username</label>
                                                <div class="col-sm-9">
                                                    <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($user->username) ?>" required>

                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Email</label>
                                                <div class="col-sm-9">
                                                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user->email) ?>" required>

                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Phone</label>
                                                <div class="col-sm-9">
                                                    <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($user->phone ?? '') ?>">
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Address</label>
                                                <div class="col-sm-9">
                                                    <textarea class="form-control" name="address"><?= htmlspecialchars($user->address ?? '') ?></textarea>
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Bio</label>
                                                <div class="col-sm-9">
                                                    <textarea class="form-control" name="bio"><?= htmlspecialchars($user->bio ?? '') ?></textarea>
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <div class="col-sm-9 offset-sm-3">
                                                    <button type="submit" class="btn btn-primary">Update Profile</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Password Tab -->
                                    <div class="tab-pane fade" id="password" role="tabpanel">
                                        <form method="POST" class="mt-4">
                                            <input type="hidden" name="change_password" value="1">
                                            
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Current Password</label>
                                                <div class="col-sm-9">
                                                    <input type="password" class="form-control" name="current_password" required>
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">New Password</label>
                                                <div class="col-sm-9">
                                                    <input type="password" class="form-control" name="new_password" required>
                                                    <small class="form-text text-muted">Minimum 8 characters</small>
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Confirm Password</label>
                                                <div class="col-sm-9">
                                                    <input type="password" class="form-control" name="confirm_password" required>
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <div class="col-sm-9 offset-sm-3">
                                                    <button type="submit" class="btn btn-primary">Change Password</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    
    <?php include '../components/footer.php'; ?>
    <?php include '../components/script.php'; ?>
    
    <script>
        $(document).ready(function() {
            $('form[method="POST"]').on('submit', function(e) {
                if ($(this).find('input[name="change_password"]').length > 0) {
                    e.preventDefault();

                    const currentPassword = $('input[name="current_password"]').val();
                    const newPassword = $('input[name="new_password"]').val();
                    const confirmPassword = $('input[name="confirm_password"]').val();

                    if (newPassword !== confirmPassword) {
                        Swal.fire('Error!', 'New passwords do not match', 'error');
                        return;
                    }

                    Swal.fire({
                        title: 'Changing Password',
                        text: 'Please wait...',
                        icon: 'info',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => Swal.showLoading()
                    });

                    $.ajax({
                        url: 'api/change_password.php',
                        type: 'POST',
                        data: $(this).serialize(),
                        dataType: 'json',
                        success: function(response) {
                            Swal.close();
                            if (response.status === 'success') {
                                Swal.fire('Success!', response.message, 'success');
                                $('form')[0].reset();
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Failed to change password', 'error');
                        }
                    });
                }
            });
        });

    </script>
</div>  