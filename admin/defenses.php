<?php
$title = "Manage Defense";

require_once '../includes/auth_check.php';
require_once '../components/head.php';
require_once '../config/db_connect.php'; 

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check for alert messages
$alertMessage = '';
$alertType = '';
if (isset($_SESSION['alert'])) {
    $alertMessage = $_SESSION['alert']['message'];
    $alertType = $_SESSION['alert']['type'];
    unset($_SESSION['alert']);
}

// Authorization check
if (!in_array($_SESSION['user_role'], ['Admin', 'Coordinator'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Fetch scheduled defenses
$defenses = $db->query("
    SELECT d.*, 
           s.full_name as student_name,
           p.project_name,
           sp.full_name as supervisor_name,
           d.venue as venue_name
    FROM defense_schedule d
    JOIN students s ON d.student_id = s.id
    JOIN projects p ON d.project_id = p.id
    JOIN supervisors sp ON d.supervisor_id = sp.id
    ORDER BY d.scheduled_date
")->fetchAll();

// Fetch available students with approved projects
$students = $db->query("
    SELECT s.id, s.full_name, s.student_id, p.project_name, p.id as project_id
    FROM students s
    JOIN projects p ON s.project_id = p.id
    JOIN project_assignments pa ON s.id = pa.student_id
    WHERE pa.status = 'Approved'
    AND s.id NOT IN (
        SELECT student_id FROM defense_schedule 
        WHERE status NOT IN ('Completed', 'Cancelled')
    )
    ORDER BY s.full_name
")->fetchAll();

// Fetch available projects with multiple students
$groupProjects = $db->query("
    SELECT p.id as project_id, p.project_name, 
           COUNT(s.id) as student_count
    FROM projects p
    JOIN students s ON p.id = s.project_id
    JOIN project_assignments pa ON s.id = pa.student_id
    WHERE pa.status = 'Approved'
    AND p.id NOT IN (
        SELECT DISTINCT project_id FROM defense_schedule 
        WHERE status NOT IN ('Completed', 'Cancelled')
    )
    GROUP BY p.id
    HAVING student_count > 0
    ORDER BY p.project_name
")->fetchAll();

// Fetch available supervisors
$supervisors = $db->query("
    SELECT id, full_name 
    FROM supervisors 
    ORDER BY full_name
")->fetchAll();
?>

<div class="main-wrapper">
    <?php include '../components/navbar.php'; ?>
    <?php include '../components/sidebar.php'; ?>

    <div class="main-content">
        <!-- Alert Messages -->
        <?php if ($alertMessage): ?>
        <div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
            <?= $alertMessage ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <section class="section">
            <div class="section-header">
                <h1>Defense Scheduling</h1>
                <div class="section-header-breadcrumb">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#scheduleDefenseModal">
                        <i class="fas fa-calendar-plus"></i> Schedule Defense
                    </button>
                </div>
            </div>

            <div class="section-body">
                <!-- Calendar View -->
                <div class="card">
                    <div class="card-header">
                        <h4>Defense Calendar</h4>
                    </div>
                    <div class="card-body">
                        <div id="defenseCalendar"></div>
                    </div>
                </div>

                <!-- List View -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h4>Upcoming Defenses</h4>
                    </div>
                    <div class="card-body">
                        <!-- Debug info (remove after testing) -->
                        <!--                   <div class="card mt-4">
                            <div class="card-header">
                                <h4>Debug Information</h4>
                            </div>
                            <div class="card-body">
                                <h5>Students with Projects:</h5>
                                <pre><?php print_r($students); ?></pre>
                                
                                <h5>Group Projects:</h5>
                                <pre><?php print_r($groupProjects); ?></pre>
                            </div>
                        </div> -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="defensesTable" style="width:100%;">
                                <thead>
                                    <tr>
                                        <th>Student(s)</th>
                                        <th>Project</th>
                                        <th>Date & Time</th>
                                        <th>Venue</th>
                                        <th>Supervisor</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Group defenses by project to show group defenses
                                    $groupedDefenses = [];
                                    foreach ($defenses as $defense) {
                                        if (!isset($groupedDefenses[$defense->project_id])) {
                                            $groupedDefenses[$defense->project_id] = [
                                                'project_name' => $defense->project_name,
                                                'scheduled_date' => $defense->scheduled_date,
                                                'venue_name' => $defense->venue_name,
                                                'supervisor_name' => $defense->supervisor_name,
                                                'status' => $defense->status,
                                                'students' => [],
                                                'defense_ids' => []
                                            ];
                                        }
                                        $groupedDefenses[$defense->project_id]['students'][] = $defense->student_name;
                                        $groupedDefenses[$defense->project_id]['defense_ids'][] = $defense->id;
                                    }
                                    
                                    foreach ($groupedDefenses as $projectId => $defenseInfo): 
                                        $studentCount = count($defenseInfo['students']);
                                        $isGroup = $studentCount > 1;
                                    ?>
                                        <?php
                                        $statusClass = [
                                            'Pending' => 'badge-warning',
                                            'Scheduled' => 'badge-info',
                                            'Completed' => 'badge-success',
                                            'Cancelled' => 'badge-danger',
                                            'Postponed' => 'badge-secondary'
                                        ];
                                        ?>
                                        <tr>
                                            <td>
                                                <?php if ($isGroup): ?>
                                                    <span class="badge badge-info">Group (<?= $studentCount ?> students)</span>
                                                    <button class="btn btn-sm btn-link view-students" 
                                                            data-students="<?= htmlspecialchars(implode(', ', $defenseInfo['students'])) ?>">
                                                        <i class="fas fa-users"></i> View Students
                                                    </button>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($defenseInfo['students'][0]) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($defenseInfo['project_name']) ?></td>
                                            <td><?= date('M j, Y H:i', strtotime($defenseInfo['scheduled_date'])) ?></td>
                                            <td><?= htmlspecialchars($defenseInfo['venue_name']) ?></td>
                                            <td><?= htmlspecialchars($defenseInfo['supervisor_name']) ?></td>
                                            <td>
                                                <span class="badge <?= $statusClass[$defenseInfo['status']] ?? 'badge-secondary' ?>">
                                                    <?= $defenseInfo['status'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="edit_defense.php?ids=<?= implode(',', $defenseInfo['defense_ids']) ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="api/cancel_defense.php?ids=<?= implode(',', $defenseInfo['defense_ids']) ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to cancel this defense?')"
                                                   <?= in_array($defenseInfo['status'], ['Completed', 'Cancelled']) ? 'disabled' : '' ?>>
                                                    <i class="fas fa-times"></i>
                                                </a>
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

    <!-- Schedule Defense Modal -->
    <div class="modal fade" id="scheduleDefenseModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Schedule Defense</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="defenseForm" method="POST" action="api/schedule_defense.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <div class="modal-body">
                        <ul class="nav nav-pills mb-3" id="defenseTypeTab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="individual-tab" data-toggle="tab" href="#individual" role="tab">
                                    Individual Defense
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="group-tab" data-toggle="tab" href="#group" role="tab">
                                    Group Defense
                                </a>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="defenseTypeTabContent">
                            <!-- Individual Defense Tab -->
                            <div class="tab-pane fade show active" id="individual" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Student</label>
                                            <select name="student_id" class="form-control select2" id="individualStudent">
                                                <option value="">Select Student</option>
                                                <?php foreach ($students as $student): ?>
                                                    <option value="<?= $student->id ?>" 
                                                        data-project-id="<?= $student->project_id ?>" 
                                                        data-project-name="<?= htmlspecialchars($student->project_name) ?>">
                                                        <?= htmlspecialchars($student->full_name) ?> (<?= $student->student_id ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Project</label>
                                            <input type="text" class="form-control" id="individualProject" readonly>
                                            <input type="hidden" name="project_id" id="individualProjectId">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Group Defense Tab -->
                            <div class="tab-pane fade" id="group" role="tabpanel">
                                <div class="form-group">
                                    <label>Select Project (Group)</label>
                                    <select name="group_project_id" class="form-control select2" id="groupProject">
                                        <option value="">Select Project</option>
                                        <?php foreach ($groupProjects as $project): ?>
                                            <option value="<?= $project->project_id ?>">
                                                <?= htmlspecialchars($project->project_name) ?> - 
                                                (<?= $project->student_count ?> students)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Students in this Project</label>
                                    <div id="groupStudentsList" class="border p-3" style="max-height: 200px; overflow-y: auto;">
                                        <p class="text-muted">Select a project to view students</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Date & Time</label>
                                    <input type="datetime-local" name="scheduled_date" class="form-control" required 
                                           min="<?= date('Y-m-d\TH:i') ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Venue</label>
                                    <input type="text" name="venue" class="form-control" required
                                           placeholder="Enter venue (e.g., Building A, Room 101)">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Supervisor</label>
                                    <select name="supervisor_id" class="form-control select2" required>
                                        <option value="">Select Supervisor</option>
                                        <?php foreach ($supervisors as $supervisor): ?>
                                            <option value="<?= $supervisor->id ?>">
                                                <?= htmlspecialchars($supervisor->full_name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Defense Type</label>
                                    <select name="defense_type" class="form-control" required>
                                        <option value="Proposal">Proposal Defense</option>
                                        <option value="Final">Final Defense</option>
                                        <option value="Progress">Progress Review</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Additional Notes</label>
                            <textarea name="notes" class="form-control" rows="3" maxlength="500"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calendar-check"></i> Schedule Defense
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Students Modal -->
    <div class="modal fade" id="viewStudentsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Students in Defense</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <ul id="studentsList" class="list-group"></ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../components/footer.php'; ?>
    <?php include '../components/script.php'; ?>

    <!-- FullCalendar JS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>

    <script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        width: '100%',
        dropdownParent: $('#scheduleDefenseModal')
    });

    // Initialize DataTable
    $('#defensesTable').DataTable({
        responsive: true,
        order: [[2, 'asc']]
    });

    // When individual student is selected, update project info
    $('#individualStudent').change(function() {
        const selectedOption = $(this).find(':selected');
        const projectName = selectedOption.data('project-name');
        const projectId = selectedOption.data('project-id');
        
        $('#individualProject').val(projectName || 'No project assigned');
        $('#individualProjectId').val(projectId || '');
    });

    // When group project is selected, fetch and display students
    $('#groupProject').change(function() {
        const projectId = $(this).val();
        if (projectId) {
            // Show loading message
            $('#groupStudentsList').html('<p class="text-muted">Loading students...</p>');
            
            $.ajax({
                url: 'api/get_project_students.php',
                type: 'GET',
                data: { project_id: projectId },
                dataType: 'json',
                success: function(response) {
                    console.log('API Response:', response); // Debug log
                    if (response.status === 'success') {
                        let html = '';
                        if (response.students && response.students.length > 0) {
                            response.students.forEach(student => {
                                html += `<div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="student_ids[]" 
                                           value="${student.id}" id="student_${student.id}" checked>
                                    <label class="form-check-label" for="student_${student.id}">
                                        ${student.full_name} (${student.student_id})
                                    </label>
                                </div>`;
                            });
                        } else {
                            html = '<p class="text-danger">No students found for this project</p>';
                        }
                        $('#groupStudentsList').html(html);
                    } else {
                        $('#groupStudentsList').html('<p class="text-danger">Error: ' + (response.message || 'Unknown error') + '</p>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error); // Debug log
                    $('#groupStudentsList').html('<p class="text-danger">Error loading students: ' + error + '</p>');
                }
            });
        } else {
            $('#groupStudentsList').html('<p class="text-muted">Select a project to view students</p>');
        }
    });

    // View students button
    $('.view-students').click(function() {
        const students = $(this).data('students').split(',');
        let html = '';
        students.forEach(student => {
            html += `<li class="list-group-item">${student.trim()}</li>`;
        });
        $('#studentsList').html(html);
        $('#viewStudentsModal').modal('show');
    });

    // Form submission handling
    $('#defenseForm').submit(function(e) {
        e.preventDefault();
        
        // Determine if it's individual or group defense
        const isIndividual = $('#individual-tab').hasClass('active');
        const formData = new FormData(this);
        
        if (isIndividual) {
            const studentId = $('#individualStudent').val();
            const projectId = $('#individualProjectId').val();
            
            if (!studentId) {
                alert('Please select a student');
                return false;
            }
            if (!projectId) {
                alert('Selected student does not have a project assigned');
                return false;
            }
            
            formData.append('student_ids[]', studentId);
            formData.set('project_id', projectId);
        } else {
            const projectId = $('#groupProject').val();
            if (!projectId) {
                alert('Please select a project');
                return false;
            }
            
            // Get all selected student IDs
            const studentIds = [];
            $('#groupStudentsList input[name="student_ids[]"]:checked').each(function() {
                studentIds.push($(this).val());
            });
            
            if (studentIds.length === 0) {
                alert('Please select at least one student');
                return false;
            }
            
            // Clear existing student_ids and add the selected ones
            formData.delete('student_ids[]');
            studentIds.forEach(id => {
                formData.append('student_ids[]', id);
            });
            
            formData.set('project_id', projectId);
        }
        
        // Submit form via AJAX
        $.ajax({
            url: 'api/schedule_defense.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
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
            error: function(xhr, status, error) {
                Swal.fire('Error!', 'Failed to schedule defense: ' + error, 'error');
            }
        });
    });

    // Initialize Calendar
    const calendarEl = document.getElementById('defenseCalendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: [
            <?php foreach ($defenses as $defense): ?>
            {
                title: '<?= addslashes($defense->student_name) ?> - <?= addslashes($defense->project_name) ?>',
                start: '<?= date('Y-m-d\TH:i:s', strtotime($defense->scheduled_date)) ?>',
                extendedProps: {
                    venue: '<?= addslashes($defense->venue_name) ?>',
                    supervisor: '<?= addslashes($defense->supervisor_name) ?>',
                    status: '<?= $defense->status ?>'
                },
                backgroundColor: 
                    '<?= $defense->status === 'Completed' ? '#28a745' : 
                      ($defense->status === 'Pending' ? '#ffc107' : 
                      ($defense->status === 'Cancelled' ? '#dc3545' : '#17a2b8')) ?>'
            },
            <?php endforeach; ?>
        ],
        eventClick: function(info) {
            const event = info.event;
            alert(
                `Defense Details:\n\n` +
                `Student: ${event.title}\n` +
                `Date: ${event.start.toLocaleString()}\n` +
                `Venue: ${event.extendedProps.venue}\n` +
                `Supervisor: ${event.extendedProps.supervisor}\n` +
                `Status: ${event.extendedProps.status}`
            );
        }
    });
    calendar.render();
});
</script>
</div>