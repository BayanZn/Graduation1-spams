<?php
$title = "Project Assignments";
require_once '../includes/auth_check.php';
require_once '../components/head.php';

// Only allow admins and coordinators
if (!in_array($_SESSION['user_role'], ['Admin', 'Coordinator'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Fetch supervisor mapping for projects
$supervisorList = $db->query("
    SELECT ps.project_id, sp.id, sp.full_name, sp.staff_id
    FROM project_supervision ps
    JOIN supervisors sp ON ps.supervisor_id = sp.id
    ")->fetchAll(PDO::FETCH_ASSOC);

// Group supervisors by project_id
$projectSupervisorMap = [];
foreach ($supervisorList as $row) {
    $projectSupervisorMap[$row['project_id']][] = [
        'id' => $row['id'],
        'name' => $row['full_name'] . ' (' . $row['staff_id'] . ')'
    ];
}

// Fetch unassigned students
$unassignedStudents = $db->query("
    SELECT s.*, p.program_name 
    FROM students s
    JOIN programs p ON s.program_id = p.id
    WHERE s.id NOT IN (SELECT student_id FROM project_assignments)
    ORDER BY s.full_name
    ")->fetchAll();

// Fetch available projects
$availableProjects = $db->query("
    SELECT p.*, 
    GROUP_CONCAT(CONCAT(sp.full_name, ' (', sp.staff_id, ')') SEPARATOR ', ') as supervisors
    FROM projects p
    LEFT JOIN project_supervision ps ON p.id = ps.project_id
    LEFT JOIN supervisors sp ON ps.supervisor_id = sp.id
    WHERE p.status = 'Available'
    GROUP BY p.id
    ORDER BY p.project_name
    ")->fetchAll();

// Fetch all supervisors
$supervisors = $db->query("SELECT * FROM supervisors ORDER BY full_name")->fetchAll();

// Fetch existing assignments
$assignments = $db->query("
    SELECT pa.*, 
    s.full_name as student_name, 
    p.project_name, 
    sp.full_name as supervisor_name
    FROM project_assignments pa
    JOIN students s ON pa.student_id = s.id
    JOIN projects p ON pa.project_id = p.id
    JOIN supervisors sp ON pa.supervisor_id = sp.id
    ORDER BY pa.assigned_at DESC
    ")->fetchAll();
    ?>

    <div class="main-wrapper">
        <?php include '../components/navbar.php'; ?>
        <?php include '../components/sidebar.php'; ?>
        <script>
            const projectSupervisorsMap = <?= json_encode($projectSupervisorMap) ?>;
        </script>


        <div class="main-content">
            <section class="section">
                <div class="section-header">
                    <h1>Project Assignments</h1>
                </div>

                <div class="section-body">
                    <!-- Assignment Form -->
                    <div class="card">
                        <div class="card-header">
                            <h4>Create New Assignment</h4>
                        </div>
                        <div class="card-body">
                            <form id="assignmentForm">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Student</label>
                                            <select name="student_ids[]" class="form-control" multiple required>
                                                <?php foreach ($unassignedStudents as $student): ?>
                                                    <option value="<?= $student->id ?>">
                                                        <?= htmlspecialchars($student->full_name) ?> 
                                                        (<?= htmlspecialchars($student->student_id) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="form-text text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple students.</small>

                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Project</label>
                                            <select name="project_id" class="form-control" id="projectSelect" required>
                                                <option value="">Select Project</option>
                                                <?php foreach ($availableProjects as $project): ?>
                                                    <option value="<?= $project->id ?>">
                                                        <?= htmlspecialchars($project->project_name) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Supervisor</label>
                                            <select name="supervisor_id" class="form-control" id="supervisorSelect" required>
                                                <option value="">Select Supervisor</option>
                                                <!-- Will be populated dynamically -->
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Assign Project</button>
                            </form>

                        </div>
                    </div>

                    <!-- Current Assignments -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h4>Current Assignments</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="tableExport" style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Project</th>
                                            <th>Supervisor</th>
                                            <th>Status</th>
                                            <th>Assigned On</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assignments as $assignment): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($assignment->student_name) ?></td>
                                                <td><?= htmlspecialchars($assignment->project_name) ?></td>
                                                <td><?= htmlspecialchars($assignment->supervisor_name) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= 
                                                    $assignment->status === 'Approved' ? 'success' : 
                                                    ($assignment->status === 'Rejected' ? 'danger' : 'warning')
                                                ?>">
                                                <?= $assignment->status ?>
                                            </span>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($assignment->assigned_at)) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info edit-assignment" 
                                            data-id="<?= $assignment->id ?>"
                                            data-status="<?= $assignment->status ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-assignment" 
                                        data-id="<?= $assignment->id ?>">
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

<!-- Edit Assignment Modal -->
<div class="modal fade" id="editAssignmentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Assignment Status</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editAssignmentForm">
                <input type="hidden" name="assignment_id" id="editAssignmentId">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control" id="editAssignmentStatus" required>
                            <option value="Proposed">Proposed</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../components/footer.php'; ?>
<?php include '../components/script.php'; ?>

<script>
    $(document).ready(function() {
        $('#assignmentsTable').DataTable();

        // Show project supervisors when project is selected
        $('select[name="project_id"]').change(function() {
            const supervisors = $(this).find(':selected').data('supervisors');
            $('#projectSupervisors').text('Supervisors: ' + (supervisors || 'Not assigned'));
        });

        // Handle assignment form submission
        $('#assignmentForm').submit(function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Assigning Project',
                text: 'Please wait...',
                icon: 'info',
                allowOutsideClick: false, 
                showConfirmButton: false,
                willOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: 'api/create_assignment.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
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
                    Swal.fire('Error!', 'Failed to create assignment', 'error');
                }
            });
        });

        // Edit assignment button
        $('.edit-assignment').click(function() {
            const assignmentId = $(this).data('id');
            const status = $(this).data('status');
            
            $('#editAssignmentId').val(assignmentId);
            $('#editAssignmentStatus').val(status);
            $('#editAssignmentModal').modal('show');
        });

        // Update assignment status
        $('#editAssignmentForm').submit(function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Updating Assignment',
                text: 'Please wait...',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: 'api/update_assignment.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
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
                    Swal.fire('Error!', 'Failed to update assignment', 'error');
                }
            });
        });

        // Delete assignment
        $('.delete-assignment').click(function() {
            const assignmentId = $(this).data('id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "This will remove the assignment permanently!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Deleting',
                        text: 'Please wait...',
                        icon: 'info',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => Swal.showLoading()
                    });

                    $.ajax({
                        url: `api/delete_assignment.php?id=${assignmentId}`,
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Deleted!',
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
                            Swal.fire('Error!', 'Failed to delete assignment', 'error');
                        }
                    });
                }
            });
        });

        $('#projectSelect').change(function () {
            const projectId = $(this).val();
            const supervisorSelect = $('#supervisorSelect');

            supervisorSelect.empty().append('<option value="">Select Supervisor</option>');

            if (projectSupervisorsMap[projectId]) {
                projectSupervisorsMap[projectId].forEach(sup => {
                    supervisorSelect.append(`<option value="${sup.id}">${sup.name}</option>`);
                });
            } else {
                supervisorSelect.append('<option disabled>No supervisor assigned to this project</option>');
            }
        });

    });
</script>
</div>