<?php
require_once '../includes/auth_check.php';
require_once '../components/head.php';

if (!isset($_GET['id'])) {
    header("Location: projects.php");
    exit();
}

$project_id = (int)$_GET['id'];

// Get project data
$stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) {
    header("Location: projects.php");
    exit();
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
                <h1>Edit Project</h1>
                <div class="section-header-breadcrumb">
                    <a href="projects.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Projects
                    </a>
                </div>
            </div>
            
            <div class="section-body">
                <div class="card">
                    <div class="card-body">
                        <form id="editProjectForm" method="POST" action="api/update_project.php">
                            <input type="hidden" name="id" value="<?= $project->id ?>">
                            
                            <div class="form-group">
                                <label>Project Name</label>
                                <input type="text" name="project_name" class="form-control" 
                                       value="<?= htmlspecialchars($project->project_name) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Case Study</label>
                                <textarea name="project_case" class="form-control" rows="5" required><?= 
                                    htmlspecialchars($project->project_case) ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Project Level</label>
                                <select name="project_level" class="form-control" required>
                                    <option value="Beginner" <?= $project->project_level == 'Beginner' ? 'selected' : '' ?>>Beginner</option>
                                    <option value="Intermediate" <?= $project->project_level == 'Intermediate' ? 'selected' : '' ?>>Intermediate</option>
                                    <option value="Advanced" <?= $project->project_level == 'Advanced' ? 'selected' : '' ?>>Advanced</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Update Project</button>
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
        $('#editProjectForm').submit(function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Updating Project',
                text: 'Please wait...',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => Swal.showLoading()
            });
            
            $.ajax({
                url: 'api/update_project.php',
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
                            window.location.href = 'projects.php';
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to update project', 'error');
                }
            });
        });
    });
    </script>
</div>