<?php
$title = "Dashboard";

require_once '../includes/auth_check.php';
require_once '../components/head.php';

// Count stats with fetchColumn() for simplicity
$stats = [
    'students'   => (int) $db->query("SELECT COUNT(*) FROM students")->fetchColumn(),
    'supervisors'=> (int) $db->query("SELECT COUNT(*) FROM supervisors")->fetchColumn(),
    'projects'   => (int) $db->query("SELECT COUNT(*) FROM projects")->fetchColumn(),
    'allocated'  => (int) $db->query("SELECT COUNT(*) FROM project_assignments WHERE status = 'Approved'")->fetchColumn(),
    'defenses'   => (int) $db->query("SELECT COUNT(*) FROM defense_schedule")->fetchColumn(),
    'completed'  => (int) $db->query("SELECT COUNT(*) FROM defense_schedule WHERE status = 'Completed'")->fetchColumn(),
    'pending'    => (int) $db->query("SELECT COUNT(*) FROM defense_schedule WHERE status = 'Pending'")->fetchColumn(),
    'submissions'=> (int) $db->query("SELECT COUNT(*) FROM project_chapter_submissions")->fetchColumn()
];

// Get recent activities
$recentActivities = $db->query("
    (SELECT 'defense' as type, ds.scheduled_date as date, 
            CONCAT('Defense scheduled for ', s.full_name) as description,
            ds.created_at
     FROM defense_schedule ds
     JOIN students s ON ds.student_id = s.id
     ORDER BY ds.created_at DESC LIMIT 3)
     
    UNION ALL
     
    (SELECT 'assignment' as type, pa.assigned_at as date,
            CONCAT('Project assigned to ', s.full_name) as description,
            pa.assigned_at as created_at
     FROM project_assignments pa
     JOIN students s ON pa.student_id = s.id
     ORDER BY pa.assigned_at DESC LIMIT 3)
     
    UNION ALL
     
    (SELECT 'submission' as type, ps.submitted_at as date,
            CONCAT('Project submitted by ', s.full_name) as description,
            ps.submitted_at as created_at
     FROM project_chapter_submissions ps
     JOIN students s ON ps.student_id = s.id
     ORDER BY ps.submitted_at DESC LIMIT 3)
     
    ORDER BY created_at DESC LIMIT 7
")->fetchAll();

// Calculate percentages safely
$allocationRate = $stats['students'] > 0 ? ($stats['allocated'] / $stats['students']) * 100 : 0;
$defenseRate    = $stats['defenses'] > 0 ? ($stats['completed'] / $stats['defenses']) * 100 : 0;
$completionRate = $stats['allocated'] > 0 ? ($stats['completed'] / $stats['allocated']) * 100 : 0;

// Get upcoming defenses
$upcomingDefenses = $db->query("
    SELECT ds.*, s.full_name AS student_name, p.project_name, sp.full_name AS supervisor_name
    FROM defense_schedule ds
    JOIN students s ON ds.student_id = s.id
    JOIN projects p ON ds.project_id = p.id
    JOIN supervisors sp ON ds.supervisor_id = sp.id
    WHERE ds.scheduled_date >= NOW() AND ds.status NOT IN ('Completed', 'Cancelled')
    ORDER BY ds.scheduled_date ASC
    LIMIT 5
")->fetchAll();

// Get project status distribution
$projectStatus = $db->query("
    SELECT status, COUNT(*) as count 
    FROM projects 
    GROUP BY status
")->fetchAll();

// Get defense status distribution
$defenseStatus = $db->query("
    SELECT status, COUNT(*) as count 
    FROM defense_schedule 
    GROUP BY status
")->fetchAll();
?>

<div class="main-wrapper main-wrapper-1">
    <div class="navbar-bg"></div>
    <?php include '../components/navbar.php'; ?>
    <?php include '../components/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Admin Dashboard</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active">Dashboard</div>
                    <div class="breadcrumb-item">Overview</div>
                </div>
            </div>

            <div class="section-body">
                <!-- Quick Stats Row -->
                <div class="row">
                    <!-- Total Students -->
                    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-primary">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Total Students</h4>
                                </div>
                                <div class="card-body">
                                    <?= number_format($stats['students']) ?>
                                </div>
                            </div>
                            <div class="card-footer">
                                <small><?= $stats['allocated'] ?> with projects</small>
                            </div>
                        </div>
                    </div>

                    <!-- Supervisors -->
                    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-info">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Supervisors</h4>
                                </div>
                                <div class="card-body">
                                    <?= number_format($stats['supervisors']) ?>
                                </div>
                            </div>
                            <div class="card-footer">
                                <small>Active staff</small>
                            </div>
                        </div>
                    </div>

                    <!-- Projects -->
                    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-warning">
                                <i class="fas fa-project-diagram"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Projects</h4>
                                </div>
                                <div class="card-body">
                                    <?= number_format($stats['projects']) ?>
                                </div>
                            </div>
                            <div class="card-footer">
                                <small><?= $stats['allocated'] ?> allocated</small>
                            </div>
                        </div>
                    </div>

                    <!-- Defenses -->
                    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-success">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Defenses</h4>
                                </div>
                                <div class="card-body">
                                    <?= number_format($stats['defenses']) ?>
                                </div>
                            </div>
                            <div class="card-footer">
                                <small><?= $stats['completed'] ?> completed</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and Detailed Stats Row -->
                <div class="row">
                    <!-- Project Status Chart -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h4>Project Status Distribution</h4>
                            </div>
                            <div class="card-body">
                                <canvas id="projectStatusChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Defense Status Chart -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h4>Defense Status Distribution</h4>
                            </div>
                            <div class="card-body">
                                <canvas id="defenseStatusChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress Overview -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>System Progress Overview</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-4">
                                            <div class="text-small font-weight-bold text-muted float-right">
                                                <?= number_format($allocationRate, 1) ?>%
                                            </div>
                                            <div class="font-weight-bold mb-1">Project Allocation Rate</div>
                                            <div class="progress" data-height="15">
                                                <div class="progress-bar bg-success" style="width: <?= $allocationRate ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?= $stats['allocated'] ?> of <?= $stats['students'] ?> students have projects</small>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-4">
                                            <div class="text-small font-weight-bold text-muted float-right">
                                                <?= number_format($defenseRate, 1) ?>%
                                            </div>
                                            <div class="font-weight-bold mb-1">Defense Completion Rate</div>
                                            <div class="progress" data-height="15">
                                                <div class="progress-bar bg-info" style="width: <?= $defenseRate ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?= $stats['completed'] ?> of <?= $stats['defenses'] ?> defenses completed</small>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-4">
                                            <div class="text-small font-weight-bold text-muted float-right">
                                                <?= number_format($completionRate, 1) ?>%
                                            </div>
                                            <div class="font-weight-bold mb-1">Project Completion Rate</div>
                                            <div class="progress" data-height="15">
                                                <div class="progress-bar bg-warning" style="width: <?= $completionRate ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?= $stats['completed'] ?> of <?= $stats['allocated'] ?> projects defended</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities and Upcoming Defenses -->
                <div class="row">
                    <!-- Upcoming Defenses -->
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header">
                                <h4>Upcoming Defenses</h4>
                                <div class="card-header-action">
                                    <a href="defenses.php" class="btn btn-primary">View All <i class="fas fa-chevron-right"></i></a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Project</th>
                                                <th>Date & Time</th>
                                                <th>Supervisor</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($upcomingDefenses) > 0): ?>
                                                <?php foreach ($upcomingDefenses as $defense): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($defense->student_name) ?></td>
                                                    <td><?= htmlspecialchars($defense->project_name) ?></td>
                                                    <td><?= date('M j, Y H:i', strtotime($defense->scheduled_date)) ?></td>
                                                    <td><?= htmlspecialchars($defense->supervisor_name) ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= 
                                                            $defense->status === 'Scheduled' ? 'info' : 
                                                            ($defense->status === 'Pending' ? 'warning' : 'secondary')
                                                        ?>">
                                                            <?= htmlspecialchars($defense->status) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No upcoming defenses</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header">
                                <h4>Recent Activities</h4>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled list-unstyled-border">
                                    <?php if (count($recentActivities) > 0): ?>
                                        <?php foreach ($recentActivities as $activity): ?>
                                        <li class="media">
                                            <div class="media-icon mr-3">
                                                <?php if ($activity->type === 'defense'): ?>
                                                    <div class="bg-info text-white rounded-circle p-2">
                                                        <i class="fas fa-calendar-alt"></i>
                                                    </div>
                                                <?php elseif ($activity->type === 'assignment'): ?>
                                                    <div class="bg-success text-white rounded-circle p-2">
                                                        <i class="fas fa-tasks"></i>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="bg-warning text-white rounded-circle p-2">
                                                        <i class="fas fa-file-upload"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="media-body">
                                                <h6 class="media-title"><?= htmlspecialchars($activity->description) ?></h6>
                                                <div class="text-small text-muted">
                                                    <?= date('M j, Y g:i A', strtotime($activity->created_at)) ?>
                                                </div>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li class="media">
                                            <div class="media-body">
                                                <h6 class="media-title">No recent activities</h6>
                                            </div>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </div>

    <?php include '../components/footer.php'; ?>
    <?php include '../components/script.php'; ?>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    $(document).ready(function() {
        // Project Status Chart
        const projectStatusCtx = document.getElementById('projectStatusChart').getContext('2d');
        const projectStatusChart = new Chart(projectStatusCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php foreach ($projectStatus as $status): ?>
                        '<?= ucfirst($status->status) ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    data: [
                        <?php foreach ($projectStatus as $status): ?>
                            <?= $status->count ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: [
                        '#6777ef', '#ffa426', '#fc544b', '#63ed7a', '#191d21'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Defense Status Chart
        const defenseStatusCtx = document.getElementById('defenseStatusChart').getContext('2d');
        const defenseStatusChart = new Chart(defenseStatusCtx, {
            type: 'pie',
            data: {
                labels: [
                    <?php foreach ($defenseStatus as $status): ?>
                        '<?= ucfirst($status->status) ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    data: [
                        <?php foreach ($defenseStatus as $status): ?>
                            <?= $status->count ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: [
                        '#6777ef', '#ffa426', '#fc544b', '#63ed7a', '#191d21'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();
    });
    </script>
</div>