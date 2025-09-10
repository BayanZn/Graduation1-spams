<?php
require_once '../includes/auth_check.php';
require_once '../components/head.php';

// Get supervisor's details
$stmt = $db->prepare("SELECT * FROM supervisors WHERE id = ?");
$stmt->execute([$_SESSION['related_id']]);
$supervisor = $stmt->fetch();

// Get supervised projects
$projects = $db->prepare("
    SELECT p.*, s.full_name as student_name, 
    ds.scheduled_date, ds.status as defense_status
    FROM project_supervision ps
    JOIN projects p ON ps.project_id = p.id
    LEFT JOIN students s ON p.id = s.project_id
    LEFT JOIN defense_schedule ds ON s.id = ds.student_id
    WHERE ps.supervisor_id = ?
    ORDER BY p.project_name
    ");
$projects->execute([$_SESSION['related_id']]);
?>

<div class="main-wrapper main-wrapper-1">
    <div class="navbar-bg"></div>
    <?php include '../components/navbar.php'; ?>
    <?php include '../components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Supervisor Dashboard</h1>
            </div>
            
            <div class="section-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card profile-widget">
                            <div class="profile-widget-header">
                                <div class="profile-widget-items">
                                    <div class="profile-widget-item">
                                        <div class="profile-widget-item-label">Department</div>
                                        <div class="profile-widget-item-value"><?= htmlspecialchars($supervisor->department) ?></div>
                                    </div>
                                    <div class="profile-widget-item">
                                        <div class="profile-widget-item-label">Projects</div>
                                        <div class="profile-widget-item-value"><?= $projects->rowCount() ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="profile-widget-description">
                                <div class="profile-widget-name">
                                    <?= htmlspecialchars($supervisor->full_name) ?>
                                    <div class="text-muted d-inline font-weight-normal">
                                        <div class="slash"></div> <?= htmlspecialchars($supervisor->staff_id) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h4>Supervised Projects</h4>
                            </div>
                            <div class="card-body">
                                <?php if($projects->rowCount() > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Project</th>
                                                    <th>Student</th>
                                                    <th>Defense Status</th>
                                                    <!-- <th>Actions</th> -->
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($project = $projects->fetch()): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($project->project_name) ?></td>
                                                        <td>
                                                            <?= $project->student_name ? 
                                                            htmlspecialchars($project->student_name) : 
                                                            '<span class="text-muted">Not assigned</span>' ?>
                                                        </td>
                                                        <td>
                                                            <?php if($project->defense_status): ?>
                                                                <span class="badge badge-<?= 
                                                                $project->defense_status === 'Completed' ? 'success' : 
                                                                ($project->defense_status === 'Scheduled' ? 'info' : 'warning')
                                                            ?>">
                                                            <?= $project->defense_status ?>
                                                        </span>
                                                        <?php if($project->scheduled_date): ?>
                                                            <br>
                                                            <small><?= date('M j, Y H:i', strtotime($project->scheduled_date)) ?></small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">Not scheduled</span>
                                                    <?php endif; ?>
                                                </td>
                                                <!-- <td>
                                                    <button class="btn btn-sm btn-primary view-project" 
                                                    data-id="<?= $project->id ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if($project->student_name): ?>
                                                    <button class="btn btn-sm btn-info send-message" 
                                                    data-student="<?= $project->student_name ?>">
                                                    <i class="fas fa-envelope"></i>
                                                </button>
                                            <?php endif; ?> -->
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> You haven't been assigned any projects yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>
</section>
</div>

<?php include '../components/footer.php'; ?>
<?php include '../components/script.php'; ?>

<script>
    $(document).ready(function() {
        // View project details
        $(document).ready(function() {
        // View project details - improved error handling
            $('.view-project').click(function() {
                const projectId = $(this).data('id');
                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                Swal.fire({
                    title: 'Loading Project',
                    html: 'Please wait while we fetch project details...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                $.ajax({
                    url: 'api/get_project.php?id=' + projectId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        $btn.prop('disabled', false).html('<i class="fas fa-eye"></i>');
                        Swal.close();

                        if (response.status === 'success') {
                            const project = response.data.project;
                            const student = response.data.student;
                            const defense = response.data.defense;
                            const submission = response.data.submission;

                            let html = `
                        <div class="text-left">
                            <h5>${project.name}</h5>
                            <p><strong>Case Study:</strong> ${project.case_study || 'Not specified'}</p>
                            <p><strong>Level:</strong> ${project.level || 'Not specified'}</p>
                            <p><strong>Description:</strong> ${project.description || 'Not provided'}</p>
                            <hr>
                            `;

                            if (student) {
                                html += `
                            <h6>Student Information</h6>
                            <p><strong>Name:</strong> ${student.name}</p>
                            <p><strong>Email:</strong> ${student.email}</p>
                                `;
                            } else {
                                html += `<p class="text-muted">No student assigned</p>`;
                            }

                            if (defense) {
                                html += `
                            <hr>
                            <h6>Defense Information</h6>
                            <p><strong>Status:</strong> 
                                <span class="badge ${defense.status === 'Completed' ? 'badge-success' : 
                            defense.status === 'Scheduled' ? 'badge-info' : 'badge-warning'}">
                                ${defense.status}
                                </span>
                            </p>
                            ${defense.scheduled_date ? 
                              `<p><strong>Scheduled:</strong> ${new Date(defense.scheduled_date).toLocaleString()}</p>` : ''}
                          `;
                      }

                      if (submission) {
                        html += `
                            <hr>
                            <h6>Submission Details</h6>
                            <p><strong>Status:</strong> 
                                <span class="badge ${submission.status === 'Approved' ? 'badge-success' : 
                            submission.status === 'Pending' ? 'badge-warning' : 'badge-secondary'}">
                                ${submission.status}
                                </span>
                            </p>
                            ${submission.submitted_at ? 
                              `<p><strong>Submitted:</strong> ${new Date(submission.submitted_at).toLocaleString()}</p>` : ''}
                            ${submission.feedback ? 
                              `<p><strong>Feedback:</strong> ${submission.feedback}</p>` : ''}
                          `;
                      }

                      if (response.data.files.length > 0) {
                        html += `
                            <hr>
                            <h6>Submitted Files</h6>
                            <ul class="list-unstyled">
                            ${response.data.files.map(file => `
                                    <li>
                                        <a href="${file.path}" target="_blank" class="text-primary">
                                            ${file.path.split('/').pop()}
                                        </a>
                                        (${new Date(file.submitted_at).toLocaleDateString()})
                                    </li>
                            `).join('')}
                            </ul>
                        `;
                    }
                    
                    html += `</div>`;
                    
                    Swal.fire({
                        title: 'Project Details',
                        html: html,
                        width: '800px',
                        showConfirmButton: true,
                        showCloseButton: true
                    });
                } else {
                    Swal.fire('Error!', response.message || 'Failed to load project details', 'error');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-eye"></i>');
                let error = xhr.responseJSON || {};
                Swal.fire({
                    title: 'Error!',
                    html: error.message || 'Failed to connect to server',
                    icon: 'error',
                    footer: error.debug ? JSON.stringify(error.debug) : ''
                });
            }
        });
});

    // Rest of your existing code...
});

        // Send message to student
$('.send-message').click(function() {
    const studentName = $(this).data('student');

    Swal.fire({
        title: `Message to ${studentName}`,
        input: 'textarea',
        inputPlaceholder: 'Type your message here...',
        showCancelButton: true,
        confirmButtonText: 'Send',
        showLoaderOnConfirm: true,
        preConfirm: (message) => {
            return $.ajax({
                url: 'api/send_message.php',
                type: 'POST',
                data: {
                    student_name: studentName,
                    message: message
                },
                dataType: 'json'
            }).then(response => {
                if (!response.success) {
                    throw new Error(response.message);
                }
                return response;
            }).catch(error => {
                Swal.showValidationMessage(`Request failed: ${error}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Sent!', 'Your message has been sent.', 'success');
        }
    });
});
});
</script>
</div>