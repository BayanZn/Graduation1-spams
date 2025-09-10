<?php
$title = "Manage Students";

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
                <h1>Manage Students</h1>
                <div class="section-header-breadcrumb">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addStudentModal">
                        <i class="fas fa-plus"></i> Add Student
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
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Faculty</th>
                            <th>Year</th>
                            <th>Project</th>
                            <th>Defense Status</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                                    <?php
                                    $query = "SELECT s.*, p.department, pr.project_name 
                                              FROM students s
                                              JOIN programs p ON s.program_id = p.id
                                              LEFT JOIN projects pr ON s.project_id = pr.id
                                              ORDER BY s.full_name";
                                    $students = $db->query($query);
                                    
                                    while($student = $students->fetch()):
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($student->student_id) ?></td>
                                        <td><?= htmlspecialchars($student->full_name) ?></td>
                                        <td><?= htmlspecialchars($student->department) ?></td>
                                        <td><?= htmlspecialchars($student->year_level) ?></td>
                                        <td>
                                            <?= $student->project_name ? htmlspecialchars($student->project_name) : 
                                               '<span class="badge badge-warning">Not assigned</span>' ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">Pending</span>
                                        </td>
                                        <td>

                                            <a href="edit_student.php?id=<?= $student->id ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <button class="btn btn-sm btn-danger delete-student" 
                                                    data-id="<?= $student->id ?>">
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
            </div>
          </div>
            
           
        </section>
    </div>
    
    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Student</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="studentForm" method="POST" action="api/add_student.php">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Student ID</label>
                            <input type="text" name="student_id" class="form-control" required>
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
                            <select name="program_id" class="form-control" required>
                                <option value="">Select department</option>
                                <?php
                                $programs = $db->query("SELECT * FROM programs");
                                while($program = $programs->fetch()):
                                ?>
                                <option value="<?= $program->id ?>"><?= htmlspecialchars($program->department) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Year Level</label>
                            <select name="year_level" class="form-control" required>
                                <option value="">Select Year</option>
                                <option value="1st year">1st year</option>
                                <option value="2nd year">2nd year</option>
                                <option value="3rd year">3rd year</option>
                                <option value="4th year">4th year</option>
                                <option value="Final year">Final year</option>
                                   
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Add Student Modal -->
    <div class="modal fade" id="edit-student" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Student</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="studentForm" method="POST" action="api/add_student.php">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Student ID</label>
                            <input type="text" name="student_id" class="form-control" required>
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
                            <label>Program</label>
                            <select name="program_id" class="form-control" required>
                                <option value="">Select Program</option>
                                <?php
                                $programs = $db->query("SELECT * FROM programs");
                                while($program = $programs->fetch()):
                                ?>
                                <option value="<?= $program->id ?>"><?= htmlspecialchars($program->program_name) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Year Level</label>
                            <select name="year_level" class="form-control" required>
                                <option value="">Select Year</option>
                                <option value="1st year">1st year</option>
                                <option value="2nd year">2nd year</option>
                                <option value="3rd year">3rd year</option>
                                <option value="4th year">4th year</option>
                                <option value="Final year">Final year</option>
                                   
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Student</button>
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
        $('#studentsTable').DataTable();
        
        // Handle student form submission
        $('#studentForm').submit(function(e) {
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
                url: 'api/add_student.php',
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
                    Swal.fire('Error!', 'Failed to add student', 'error');
                }
            });
        });
        
        // Delete student handler
        $('.delete-student').click(function() {
            const studentId = $(this).data('id');
            
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
                        url: 'api/delete_student.php?id=' + studentId,
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
                            Swal.fire('Error!', 'Failed to delete student', 'error');
                        }
                    });
                }
            });
        });
    });
    </script>
</div> 