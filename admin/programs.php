<?php
$title = "Program Management";
require_once '../includes/auth_check.php';
require_once '../components/head.php';

// Only allow admins
if ($_SESSION['user_role'] !== 'Admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Fetch all programs
$programs = $db->query("SELECT * FROM programs ORDER BY program_name")->fetchAll();
?>

<div class="main-wrapper">
    <?php include '../components/navbar.php'; ?>
    <?php include '../components/sidebar.php'; ?>

    <!-- Main Content -->
      <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Department Management</h1>
                <div class="section-header-breadcrumb">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addProgramModal">
                        <i class="fas fa-plus"></i> Add Department
                    </button>
                </div>
            </div>
            <div class="section-body">
            <div class="row">
              <div class="col-12">
                <div class="card">
                  <div class="card-body">
                    <div class="table-responsive">
                      <table class="table table-striped table-hover" id="tableExport" style="width:100%;">
                        <thead>
                                    <tr>
                                        <th>Faculty Name</th>
                                        <th>Department Code</th>
                                        <th>Department</th>
                                        <th>Duration (Years)</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    foreach ($programs as $program): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($program->program_name) ?></td>
                                            <td><?= htmlspecialchars($program->program_code) ?></td>
                                            <td><?= htmlspecialchars($program->department) ?></td>
                                            <td><?= $program->duration_years ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary edit-program" 
                                                        data-id="<?= $program->id ?>"
                                                        data-name="<?= htmlspecialchars($program->program_name) ?>"
                                                        data-code="<?= htmlspecialchars($program->program_code) ?>"
                                                        data-dept="<?= htmlspecialchars($program->department) ?>"
                                                        data-duration="<?= $program->duration_years ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-program" 
                                                        data-id="<?= $program->id ?>">
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
            </div>
          </div>
            
           
        </section>
    </div>

    <!-- Add Program Modal -->
    <div class="modal fade" id="addProgramModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Department</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="addProgramForm" method="POST" action="api/add_program.php">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Faculty Name</label>
                            <input type="text" name="program_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Department Code</label>
                            <input type="text" name="program_code" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Department</label>
                            <input type="text" name="department" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Duration (Years)</label>
                            <input type="number" name="duration_years" class="form-control" min="1" max="6" value="4">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Program</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Program Modal -->
    <div class="modal fade" id="editProgramModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Program</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editProgramForm" method="POST" action="api/edit_program.php">
                    <input type="hidden" name="program_id" id="editProgramId">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Faculty Name</label>
                            <input type="text" name="program_name" id="editProgramName" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Department Code</label>
                            <input type="text" name="program_code" id="editProgramCode" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Department</label>
                            <input type="text" name="department" id="editProgramDept" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Duration (Years)</label>
                            <input type="number" name="duration_years" id="editProgramDuration" class="form-control" min="1" max="6">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Program</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../components/footer.php'; ?>
    <?php include '../components/script.php'; ?>

    <script>
        $(document).ready(function() {
    $('#programsTable').DataTable();

    // Handle Add Program Form Submission
    $('#addProgramForm').submit(function(e) {
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
            url: 'api/add_program.php',
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
                        window.location.href = 'programs.php';
                    });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error!', 'Failed to add program', 'error');
            }
        });
    });

    // Handle Edit Program Form Submission
    $('#editProgramForm').submit(function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Updating Program',
            text: 'Please wait...',
            icon: 'info',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: 'api/edit_program.php',
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
                        window.location.href = 'programs.php';
                    });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error!', 'Failed to update program', 'error');
            }
        });
    });

    // Delete program
    $('.delete-program').click(function() {
        const programId = $(this).data('id');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
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
                    url: `api/delete_program.php?id=${programId}`,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                title: 'Deleted!',
                                text: response.message,
                                icon: 'success'
                            }).then(() => {
                                window.location.href = 'programs.php';
                            });
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Failed to delete program', 'error');
                    }
                });
            }
        });
    });

    // Edit button click handler remains the same
    $('.edit-program').click(function() {
        const programId = $(this).data('id');
        const programName = $(this).data('name');
        const programCode = $(this).data('code');
        const programDept = $(this).data('dept');
        const programDuration = $(this).data('duration');

        $('#editProgramId').val(programId);
        $('#editProgramName').val(programName);
        $('#editProgramCode').val(programCode);
        $('#editProgramDept').val(programDept);
        $('#editProgramDuration').val(programDuration);

        $('#editProgramModal').modal('show');
    });
});
    </script>
</div>