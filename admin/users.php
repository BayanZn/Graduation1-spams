<?php
require_once '../includes/auth_check.php';
require_once '../components/head.php';

// Only allow admins
if ($_SESSION['user_role'] !== 'Admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Fetch all users
$users = $db->query("
    SELECT u.*, 
           CASE 
               WHEN u.role = 'Student' THEN s.student_id
               WHEN u.role = 'Supervisor' THEN sp.staff_id
               ELSE NULL
           END as external_id
    FROM users u
    LEFT JOIN students s ON u.related_id = s.id AND u.role = 'Student'
    LEFT JOIN supervisors sp ON u.related_id = sp.id AND u.role = 'Supervisor'
    ORDER BY u.role, u.username
")->fetchAll();
?>

<div class="main-wrapper">
    <?php include '../components/navbar.php'; ?>
    <?php include '../components/sidebar.php'; ?>

    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>User Management</h1>
                <div class="section-header-breadcrumb">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addUserModal">
                        <i class="fas fa-plus"></i> Add User
                    </button>
                </div>
            </div>

            <div class="section-body">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="tableExport" style="width:100%;">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>ID</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user->username) ?></td>
                                            <td><?= htmlspecialchars($user->email) ?></td>
                                            <td><?= htmlspecialchars($user->role) ?></td>
                                            <td><?= $user->external_id ?? 'N/A' ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary edit-user" 
                                                        data-id="<?= $user->id ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-user" 
                                                        data-id="<?= $user->id ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="addUserForm" method="POST" action="api/add_user.php">

                    <div class="modal-body">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" class="form-control" required>
                                <option value="">Select Role</option>
                                <option selected value="Admin">Admin</option>
                                
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../components/footer.php'; ?>
    <?php include '../components/script.php'; ?>

    <script>
$(document).ready(function () {
    $('#tableExport').DataTable();

    // Delete user (already in your code)
    // Delete user via AJAX
    $('.delete-user').click(function () {
        const userId = $(this).data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: "This user will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `api/delete_user.php?id=${userId}`,
                    type: 'GET', // Or 'POST' if your delete_user.php expects POST
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message
                            }).then(() => {
                                location.reload(); // OR remove the row without reload
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message || 'Failed to delete user.'
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while deleting the user.'
                        });
                    }
                });
            }
        });
    });


    // Submit add user form via AJAX
    $('#addUserForm').submit(function (e) {
        e.preventDefault(); // Prevent default form submission

        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message
                    }).then(() => {
                        location.reload(); // Reload to update user list
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Something went wrong while processing your request.'
                });
            }
        });
    });
});
</script>

</div>