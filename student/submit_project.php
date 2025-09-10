<?php
require_once '../includes/auth_check.php';
require_once '../components/head.php';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get student's current project info and group members
$stmt = $db->prepare("
    SELECT s.project_id, p.project_name, p.project_case, 
           ds.status as defense_status, s.id as student_id
    FROM students s
    LEFT JOIN projects p ON s.project_id = p.id
    LEFT JOIN defense_schedule ds ON s.id = ds.student_id
    WHERE s.id = ?
");
$stmt->execute([$_SESSION['related_id']]);
$student = $stmt->fetch();

// Get all group members if project exists
$groupMembers = [];
if ($student && $student->project_id) {
    $stmt = $db->prepare("
        SELECT s.id, s.full_name, s.student_id, s.email
        FROM students s
        WHERE s.project_id = ?
        ORDER BY s.full_name
    ");
    $stmt->execute([$student->project_id]);
    $groupMembers = $stmt->fetchAll();
}

// Get all chapter submissions for the group
$groupSubmissions = [];
if ($student && $student->project_id) {
    $stmt = $db->prepare("
        SELECT pcs.*, s.full_name as student_name, s.student_id
        FROM project_chapter_submissions pcs
        JOIN students s ON pcs.student_id = s.id
        WHERE s.project_id = ?
        ORDER BY pcs.submitted_at DESC
    ");
    $stmt->execute([$student->project_id]);
    $groupSubmissions = $stmt->fetchAll();
}

// Get project supervisor
$supervisor = null;
if ($student && $student->project_id) {
    $stmt = $db->prepare("
        SELECT sp.full_name, sp.email, sp.specialization
        FROM project_supervision ps
        JOIN supervisors sp ON ps.supervisor_id = sp.id
        WHERE ps.project_id = ?
        LIMIT 1
    ");
    $stmt->execute([$student->project_id]);
    $supervisor = $stmt->fetch();
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
                <h1>Project Submission</h1>
                <?php if($student && $student->project_id): ?>
                <div class="section-header-breadcrumb">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#chapterModal">
                        <i class="fas fa-upload"></i> Submit Chapter
                    </button>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if(!$student || !$student->project_id): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> You haven't been assigned a project yet.
                </div>
            <?php else: ?>
                
                <div class="row">
                    <!-- Project Details Card -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h4>Project Details</h4>
                            </div>
                            <div class="card-body">
                                <h5><?= htmlspecialchars($student->project_name) ?></h5>
                                <p class="text-muted"><?= htmlspecialchars($student->project_case) ?></p>
                                
                                <?php if($supervisor): ?>
                                <div class="mt-3">
                                    <h6>Supervisor</h6>
                                    <p class="mb-1">
                                        <i class="fas fa-user-tie"></i> 
                                        <?= htmlspecialchars($supervisor->full_name) ?>
                                    </p>
                                    <p class="mb-1">
                                        <i class="fas fa-envelope"></i> 
                                        <?= htmlspecialchars($supervisor->email) ?>
                                    </p>
                                    <?php if($supervisor->specialization): ?>
                                    <p class="mb-0">
                                        <i class="fas fa-graduation-cap"></i> 
                                        <?= htmlspecialchars($supervisor->specialization) ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Group Members Card -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h4>Group Members</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Student ID</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($groupMembers as $member): ?>
                                            <tr class="<?= $member->id == $_SESSION['related_id'] ? 'table-primary' : '' ?>">
                                                <td>
                                                    <?= htmlspecialchars($member->full_name) ?>
                                                    <?php if($member->id == $_SESSION['related_id']): ?>
                                                    <span class="badge badge-info">You</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($member->student_id) ?></td>
                                                <td><?= htmlspecialchars($member->email) ?></td>
                                                <td>
                                                    <?php if($member->id == $_SESSION['related_id']): ?>
                                                    <span class="badge badge-primary">Current User</span>
                                                    <?php else: ?>
                                                    <span class="badge badge-secondary">Member</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats Sidebar -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h4>Submission Stats</h4>
                            </div>
                            <div class="card-body">
                                <?php
                                $totalSubmissions = count($groupSubmissions);
                                $approvedSubmissions = 0;
                                $pendingSubmissions = 0;
                                $rejectedSubmissions = 0;
                                
                                foreach ($groupSubmissions as $submission) {
                                    if ($submission->status === 'Approved') $approvedSubmissions++;
                                    elseif ($submission->status === 'Pending') $pendingSubmissions++;
                                    elseif ($submission->status === 'Rejected') $rejectedSubmissions++;
                                }
                                ?>
                                
                                <div class="mb-3">
                                    <h6>Total Submissions: <?= $totalSubmissions ?></h6>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-success" style="width: <?= $totalSubmissions > 0 ? ($approvedSubmissions/$totalSubmissions)*100 : 0 ?>%"></div>
                                        <div class="progress-bar bg-warning" style="width: <?= $totalSubmissions > 0 ? ($pendingSubmissions/$totalSubmissions)*100 : 0 ?>%"></div>
                                        <div class="progress-bar bg-danger" style="width: <?= $totalSubmissions > 0 ? ($rejectedSubmissions/$totalSubmissions)*100 : 0 ?>%"></div>
                                    </div>
                                    <div class="mt-1">
                                        <small class="text-success">Approved: <?= $approvedSubmissions ?></small>
                                        <small class="text-warning ml-2">Pending: <?= $pendingSubmissions ?></small>
                                        <small class="text-danger ml-2">Rejected: <?= $rejectedSubmissions ?></small>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <h6>Your Contributions</h6>
                                    <?php
                                    $userSubmissions = array_filter($groupSubmissions, function($sub) {
                                        return $sub->student_id == $_SESSION['related_id'];
                                    });
                                    $userCount = count($userSubmissions);
                                    ?>
                                    <p class="mb-1">Chapters submitted: <?= $userCount ?></p>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-info" style="width: <?= $totalSubmissions > 0 ? ($userCount/$totalSubmissions)*100 : 0 ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Defense Status -->
                        <?php if($student->defense_status): ?>
                        <div class="card mt-4">
                            <div class="card-header">
                                <h4>Defense Status</h4>
                            </div>
                            <div class="card-body">
                                <span class="badge badge-<?= 
                                    $student->defense_status === 'Completed' ? 'success' :
                                    ($student->defense_status === 'Scheduled' ? 'info' :
                                    ($student->defense_status === 'Pending' ? 'warning' : 'secondary'))
                                ?>">
                                    <?= $student->defense_status ?>
                                </span>
                                <?php if($student->defense_status === 'Scheduled'): ?>
                                <p class="mt-2 mb-0">
                                    <small class="text-muted">Please check the defenses page for schedule details.</small>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Group Submissions Table -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h4>Group Chapter Submissions</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Chapter</th>
                                        <th>Submitted By</th>
                                        <th>Status</th>
                                        <th>Supervisor Comments</th>
                                        <th>Feedback</th>
                                        <th>File</th>
                                        <th>Submitted At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($groupSubmissions): ?>
                                        <?php foreach ($groupSubmissions as $s): ?>
                                            <tr class="<?= $s->student_id == $_SESSION['related_id'] ? 'table-info' : '' ?>">
                                                <td><?= htmlspecialchars($s->chapter_name) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($s->student_name) ?>
                                                    (<?= $s->student_id ?>)
                                                    <?php if($s->student_id == $_SESSION['related_id']): ?>
                                                    <span class="badge badge-info">You</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= 
                                                        $s->status === 'Approved' ? 'success' :
                                                        ($s->status === 'Rejected' ? 'danger' : 'warning') ?>">
                                                        <?= $s->status ?>
                                                    </span>
                                                </td>
                                                <td><?= nl2br(htmlspecialchars($s->comments ?? '-')) ?></td>
                                                <td><?= nl2br(htmlspecialchars($s->feedback ?? '-')) ?></td>
                                                <td>
                                                    <a href="<?= htmlspecialchars($s->file_path) ?>" 
                                                       target="_blank" 
                                                       class="btn btn-sm btn-outline-primary"
                                                       rel="noopener noreferrer">
                                                        <i class="fas fa-download"></i> Download
                                                    </a>
                                                </td>
                                                <td><?= date('M d, Y H:i', strtotime($s->submitted_at)) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fas fa-folder-open fa-2x mb-2"></i>
                                                <p>No chapter submissions yet for this group.</p>
                                                <button class="btn btn-primary" data-toggle="modal" data-target="#chapterModal">
                                                    <i class="fas fa-upload"></i> Submit First Chapter
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Chapter Submission Modal -->
    <div class="modal fade" id="chapterModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <form id="chapterSubmitForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Submit Chapter</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Select Chapter *</label>
                                    <select name="chapter_name" class="form-control" required>
                                        <option value="">-- Select Chapter --</option>
                                        <option value="Chapter 1: Introduction">Chapter 1: Introduction</option>
                                        <option value="Chapter 2: Literature Review">Chapter 2: Literature Review</option>
                                        <option value="Chapter 3: Methodology">Chapter 3: Methodology</option>
                                        <option value="Chapter 4: Results & Analysis">Chapter 4: Results & Analysis</option>
                                        <option value="Chapter 5: Conclusion">Chapter 5: Conclusion</option>
                                        <option value="Abstract">Abstract</option>
                                        <option value="Full Draft">Full Draft</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Upload File *</label>
                                    <div class="custom-file">
                                        <input type="file" name="project_file" class="custom-file-input" required 
                                               accept=".pdf,.docx,.doc">
                                        <label class="custom-file-label">Choose file (PDF or DOCX, max 10MB)</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Comments (optional)</label>
                            <textarea name="comments" class="form-control" rows="3" 
                                      placeholder="Add any notes or comments about this submission..."></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            This submission will be visible to all group members and your supervisor.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Submit Chapter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php include '../components/footer.php'; ?>
    <?php include '../components/script.php'; ?>

    <script>
    $(document).ready(function () {
        // Update custom file input label
        $('.custom-file-input').on('change', function() {
            let fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass("selected").html(fileName);
        });

        $('#chapterSubmitForm').submit(function (e) {
            e.preventDefault();
            
            // Validate file size
            const fileInput = $('input[name="project_file"]')[0];
            if (fileInput.files[0] && fileInput.files[0].size > 10 * 1024 * 1024) {
                Swal.fire('Error!', 'File size must be less than 10MB', 'error');
                return false;
            }

            Swal.fire({
                title: 'Submitting Chapter...',
                text: 'Please wait while we upload your chapter...',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => Swal.showLoading()
            });

            var formData = new FormData(this);
            $.ajax({
                url: 'submit_chapter.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            title: 'Success!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            $('#chapterModal').modal('hide');
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error("XHR Response Text:", xhr.responseText);
                    console.error("XHR Status:", status);
                    console.error("Error:", error);
                    
                    let errorMessage = 'An error occurred while submitting. ';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMessage = response.message || errorMessage;
                    } catch (e) {
                        errorMessage += 'Please try again.';
                    }
                    
                    Swal.fire('Error!', errorMessage, 'error');
                }
            });
        });
    });
    </script>
</div>