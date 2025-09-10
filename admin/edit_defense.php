<?php
$title = "Edit Defense Schedule";

require_once '../includes/auth_check.php';
require_once '../config/db_connect.php';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Authorization check
if (!in_array($_SESSION['user_role'], ['Admin', 'Coordinator'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Get defense IDs from URL
$defenseIds = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];
if (empty($defenseIds)) {
    header("Location: defenses.php?error=no_defense_selected");
    exit();
}

// Fetch defense details
$defenseStmt = $db->prepare("
    SELECT d.*, 
           s.full_name as student_name,
           s.student_id,
           p.project_name,
           sp.full_name as supervisor_name
    FROM defense_schedule d
    JOIN students s ON d.student_id = s.id
    JOIN projects p ON d.project_id = p.id
    JOIN supervisors sp ON d.supervisor_id = sp.id
    WHERE d.id = ?
");

$defenses = [];
$projectId = null;
$studentIds = [];
$defenseData = null;

foreach ($defenseIds as $id) {
    $defenseStmt->execute([$id]);
    $defense = $defenseStmt->fetch();
    
    if ($defense) {
        $defenses[] = $defense;
        $projectId = $defense->project_id;
        $studentIds[] = $defense->student_id;
        
        // Use first defense record for common data
        if (!$defenseData) {
            $defenseData = $defense;
        }
    }
}

if (empty($defenses)) {
    header("Location: defenses.php?error=defense_not_found");
    exit();
}

$isGroupDefense = count($defenses) > 1;

// Fetch all available supervisors
$supervisors = $db->query("
    SELECT id, full_name 
    FROM supervisors 
    ORDER BY full_name
")->fetchAll();

// Fetch all students in this project (for group defense editing)
$projectStudents = [];
if ($isGroupDefense && $projectId) {
    $projectStudents = $db->prepare("
        SELECT s.id, s.full_name, s.student_id
        FROM students s
        WHERE s.project_id = ?
        ORDER BY s.full_name
    ");
    $projectStudents->execute([$projectId]);
    $projectStudents = $projectStudents->fetchAll();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'CSRF token validation failed'
        ];
        header("Location: edit_defense.php?ids=" . implode(',', $defenseIds));
        exit();
    }
    
    $scheduledDate = $_POST['scheduled_date'];
    $venue = $_POST['venue'];
    $supervisorId = (int)$_POST['supervisor_id'];
    $defenseType = $_POST['defense_type'];
    $status = $_POST['status'];
    $notes = $_POST['notes'] ?? '';
    
    // For group defense, get selected student IDs
    $selectedStudentIds = [];
    if ($isGroupDefense) {
        $selectedStudentIds = $_POST['student_ids'] ?? [];
    } else {
        $selectedStudentIds = [$defenses[0]->student_id];
    }
    
    try {
        $db->beginTransaction();
        
        // Update common defense details
        $updateStmt = $db->prepare("
            UPDATE defense_schedule 
            SET scheduled_date = ?, venue = ?, supervisor_id = ?, 
                defense_type = ?, status = ?, notes = ?
            WHERE id = ?
        ");
        
        foreach ($defenses as $defense) {
            // Only update if this student is still selected (for group defense)
            if (in_array($defense->student_id, $selectedStudentIds)) {
                $updateStmt->execute([
                    $scheduledDate, $venue, $supervisorId, 
                    $defenseType, $status, $notes, $defense->id
                ]);
            }
        }
        
        // For group defense, add new students if selected
        if ($isGroupDefense) {
            foreach ($selectedStudentIds as $studentId) {
                $studentId = (int)$studentId;
                
                // Check if this student already has a defense record
                $exists = false;
                foreach ($defenses as $defense) {
                    if ($defense->student_id == $studentId) {
                        $exists = true;
                        break;
                    }
                }
                
                // Create new defense record for this student
                if (!$exists) {
                    $insertStmt = $db->prepare("
                        INSERT INTO defense_schedule 
                        (student_id, project_id, scheduled_date, venue, supervisor_id, defense_type, status, notes)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $insertStmt->execute([
                        $studentId, $projectId, $scheduledDate, $venue, 
                        $supervisorId, $defenseType, $status, $notes
                    ]);
                }
            }
            
            // Remove defense records for deselected students
            foreach ($defenses as $defense) {
                if (!in_array($defense->student_id, $selectedStudentIds)) {
                    $deleteStmt = $db->prepare("DELETE FROM defense_schedule WHERE id = ?");
                    $deleteStmt->execute([$defense->id]);
                }
            }
        }
        
        $db->commit();
        
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'Defense schedule updated successfully'
        ];
        header("Location: defenses.php");
        exit();
        
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Error updating defense: ' . $e->getMessage()
        ];
        header("Location: edit_defense.php?ids=" . implode(',', $defenseIds));
        exit();
    }
}

// Check for alert messages - Moved this AFTER all header redirects
$alertMessage = '';
$alertType = '';
if (isset($_SESSION['alert'])) {
    $alertMessage = $_SESSION['alert']['message'];
    $alertType = $_SESSION['alert']['type'];
    unset($_SESSION['alert']);
}
require_once '../components/head.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title><?= $title ?> - Project Allocation System</title>
    
    <!-- General CSS Files -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css">
    
    <!-- Template CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/components.css">
</head>

<body>
<div class="main-wrapper">
    <div class="navbar-bg"></div>
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
                <h1>Edit Defense Schedule</h1>
                <div class="section-header-breadcrumb">
                    <a href="defenses.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Defenses
                    </a>
                </div>
            </div>

            <div class="section-body">
                <div class="card">
                    <div class="card-header">
                        <h4><?= $isGroupDefense ? 'Group Defense' : 'Individual Defense' ?></h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            
                            <!-- Students Section -->
                            <div class="form-group">
                                <label><?= $isGroupDefense ? 'Students' : 'Student' ?></label>
                                <?php if ($isGroupDefense): ?>
                                    <div class="border p-3" style="max-height: 200px; overflow-y: auto;">
                                        <?php foreach ($projectStudents as $student): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="student_ids[]" 
                                                       value="<?= $student->id ?>" id="student_<?= $student->id ?>"
                                                       <?= in_array($student->id, $studentIds) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="student_<?= $student->id ?>">
                                                    <?= htmlspecialchars($student->full_name) ?> (<?= $student->student_id ?>)
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <input type="text" class="form-control" 
                                           value="<?= htmlspecialchars($defenses[0]->student_name) ?> (<?= $defenses[0]->student_id ?>)" 
                                           readonly>
                                    <input type="hidden" name="student_ids[]" value="<?= $defenses[0]->student_id ?>">
                                <?php endif; ?>
                            </div>

                            <!-- Project Info -->
                            <div class="form-group">
                                <label>Project</label>
                                <input type="text" class="form-control" 
                                       value="<?= htmlspecialchars($defenseData->project_name) ?>" readonly>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Date & Time</label>
                                        <input type="datetime-local" name="scheduled_date" class="form-control" 
                                               value="<?= date('Y-m-d\TH:i', strtotime($defenseData->scheduled_date)) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Venue</label>
                                        <input type="text" name="venue" class="form-control" 
                                               value="<?= htmlspecialchars($defenseData->venue) ?>" required
                                               placeholder="Enter venue (e.g., Building A, Room 101)">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Supervisor</label>
                                        <select name="supervisor_id" class="form-control" required>
                                            <option value="">Select Supervisor</option>
                                            <?php foreach ($supervisors as $supervisor): ?>
                                                <option value="<?= $supervisor->id ?>" 
                                                    <?= $supervisor->id == $defenseData->supervisor_id ? 'selected' : '' ?>>
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
                                            <option value="Proposal" <?= $defenseData->defense_type == 'Proposal' ? 'selected' : '' ?>>Proposal Defense</option>
                                            <option value="Final" <?= $defenseData->defense_type == 'Final' ? 'selected' : '' ?>>Final Defense</option>
                                            <option value="Progress" <?= $defenseData->defense_type == 'Progress' ? 'selected' : '' ?>>Progress Review</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select name="status" class="form-control" required>
                                            <option value="Pending" <?= $defenseData->status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Scheduled" <?= $defenseData->status == 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                            <option value="Completed" <?= $defenseData->status == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="Cancelled" <?= $defenseData->status == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            <option value="Postponed" <?= $defenseData->status == 'Postponed' ? 'selected' : '' ?>>Postponed</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Additional Notes</label>
                                <textarea name="notes" class="form-control" rows="3" maxlength="500"><?= htmlspecialchars($defenseData->notes) ?></textarea>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Defense Schedule
                                </button>
                                <a href="defenses.php" class="btn btn-secondary">Cancel</a>
                                
                                <?php if ($defenseData->status !== 'Completed' && $defenseData->status !== 'Cancelled'): ?>
                                <a href="api/cancel_defense.php?ids=<?= implode(',', $defenseIds) ?>" 
                                   class="btn btn-danger float-right"
                                   onclick="return confirm('Are you sure you want to cancel this defense?')">
                                    <i class="fas fa-times"></i> Cancel Defense
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Defense History -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h4>Defense History</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Previous Date</th>
                                        <th>Previous Venue</th>
                                        <th>Previous Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($defenses as $defense): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($defense->student_name) ?></td>
                                        <td><?= date('M j, Y H:i', strtotime($defense->scheduled_date)) ?></td>
                                        <td><?= htmlspecialchars($defense->venue) ?></td>
                                        <td>
                                            <span class="badge badge-<?= 
                                                $defense->status === 'Completed' ? 'success' : 
                                                ($defense->status === 'Cancelled' ? 'danger' : 
                                                ($defense->status === 'Scheduled' ? 'info' : 'warning'))
                                            ?>">
                                                <?= $defense->status ?>
                                            </span>
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

    <?php include '../components/footer.php'; ?>
    
    <!-- General JS Scripts -->
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.nicescroll/3.7.6/jquery.nicescroll.min.js"></script>
    
    <!-- Template JS File -->
    <script src="../assets/js/stisla.js"></script>
    <script src="../assets/js/scripts.js"></script>

    <script>
    $(document).ready(function() {
        // Form validation
        $('form').submit(function(e) {
            const studentCheckboxes = $('input[name="student_ids[]"]:checked');
            if (studentCheckboxes.length === 0) {
                e.preventDefault();
                alert('Please select at least one student');
                return false;
            }
        });

        // Date validation - cannot select past dates
        $('input[name="scheduled_date"]').attr('min', new Date().toISOString().slice(0, 16));
    });
    </script>
</div>
</body>
</html>