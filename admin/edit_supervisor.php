<?php
require_once '../includes/auth_check.php';
require_once '../components/head.php';

if (!isset($_GET['id'])) {
    header("Location: supervisors.php");
    exit();
}

$supervisor_id = (int)$_GET['id'];

// Get supervisor data
$stmt = $db->prepare("SELECT * FROM supervisors WHERE id = ?");
$stmt->execute([$supervisor_id]);
$supervisor = $stmt->fetch();

if (!$supervisor) {
    header("Location: supervisors.php");
    exit();
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
                <h1>Edit Supervisor</h1>
                <div class="section-header-breadcrumb">
                    <a href="supervisors.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Supervisors
                    </a>
                </div>
            </div>
            
            <div class="section-body">
                <div class="card">
                    <div class="card-body">
                        <form id="editSupervisorForm" method="POST" action="api/update_supervisor.php">
                            <input type="hidden" name="id" value="<?= $supervisor->id ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Staff ID</label>
                                        <input type="text" name="staff_id" class="form-control" 
                                               value="<?= htmlspecialchars($supervisor->staff_id) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Full Name</label>
                                        <input type="text" name="full_name" class="form-control" 
                                               value="<?= htmlspecialchars($supervisor->full_name) ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control" 
                                               value="<?= htmlspecialchars($supervisor->email) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Department</label>
                                        <input type="text" name="department" class="form-control" 
                                               value="<?= htmlspecialchars($supervisor->department) ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Specialization</label>
                                        <input type="text" name="specialization" class="form-control" 
                                               value="<?= htmlspecialchars($supervisor->specialization) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Max Projects</label>
                                        <input type="number" name="max_projects" class="form-control" 
                                               value="<?= $supervisor->max_projects ?>" min="1" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Update Supervisor</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>
    
    <?php include '../components/footer.php'; ?>
    <?php include '../components/script.php'; ?>
    
    <script>
    $(document).ready(function() {
        $('#editSupervisorForm').submit(function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Updating Supervisor',
                text: 'Please wait...',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => Swal.showLoading()
            });
            
            $.ajax({
                url: 'api/update_supervisor.php',
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
                            window.location.href = 'supervisors.php';
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to update supervisor', 'error');
                }
            });
        });
    });
    </script>
</div>