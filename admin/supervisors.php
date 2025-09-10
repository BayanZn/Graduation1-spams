<?php
$title = "Manage Supervisor";

require_once '../includes/auth_check.php';
require_once '../components/head.php';
?>

<div class="main-wrapper main-wrapper-1">
    <div class="navbar-bg"></div>
    <?php include '../components/navbar.php'; ?>
    <?php include '../components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Manage Supervisors</h1>
                <div class="section-header-breadcrumb">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addSupervisorModal">
                        <i class="fas fa-plus"></i> Add Supervisor
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
                                        <th>Staff ID</th>
                                        <th>Full Name</th>
                                        <th>Department</th>
                                        <th>Specialization</th>
                                        <th>Projects</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT s.*, 
                                              COUNT(ps.project_id) as project_count
                                              FROM supervisors s
                                              LEFT JOIN project_supervision ps ON s.id = ps.supervisor_id
                                              GROUP BY s.id
                                              ORDER BY s.full_name";
                                    $supervisors = $db->query($query);
                                    
                                    while($supervisor = $supervisors->fetch()):
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($supervisor->staff_id) ?></td>
                                        <td><?= htmlspecialchars($supervisor->full_name) ?></td>
                                        <td><?= htmlspecialchars($supervisor->department) ?></td>
                                        <td><?= htmlspecialchars($supervisor->specialization) ?></td>
                                        <td>
                                            <span class="badge badge-primary">
                                                <?= $supervisor->project_count ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="edit_supervisor.php?id=<?= $supervisor->id ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <button class="btn btn-sm btn-danger delete-supervisor" 
                                                    data-id="<?= $supervisor->id ?>"
                                                    <?= $supervisor->project_count > 0 ? 'disabled' : '' ?>>
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    
    <!-- Add Supervisor Modal -->
    <div class="modal fade" id="addSupervisorModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Supervisor</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="supervisorForm" method="POST" action="api/add_supervisor.php">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Staff ID</label>
                            <input type="text" name="staff_id" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Department</label>
                            <input type="text" name="department" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Specialization</label>
                            <input type="text" name="specialization" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Max Projects</label>
                            <input type="number" name="max_projects" class="form-control" value="5" min="1">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Supervisor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../components/footer.php'; ?>
    <?php include '../components/script.php'; ?>
    
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#supervisorsTable').DataTable();
        
        // Handle supervisor form submission
        $('#supervisorForm').submit(function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Processing',
                text: 'Please wait...',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => Swal.showLoading()
            });
            
            $.ajax({
                url: 'api/add_supervisor.php',
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
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to add supervisor', 'error');
                }
            });
        });
        
        // Delete supervisor handler
        $('.delete-supervisor').click(function() {
            if($(this).is(':disabled')) {
                Swal.fire('Warning', 'Cannot delete supervisor with assigned projects', 'warning');
                return;
            }
            
            const supervisorId = $(this).data('id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'api/delete_supervisor.php?id=' + supervisorId,
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if(response.status === 'success') {
                                Swal.fire(
                                    'Deleted!',
                                    response.message,
                                    'success'
                                ).then(() => location.reload());
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Failed to delete supervisor', 'error');
                        }
                    });
                }
            });
        });
    });
    </script>
</div>