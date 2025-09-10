<?php
require_once '../includes/auth_check.php';
require_once '../components/head.php';

// Only allow admins and coordinators
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
           GROUP_CONCAT(panel.full_name SEPARATOR ', ') as panel_members
    FROM defense_schedule d
    JOIN students s ON d.student_id = s.id
    JOIN projects p ON d.project_id = p.id
    JOIN supervisors sp ON d.supervisor_id = sp.id
    LEFT JOIN defense_panel dp ON d.id = dp.defense_id
    LEFT JOIN supervisors panel ON dp.supervisor_id = panel.id
    GROUP BY d.id
    ORDER BY d.scheduled_date
")->fetchAll();

// Fetch available students with approved projects
$students = $db->query("
    SELECT s.id, s.full_name, p.project_name
    FROM students s
    JOIN projects p ON s.project_id = p.id
    JOIN project_assignments pa ON s.id = pa.student_id
    WHERE pa.status = 'Approved'
    AND s.id NOT IN (SELECT student_id FROM defense_schedule WHERE status != 'Completed')
    ORDER BY s.full_name
")->fetchAll();

// Fetch available supervisors
$supervisors = $db->query("SELECT * FROM supervisors ORDER BY full_name")->fetchAll();

// Fetch available venues
$venues = $db->query("SELECT * FROM venues ORDER BY name")->fetchAll();
?>

<div class="main-wrapper">
    <?php include '../components/navbar.php'; ?>
    <?php include '../components/sidebar.php'; ?>

    <div class="main-content">
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
                        <div class="table-responsive">
                            <table class="table table-striped" id="defensesTable">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Project</th>
                                        <th>Date & Time</th>
                                        <th>Venue</th>
                                        <th>Supervisor</th>
                                        <th>Panel Members</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($defenses as $defense): ?>
                                        <?php
                                        $statusClass = [
                                            'Pending' => 'badge-warning',
                                            'Scheduled' => 'badge-info',
                                            'Completed' => 'badge-success',
                                            'Postponed' => 'badge-danger'
                                        ];
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($defense->student_name) ?></td>
                                            <td><?= htmlspecialchars($defense->project_name) ?></td>
                                            <td><?= date('M j, Y H:i', strtotime($defense->scheduled_date)) ?></td>
                                            <td><?= htmlspecialchars($defense->location) ?></td>
                                            <td><?= htmlspecialchars($defense->supervisor_name) ?></td>
                                            <td><?= $defense->panel_members ?: 'Not assigned' ?></td>
                                            <td>
                                                <span class="badge <?= $statusClass[$defense->status] ?>">
                                                    <?= $defense->status ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary edit-defense" 
                                                        data-id="<?= $defense->id ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger cancel-defense" 
                                                        data-id="<?= $defense->id ?>"
                                                        <?= $defense->status === 'Completed' ? 'disabled' : '' ?>>
                                                    <i class="fas fa-times"></i>
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

    <!-- Schedule Defense Modal -->
    <div class="modal fade" id="scheduleDefenseModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Schedule New Defense</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="defenseForm" method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Student</label>
                                    <select name="student_id" class="form-control select2" required>
                                        <option value="">Select Student</option>
                                        <?php foreach ($students as $student): ?>
                                            <option value="<?= $student->id ?>">
                                                <?= htmlspecialchars($student->full_name) ?> - 
                                                <?= htmlspecialchars($student->project_name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Date & Time</label>
                                    <input type="datetime-local" name="scheduled_date" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Venue</label>
                                    <select name="venue_id" class="form-control select2" required>
                                        <option value="">Select Venue</option>
                                        <?php foreach ($venues as $venue): ?>
                                            <option value="<?= $venue->id ?>">
                                                <?= htmlspecialchars($venue->name) ?> 
                                                (Capacity: <?= $venue->capacity ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
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
                        </div>
                        
                        <div class="form-group">
                            <label>Panel Members (Min 2)</label>
                            <select name="panel_members[]" class="form-control select2" multiple="multiple" required>
                                <?php foreach ($supervisors as $supervisor): ?>
                                    <option value="<?= $supervisor->id ?>">
                                        <?= htmlspecialchars($supervisor->full_name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Additional Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Schedule Defense</button>
                    </div>
                </form>
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
                        venue: '<?= addslashes($defense->location) ?>',
                        supervisor: '<?= addslashes($defense->supervisor_name) ?>',
                        panel: '<?= addslashes($defense->panel_members) ?>'
                    },
                    backgroundColor: 
                        '<?= $defense->status === 'Completed' ? '#28a745' : 
                          ($defense->status === 'Pending' ? '#ffc107' : '#17a2b8') ?>'
                },
                <?php endforeach; ?>
            ],
            eventClick: function(info) {
                const event = info.event;
                Swal.fire({
                    title: event.title,
                    html: `
                        <p><strong>Date:</strong> ${event.start.toLocaleString()}</p>
                        <p><strong>Venue:</strong> ${event.extendedProps.venue}</p>
                        <p><strong>Supervisor:</strong> ${event.extendedProps.supervisor}</p>
                        <p><strong>Panel:</strong> ${event.extendedProps.panel || 'Not assigned'}</p>
                    `,
                    icon: 'info'
                });
            }
        });
        calendar.render();

        // Handle form submission
        $('#defenseForm').submit(function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Scheduling Defense',
                text: 'Please wait...',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: 'api/schedule_defense.php',
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
                    Swal.fire('Error!', 'Failed to schedule defense', 'error');
                }
            });
        });

        // Delete defense
        $('.cancel-defense').click(function() {
            const defenseId = $(this).data('id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "This will cancel the scheduled defense",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, cancel it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'api/cancel_defense.php?id=' + defenseId,
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if(response.status === 'success') {
                                Swal.fire(
                                    'Cancelled!',
                                    response.message,
                                    'success'
                                ).then(() => location.reload());
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Failed to cancel defense', 'error');
                        }
                    });
                }
            });
        });
    });
    </script>
</div>