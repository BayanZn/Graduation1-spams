<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand">
            <a href="index.php"><?php echo $projectShortName; ?></a>
        </div>
        <div class="sidebar-brand sidebar-brand-sm">
            <a href="index.php">PA</a>
        </div>
        <ul class="sidebar-menu">
            <li class="menu-header">Dashboard</li>
            <li>
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-fire"></i><span>Dashboard</span>
                </a>
            </li>
            <li>
                    <a href="messages.php" class="nav-link">
                        <i class="fab fa-rocketchat"></i><span>Message </span><?= $unread_count > 0 ? '<span class="badge badge-danger">' . $unread_count . '</span>' : '' ?>
                    </a>
                </li>
            
            <?php if($_SESSION['user_role'] === 'Admin'): ?>
                <li class="menu-header">Administration</li>
                
                <li> 
                    <a href="programs.php" class="nav-link">
                        <i class="fas fa-award"></i><span>Manage Departments</span>
                    </a>
                </li>
                <li>
                    <a href="students.php" class="nav-link">
                        <i class="fas fa-book-reader"></i><span>Manage Students</span>
                    </a>
                </li>
                <li>
                    <a href="supervisors.php" class="nav-link">
                        <i class="fas fa-chalkboard-teacher"></i><span>Manage Supervisors</span>
                    </a>
                </li>
                <li>
                    <a href="projects.php" class="nav-link">
                        <i class="fas fa-project-diagram"></i><span>Manage Projects</span>
                    </a>
                </li>
                <li>
                    <a href="assignments.php" class="nav-link">
                        <i class="fas fa-link"></i><span>Project Assignments</span>
                    </a>
                </li>
                <li>
                    <a href="defenses.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i><span>Defense Scheduling</span>
                    </a>
                </li>
                <li>
                    <a href="users.php" class="nav-link">
                        <i class="fas fa-users"></i><span>Manage Users</span>
                    </a>
                </li>
                
            <?php elseif($_SESSION['user_role'] === 'Coordinator'): ?>
                <li class="menu-header">Coordination</li>
                <li>
                    <a href="allocate.php" class="nav-link">
                        <i class="fas fa-tasks"></i><span>Project Allocation</span>
                    </a>
                </li>
                <li>
                    <a href="assignments.php" class="nav-link">
                        <i class="fas fa-link"></i><span>Manage Assignments</span>
                    </a>
                </li>
                <li>
                    <a href="defenses.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i><span>Schedule Defenses</span>
                    </a>
                </li>
                <li>
                    <a href="defense_panels.php" class="nav-link">
                        <i class="fas fa-users-cog"></i><span>Defense Panels</span>
                    </a>
                </li>
                <li>
                    <a href="defense_results.php" class="nav-link">
                        <i class="fas fa-clipboard-check"></i><span>Defense Results</span>
                    </a>
                </li>
                
            <?php elseif($_SESSION['user_role'] === 'Supervisor'): ?>
                <li class="menu-header">Supervision</li>
                <li>
                    <a href="messages.php" class="nav-link">
                        <i class="fab fa-rocketchat"></i><span>Message <?= $unread_count > 0 ? '<span class="badge badge-danger">' . $unread_count . '</span>' : '' ?></span>
                    </a>
                </li>
                <!-- <li>
                    <a href="my_projects.php" class="nav-link">
                        <i class="fas fa-project-diagram"></i><span>My Projects</span>
                    </a>
                </li> -->
                <li>
                    <a href="review_projects.php" class="nav-link">
                        <i class="fas fa-file-alt"></i><span>Review Projects</span>
                    </a>
                </li>
                <!-- <li>
                    <a href="defense_schedule.php" class="nav-link">
                        <i class="fas fa-calendar-day"></i><span>My Defense Schedule</span>
                    </a>
                </li>
                <li>
                    <a href="evaluate_defense.php" class="nav-link">
                        <i class="fas fa-clipboard-check"></i><span>Evaluate Defenses</span>
                    </a>
                </li> -->
                
            <?php elseif($_SESSION['user_role'] === 'Student'): ?>

                <li class="menu-header">Project</li>
                <li>
                    <!-- <a href="my_project.php" class="nav-link">
                        <i class="fas fa-project-diagram"></i><span>My Project</span>
                    </a> -->
                </li>
                <li>
                    <a href="submit_project.php" class="nav-link">
                        <i class="fas fa-file-upload"></i><span>Project Submission</span>
                    </a>
                </li>
                <!-- <li>
                    <a href="defense_schedule.php" class="nav-link">
                        <i class="fas fa-calendar-check"></i><span>My Defense Schedule</span>
                    </a>
                </li> -->
            <?php endif; ?>
            
            
            <!-- <li class="menu-header">Communication</li>
            <li>
                <a href="messages.php" class="nav-link">
                    <i class="fas fa-envelope"></i><span>Messages</span>
                    <span class="badge badge-primary badge-pill" id="unread-count">0</span>
                </a>
            </li>
            <li>
                <a href="notifications.php" class="nav-link">
                    <i class="fas fa-bell"></i><span>Notifications</span>
                    <span class="badge badge-danger badge-pill" id="notification-count">0</span>
                </a>
            </li> -->

            
            <li class="menu-header">Account</li>
            
            <li>
                <a href="profile.php" class="nav-link">
                    <i class="fas fa-user"></i><span>Profile</span>
                </a>
            </li>
            <!-- <li>
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i><span>Settings</span>
                </a>
            </li> -->
            <li>

                <a href="logout.php" onclick="return confirmLogout()" class="nav-link text-danger" >
                    <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                    
                </a>
            </li>
        </ul>
    </aside>
</div>

<!-- <script>
// Update unread message count (example)
    function updateUnreadCount() {
        $.get('api/get_unread_count.php', function(data) {
            if(data.unread_count > 0) {
                $('#unread-count').text(data.unread_count).show();
            } else {
                $('#unread-count').hide();
            }
        });
    }

// Update notification count (example)
    function updateNotificationCount() {
        $.get('api/get_notification_count.php', function(data) {
            if(data.notification_count > 0) {
                $('#notification-count').text(data.notification_count).show();
            } else {
                $('#notification-count').hide();
            }
        });
    }

// Update counts every 30 seconds
    setInterval(function() {
        updateUnreadCount();
        updateNotificationCount();
    }, 30000);

// Initial load
    $(document).ready(function() {
        updateUnreadCount();
        updateNotificationCount();
    });
</script> -->