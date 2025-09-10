<?php
require_once 'includes/auth_check.php';
require_once 'components/head.php';

// Get user details based on role
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

// Get user account info
$accountInfo = $db->prepare("SELECT * FROM users WHERE id = ?");
$accountInfo->execute([$_SESSION['user_id']]);
$accountInfo = $accountInfo->fetch();
?>

<div class="main-wrapper main-wrapper-1">
    <div class="navbar-bg"></div>
    <?php include 'components/navbar.php'; ?>
    <?php include 'components/sidebar.php'; ?>
    
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
                                <div class="profile-widget-items">
                                    <div class="profile-widget-item">
                                        <div class="profile-widget-item-label">Role</div>
                                        <div class="profile-widget-item-value"><?= $_SESSION['user_role'] ?></div>
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
                                    <?= $_SESSION['user_name'] ?>
                                    <div class="text-muted d-inline font-weight-normal">
                                        <div class="slash"></div> <?= $accountInfo->username ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-4">
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
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h4>Personal Information</h4>
                            </div>
                            <div class="card-body">
                                <?php if($userDetails): ?>
                                    <table class="table table-striped">
                                        <tbody>
                                            <?php if($_SESSION['user_role'] === 'Student'): ?>
                                                <tr>
                                                    <th>Student ID</th>
                                                    <td><?= htmlspecialchars($userDetails->student_id) ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Full Name</th>
                                                    <td><?= htmlspecialchars($userDetails->full_name) ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Email</th>
                                                    <td><?= htmlspecialchars($userDetails->email) ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Program</th>
                                                    <td><?= htmlspecialchars($userDetails->program_name) ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Year Level</th>
                                                    <td><?= htmlspecialchars($userDetails->year_level) ?></td>
                                                </tr>
                                            <?php elseif($_SESSION['user_role'] === 'Supervisor'): ?>
                                                <tr>
                                                    <th>Staff ID</th>
                                                    <td><?= htmlspecialchars($userDetails->staff_id) ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Full Name</th>
                                                    <td><?= htmlspecialchars($userDetails->full_name) ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Email</th>
                                                    <td><?= htmlspecialchars($userDetails->email) ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Department</th>
                                                    <td><?= htmlspecialchars($userDetails->department) ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Specialization</th>
                                                    <td><?= htmlspecialchars($userDetails->specialization) ?></td>
                                                </tr>
                                            <?php else: ?>
                                                <tr>
                                                    <th>Username</th>
                                                    <td><?= htmlspecialchars($accountInfo->username) ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Email</th>
                                                    <td><?= htmlspecialchars($accountInfo->email) ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Role</th>
                                                    <td><?= htmlspecialchars($_SESSION['user_role']) ?></td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        No additional profile information available.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    
    <?php include 'components/footer.php'; ?>
    <?php include 'components/script.php'; ?>
    
    <script>
    $(document).ready(function() {
        // Handle password change form
        $('#passwordForm').submit(function(e) {
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
                    if(response.status === 'success') {
                        Swal.fire({
                            title: 'Success!',
                            text: response.message,
                            icon: 'success'
                        }).then(() => {
                            $('#passwordForm')[0].reset();
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to change password', 'error');
                }
            });
        });
    });
    </script>
</div>  