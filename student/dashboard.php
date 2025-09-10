<?php
require_once '../includes/auth_check.php';
require_once '../components/head.php';

// Get student's project and defense info
$stmt = $db->prepare("
   SELECT s.*, p.project_name, p.project_case, 
           ds.scheduled_date, ds.venue, ds.supervisor_id, ds.status as defense_status,
           GROUP_CONCAT(sp.full_name SEPARATOR ', ') as supervisors,
           dsp.full_name as discusser_name
    FROM students s
    LEFT JOIN projects p ON s.project_id = p.id
    LEFT JOIN defense_schedule ds ON s.id = ds.student_id
    LEFT JOIN project_supervision ps ON p.id = ps.project_id
    LEFT JOIN supervisors sp ON ps.supervisor_id = sp.id
    LEFT JOIN supervisors dsp ON ds.supervisor_id = dsp.id
    WHERE s.id = ?
    GROUP BY s.id
");
$stmt->execute([$_SESSION['related_id']]);
$student = $stmt->fetch();

// Get unread messages
$unreadMessages = $db->prepare("
    SELECT COUNT(*) as count 
    FROM messages 
    WHERE receiver_id = ? AND is_read = 0
");
$unreadMessages->execute([$_SESSION['user_id']]);
$unreadMessages = $unreadMessages->fetch()->count;
?>

<div class="main-wrapper main-wrapper-1">
    <div class="navbar-bg"></div>
    <?php include '../components/navbar.php'; ?>
    <?php include '../components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Student Dashboard</h1>
            </div>
            
            <div class="section-body">
                <div class="row">
                    <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-primary">
                                <i class="fas fa-project-diagram"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Project Status</h4>
                                </div>
                                <div class="card-body">
                                    <?= $student && $student->project_id ? 'Assigned' : 'Not Assigned' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-info">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Defense Status</h4>
                                </div>
                                <div class="card-body">
                                    <?= $student && $student->defense_status ? $student->defense_status : 'Not Scheduled' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-warning">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Unread Messages</h4>
                                </div>
                                <div class="card-body">
                                    <?= $unreadMessages ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h4>Project Information</h4>
                            </div>
                            <div class="card-body">
                                <?php if($student && $student->project_id): ?>
                                    <h5><?= htmlspecialchars($student->project_name) ?></h5>
                                    <p><?= htmlspecialchars($student->project_case) ?></p>
                                    
                                    <div class="mb-3">
                                        <h6>Supervisors:</h6>
                                        <p><?= $student->supervisors ? htmlspecialchars($student->supervisors) : 'Not assigned' ?></p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h6>Defense Information:</h6>
                                        <?php if($student->scheduled_date): ?>
                                            <p>
                                                <strong>Date:</strong> 
                                                <?= date('F j, Y \a\t H:i', strtotime($student->scheduled_date)) ?>
                                            </p>
                                            <p><strong>Discusser:</strong> <?= htmlspecialchars($student->discusser_name) ?></p>
                                            <p><strong>Venue:</strong> <?= htmlspecialchars($student->venue) ?></p>
                                            <p>
                                                <strong>Status:</strong> 
                                                <span class="badge badge-<?= 
                                                    $student->defense_status === 'Completed' ? 'success' : 
                                                    ($student->defense_status === 'Scheduled' ? 'info' : 'warning')
                                                ?>">
                                                    <?= $student->defense_status ?>
                                                </span>
                                            </p>
                                        <?php else: ?>
                                            <p class="text-muted">Defense not yet scheduled</p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> Please contact your supervisor for any questions about your project or defense.
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> You haven't been assigned a project yet. Please check back later.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h4>Quick Actions</h4>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <a href="submit_project.php" class="list-group-item list-group-item-action">
                                        <i class="fas fa-file-upload mr-2"></i> Submit Project
                                    </a>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    
    <?php include '../components/footer.php'; ?>
    <?php include '../components/script.php'; ?>
</div>