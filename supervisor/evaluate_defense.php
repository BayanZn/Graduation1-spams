<?php
require_once '../includes/auth_check.php';
require_once '../components/head.php';

// Get defenses where supervisor is panel member or chair
$defenses = $db->prepare("
    SELECT ds.*, s.full_name as student_name, p.project_name,
           (SELECT GROUP_CONCAT(sp.full_name SEPARATOR ', ') 
            FROM defense_panel dp
            JOIN supervisors sp ON dp.supervisor_id = sp.id
            WHERE dp.defense_id = ds.id) as panel_members
    FROM defense_schedule ds
    JOIN students s ON ds.student_id = s.id
    JOIN projects p ON ds.project_id = p.id
    LEFT JOIN defense_panel dp ON ds.id = dp.defense_id
    WHERE ds.panel_chair_id = ? OR dp.supervisor_id = ?
    GROUP BY ds.id
    ORDER BY ds.scheduled_date DESC
");
$defenses->execute([$_SESSION['related_id'], $_SESSION['related_id']]);
?>

<div class="main-wrapper main-wrapper-1">
    <div class="navbar-bg"></div>
    <?php include '../components/navbar.php'; ?>
    <?php include '../components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Defense Evaluation</h1>
            </div>
            
            <div class="section-body">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="defensesTable">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Project</th>
                                        <th>Date & Time</th>
                                        <th>Panel Members</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($defense = $defenses->fetch()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($defense->student_name) ?></td>
                                        <td><?= htmlspecialchars($defense->project_name) ?></td>
                                        <td><?= date('M j, Y H:i', strtotime($defense->scheduled_date)) ?></td>
                                        <td><?= htmlspecialchars($defense->panel_members) ?></td>
                                        <td>
                                            <span class="badge badge-<?= 
                                                $defense->status === 'Completed' ? 'success' : 
                                                ($defense->status === 'Scheduled' ? 'info' : 'warning')
                                            ?>">
                                                <?= $defense->status ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($defense->status === 'Scheduled' || $defense->status === 'Completed'): ?>
                                                <button class="btn btn-sm btn-primary evaluate-defense" 
                                                        data-id="<?= $defense->id ?>">
                                                    <i class="fas fa-clipboard-check"></i> Evaluate
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    
    <!-- Evaluation Modal -->
    <div class="modal fade" id="evaluationModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Defense Evaluation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="evaluationForm" method="POST" action="api/submit_evaluation.php">
                    <input type="hidden" name="defense_id" id="defenseId">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Presentation Score (1-10)</label>
                                    <input type="number" name="presentation_score" class="form-control" min="1" max="10" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Technical Score (1-10)</label>
                                    <input type="number" name="technical_score" class="form-control" min="1" max="10" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Knowledge Score (1-10)</label>
                                    <input type="number" name="knowledge_score" class="form-control" min="1" max="10" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Overall Score (1-10)</label>
                                    <input type="number" name="overall_score" class="form-control" min="1" max="10" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Strengths</label>
                            <textarea name="strengths" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Weaknesses</label>
                            <textarea name="weaknesses" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Recommendations</label>
                            <textarea name="recommendations" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Final Decision</label>
                            <select name="decision" class="form-control" required>
                                <option value="Pass">Pass</option>
                                <option value="Pass with Minor Revisions">Pass with Minor Revisions</option>
                                <option value="Pass with Major Revisions">Pass with Major Revisions</option>
                                <option value="Fail">Fail</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit Evaluation</button>
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
        $('#defensesTable').DataTable();
        
        // Handle evaluate button click
        $('.evaluate-defense').click(function() {
            const defenseId = $(this).data('id');
            
            // Check if evaluation already exists
            $.ajax({
                url: 'api/get_evaluation.php?defense_id=' + defenseId + '&supervisor_id=<?= $_SESSION['related_id'] ?>',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success' && response.data) {
                        // Load existing evaluation
                        $('#defenseId').val(defenseId);
                        $('input[name="presentation_score"]').val(response.data.presentation_score);
                        $('input[name="technical_score"]').val(response.data.technical_score);
                        $('input[name="knowledge_score"]').val(response.data.knowledge_score);
                        $('input[name="overall_score"]').val(response.data.overall_score);
                        $('textarea[name="strengths"]').val(response.data.strengths);
                        $('textarea[name="weaknesses"]').val(response.data.weaknesses);
                        $('textarea[name="recommendations"]').val(response.data.recommendations);
                        $('select[name="decision"]').val(response.data.decision);
                    } else {
                        // New evaluation
                        $('#defenseId').val(defenseId);
                        $('#evaluationForm')[0].reset();
                    }
                    $('#evaluationModal').modal('show');
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to load evaluation data', 'error');
                }
            });
        });
        
        // Handle evaluation form submission
        $('#evaluationForm').submit(function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Submitting Evaluation',
                text: 'Please wait...',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => Swal.showLoading()
            });
            
            $.ajax({
                url: 'api/submit_evaluation.php',
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
                            $('#evaluationModal').modal('hide');
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to submit evaluation', 'error');
                }
            });
        });
    });
    </script>
</div>