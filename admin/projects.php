<?php
$title = "Manage Projects";
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
                <h1>Manage Projects</h1>
                <div class="section-header-breadcrumb">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addProjectModal">
                        <i class="fas fa-plus"></i> Add Project
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
                                        <th>Project Name</th>
                                        <th>Case Study</th>
                                        <th>Level</th>
                                        <th>Status</th>
                                        <th>Supervisors</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT p.*, 
                                              GROUP_CONCAT(s.full_name SEPARATOR ', ') as supervisors
                                              FROM projects p
                                              LEFT JOIN project_supervision ps ON p.id = ps.project_id
                                              LEFT JOIN supervisors s ON ps.supervisor_id = s.id
                                              GROUP BY p.id
                                              ORDER BY p.project_name";
                                    $projects = $db->query($query);
                                    
                                    while($project = $projects->fetch()):
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($project->project_name) ?></td>
                                        <td><?= htmlspecialchars(substr($project->project_case, 0, 50)) ?>...</td>
                                        <td><?= htmlspecialchars($project->project_level) ?></td>
                                        <td>
                                            <span class="badge badge-<?= $project->allocation ? 'success' : 'warning' ?>">
                                                <?= $project->allocation ? 'Assigned' : 'Available' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= $project->supervisors ? htmlspecialchars($project->supervisors) : 'Not assigned' ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary edit-project" 
                                                    data-id="<?= $project->id ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-info assign-supervisor" 
                                                    data-id="<?= $project->id ?>">
                                                <i class="fas fa-user-plus"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-project" 
                                                    data-id="<?= $project->id ?>"
                                                    <?= $project->allocation ? 'disabled' : '' ?>>
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
    
    <!-- Add Project Modal -->
    <div class="modal fade" id="addProjectModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Project</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="projectForm" method="POST" action="api/add_project.php">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Project Name</label>
                            <input type="text" name="project_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Case Study</label>
                            <textarea name="project_case" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Project Level</label>
                            <select name="project_level" class="form-control" required>
                                <option value="Beginner">Beginner</option>
                                <option value="Intermediate">Intermediate</option>
                                <option value="Advanced">Advanced</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Assign Supervisor Modal -->
    <div class="modal fade" id="assignSupervisorModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Supervisor</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="assignSupervisorForm" method="POST">
                    <input type="hidden" name="project_id" id="projectId">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Select Supervisor</label>
                            <select name="supervisor_id" class="form-control" required>
                                <option value="">Select Supervisor</option>
                                <?php
                                $supervisors = $db->query("SELECT * FROM supervisors ORDER BY full_name");
                                while($supervisor = $supervisors->fetch()):
                                ?>
                                <option value="<?= $supervisor->id ?>">
                                    <?= htmlspecialchars($supervisor->full_name) ?> 
                                    (<?= htmlspecialchars($supervisor->department) ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_lead" id="isLead">
                                <label class="form-check-label" for="isLead">
                                    Set as Lead Supervisor
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Assign Supervisor</button>
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
        $('#projectsTable').DataTable();
        
        // Handle project form submission
        $('#projectForm').submit(function(e) {
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
                url: 'api/add_project.php',
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
                    Swal.fire('Error!', 'Failed to add project', 'error');
                }
            });
        });
        
        // Handle assign supervisor button click
        $('.assign-supervisor').click(function() {
            const projectId = $(this).data('id');
            $('#projectId').val(projectId);
            $('#assignSupervisorModal').modal('show');
        });
        
        // Handle assign supervisor form submission
        $('#assignSupervisorForm').submit(function(e) {
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
                url: 'api/assign_supervisor.php',
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
                    Swal.fire('Error!', 'Failed to assign supervisor', 'error');
                }
            });
        });
        
        // Delete project handler
        $('.delete-project').click(function() {
            if($(this).is(':disabled')) {
                Swal.fire('Warning', 'Cannot delete assigned project', 'warning');
                return;
            }
            
            const projectId = $(this).data('id');
            
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
                        url: 'api/delete_project.php?id=' + projectId,
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
                            Swal.fire('Error!', 'Failed to delete project', 'error');
                        }
                    });
                }
            });
        });
    });
    </script>
</div>