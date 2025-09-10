<?php
require_once '../includes/auth_check.php';
require_once '../components/head.php';

if (!isset($_GET['id'])) {
    header("Location: students.php");
    exit();
}

$student_id = (int)$_GET['id'];

// Get student data
$stmt = $db->prepare("
    SELECT s.*, p.program_name 
    FROM students s
    JOIN programs p ON s.program_id = p.id
    WHERE s.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: students.php");
    exit();
}

// Get all programs for dropdown
$programs = $db->query("SELECT * FROM programs");
?>

<div class="main-wrapper main-wrapper-1">
    <div class="navbar-bg"></div>
    <?php include '../components/navbar.php'; ?>
    <?php include '../components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Edit Student</h1>
                <div class="section-header-breadcrumb">
                    <a href="students.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Students
                    </a>
                </div>
            </div>
            
            <div class="section-body">
                <div class="card">
                    <div class="card-body">
                        <form id="editStudentForm" method="POST" action="api/update_student.php">
                            <input type="hidden" name="id" value="<?= $student->id ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Student ID</label>
                                        <input type="text" name="student_id" class="form-control" 
                                               value="<?= htmlspecialchars($student->student_id) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Full Name</label>
                                        <input type="text" name="full_name" class="form-control" 
                                               value="<?= htmlspecialchars($student->full_name) ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control" 
                                               value="<?= htmlspecialchars($student->email) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Department</label>
                                        <select name="program_id" class="form-control" required>
                                            <option value="">Select Department</option>
                                            <?php while($program = $programs->fetch()): ?>
                                            <option value="<?= $program->id ?>" 
                                                <?= $program->id == $student->program_id ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($program->department) ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Year Level</label>
                                        <select name="year_level" class="form-control" required>
                                            <option value="1st year" <?= $student->year_level == '1st year' ? 'selected' : '' ?>>1st year</option>
                                            <option value="2nd year" <?= $student->year_level == '2nd year' ? 'selected' : '' ?>>2nd year</option>
                                            <option value="3rd year" <?= $student->year_level == '3rd year' ? 'selected' : '' ?>>3rd year</option>
                                            <option value="4th year" <?= $student->year_level == '4th year' ? 'selected' : '' ?>>4th year</option>
                                            <option value="Final year" <?= $student->year_level == 'Final year' ? 'selected' : '' ?>>Final year</option>

                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Update Student</button>
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
        $('#editStudentForm').submit(function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Updating Student',
                text: 'Please wait...',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => Swal.showLoading()
            });
            
            $.ajax({
                url: 'api/update_student.php',
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
                            window.location.href = 'students.php';
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to update student', 'error');
                }
            });
        });
    });
    </script>
</div>