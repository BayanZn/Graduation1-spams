<?php
require_once '../includes/auth_check.php';
require_once '../components/head.php';

$stmt = $db->prepare("
    SELECT 
        pr.id AS project_id,
        pr.project_name,
        s.id AS student_id,
        s.full_name AS student_name,
        cs.id AS submission_id,
        cs.chapter_name,
        cs.file_path,
        cs.comments AS student_comments,
        cs.feedback,
        cs.status,
        cs.submitted_at
    FROM project_supervision psv
    JOIN projects pr ON psv.project_id = pr.id
    LEFT JOIN students s ON pr.id = s.project_id
    LEFT JOIN project_chapter_submissions cs ON s.id = cs.student_id
    WHERE psv.supervisor_id = ?
    ORDER BY pr.project_name, cs.chapter_name, cs.submitted_at DESC
");
$stmt->execute([$_SESSION['related_id']]);
$rows = $stmt->fetchAll(PDO::FETCH_OBJ);
?>



<div class="main-wrapper main-wrapper-1">
    <div class="navbar-bg"></div>
    <?php include '../components/navbar.php'; ?>
    <?php include '../components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Project Review</h1>
                <div class="section-header-breadcrumb">
                    <div class="badge badge-primary mr-2">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['email'] ?? 'Supervisor') ?>
                    </div>
                </div>
            </div>
            
            <div class="section-body">
                <div class="card">
                  <div class="card-body">
                    <?php if (empty($rows)): ?>
                      <div class="alert alert-info">No chapter submissions yet.</div>
                    <?php else: ?>
                      <div class="table-responsive">
                        <table class="table table-striped" id="chaptersTable">
                          <thead>
                            <tr>
                              <th>Project</th>
                              <th>Student</th>
                              <th>Chapter</th>
                              <th>Status</th>
                              <th>Submitted At</th>
                              <th>Actions</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($rows as $row): ?>
                              <tr>
                                <td><?= htmlspecialchars($row->project_name) ?></td>
                                <td><?= htmlspecialchars($row->student_name ?? '—') ?></td>
                                <td><?= htmlspecialchars($row->chapter_name) ?></td>
                                <td>
                                  <span class="badge badge-<?= 
                                    $row->status === 'Approved' ? 'success' :
                                    ($row->status === 'Rejected' ? 'danger' : 'info') ?>">
                                    <?= $row->status ?>
                                  </span>
                                </td>
                                <td><?= date('M j, Y h:i A', strtotime($row->submitted_at)) ?></td>
                                <td>
                                  <?php if ($row->submission_id): ?>
                                    <button 
                                      class="btn btn-sm btn-primary review-chapter"
                                      data-id="<?= $row->submission_id ?>">
                                      <i class="fas fa-eye"></i> Review
                                    </button>
                                  <?php else: ?>
                                    <span class="text-muted">No submission</span>
                                  <?php endif; ?>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>

            </div>
        </section>
    </div>
    
    <!-- Chapter Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form id="reviewForm" method="POST" action="api/review_chapter.php">
            <input type="hidden" name="submission_id" id="submissionId">
            <div class="modal-header">
              <h5 class="modal-title">Review Chapter Submission</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Chapter Document</label>
                <div id="chapterDocumentLink" class="mb-3"></div>
              </div>
              <div class="form-group">
                <label>Student Comments</label>
                <div id="studentComments" class="form-control bg-light p-3"></div>
              </div>
              <div class="form-group">
                <label>Supervisor Feedback</label>
                <textarea name="feedback" id="feedback" class="form-control" rows="4" required></textarea>
              </div>
              <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control" required>
                  <option value="Pending">Pending Review</option>
                  <option value="Approved">Approve</option>
                  <option value="Rejected">Request Changes</option>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary">Submit Review</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    
    <?php include '../components/footer.php'; ?>
    <?php include '../components/script.php'; ?>
    
    <script>
    $(document).ready(function() {
      $('#chaptersTable').DataTable({
        order: [[4, 'desc']],
        columnDefs: [{ orderable: false, targets: [5] }]
      });

      $('.review-chapter').click(function() {
        const id = $(this).data('id');
        Swal.fire({ title: 'Loading...', showConfirmButton: false, willOpen: () => Swal.showLoading() });
        $.getJSON('api/get_chapter_submission.php', { id })
          .done(response => {
            Swal.close();
            if (response.status === 'success') {
              const d = response.data;
              const filePath = d.file_path.replace('..', '/project-allocation-system');
              $('#submissionId').val(d.id);
              $('#chapterDocumentLink').html(`
  <a href="${filePath}" target="_blank" class="btn btn-outline-primary">
    <i class="fas fa-download"></i> Download Chapter
  </a>
  <small class="text-muted d-block mt-1">Submitted on: ${new Date(d.submitted_at).toLocaleString()}</small>
`);
              $('#studentComments').text(d.comments || '—');
              $('#feedback').val(d.feedback || '');
              $('select[name="status"]').val(d.status || 'Pending');
              $('#reviewModal').modal('show');
            } else {
              Swal.fire('Error!', response.message, 'error');
            }
          })
          .fail(() => Swal.fire('Error!', 'Could not load submission', 'error'));
      }); 

      $('#reviewForm').submit(function(e) {
        e.preventDefault();
        Swal.fire({ title: 'Submitting review...', showConfirmButton: false, willOpen: () => Swal.showLoading() });
        $.post('api/review_chapter.php', $(this).serialize(), 'json')
          .done(response => {
            Swal.close();
            if (response.status === 'success') {
              Swal.fire('Success!', response.message, 'success').then(() => location.reload());
            } else {
              Swal.fire('Error!', response.message, 'error');
            }
          })
          .fail((xhr, status, err) => Swal.fire('Error!', 'Failed: ' + err, 'error'));
      });
    });
    </script>

</div>