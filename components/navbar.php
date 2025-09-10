<?php

require_once __DIR__ . '/../includes/messaging_functions.php';
$unread_count = isset($_SESSION['user_id']) ? getUnreadCount($_SESSION['user_id']) : 0;
?>
<nav class="navbar navbar-expand-lg main-navbar sticky">
  <div class="form-inline mr-auto">
    <ul class="navbar-nav mr-3">
      <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg
        collapse-btn"> <i data-feather="align-justify"></i></a></li>
        <li><a href="#" class="nav-link nav-link-lg fullscreen-btn">
          <i data-feather="maximize"></i>
        </a></li>
        <li>
          <form class="form-inline mr-auto">
            <div class="search-element">
              <input class="form-control" type="search" placeholder="Search" aria-label="Search" data-width="200">
              <button class="btn" type="submit">
                <i class="fas fa-search"></i>
              </button>
            </div>
          </form>
        </li>
      </ul>
    </div>
    <ul class="navbar-nav navbar-right">
      <li class="dropdown dropdown-list-toggle">
        <a href="#" data-toggle="dropdown" class="nav-link nav-link-lg message-toggle">
          <i class="far fa-envelope"></i>
          <?php if ($unread_count > 0): ?>
            <span class="badge badge-primary navbar-badge"><?= $unread_count ?></span>
          <?php endif; ?>
        </a>
        <div class="dropdown-menu dropdown-list dropdown-menu-right">
          <div class="dropdown-header">Messages
            <div class="float-right">
              <a href="messages.php">View All</a>
            </div>
          </div>
          <div class="dropdown-list-content dropdown-list-message">
            <?php
            $recent_messages = getUserMessages($_SESSION['user_id'], 3, true);
            if ($recent_messages && count($recent_messages) > 0):
              foreach ($recent_messages as $message):
                ?>
                <a href="messages.php?view=<?= $message->id ?>" class="dropdown-item dropdown-item-unread">
                  <div class="dropdown-item-avatar">
                    <div class="avatar-icon bg-primary text-white">
                      <?= strtoupper(substr($message->sender_username, 0, 1)) ?>
                    </div>
                  </div>
                  <div class="dropdown-item-desc">
                    <b><?= htmlspecialchars($message->sender_username) ?></b>
                    <p><?= substr(htmlspecialchars($message->subject), 0, 30) ?>...</p>
                    <small><?= date('M j, H:i', strtotime($message->created_at)) ?></small>
                  </div>
                </a>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="dropdown-item text-center py-3">
                <p>No new messages</p>
              </div>
            <?php endif; ?>
          </div>
          <div class="dropdown-footer text-center">
            <a href="messages.php">View All <i class="fas fa-chevron-right"></i></a>
          </div>
        </div>
      </li>
      <!-- <li class="dropdown dropdown-list-toggle"><a href="#" data-toggle="dropdown"
        class="nav-link notification-toggle nav-link-lg"><i data-feather="bell" class="bell"></i>
      </a>
      <div class="dropdown-menu dropdown-list dropdown-menu-right pullDown">
        <div class="dropdown-header">
          Notifications
          <div class="float-right">
            <a href="#">Mark All As Read</a>
          </div>
        </div>
        <?php
        $userId = $_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];

        $notifications = $db->prepare("
          SELECT * FROM notifications
          WHERE user_id = ? AND user_role = ?
          ORDER BY created_at DESC
          ");
        $notifications->execute([$userId, $userRole]);
        $notifications = $notifications->fetchAll(PDO::FETCH_ASSOC);
        $unreadNotifications = array_filter($notifications, function ($n) {
          return !$n['is_read'];
        });
        $unreadNotifCount = count($unreadNotifications);
        ?>

        <?php foreach ($notifications as $note): ?>
          <div class="dropdown-list-content dropdown-list-icons">

            <a href="#" data-toggle="dropdown" class="nav-link notification-toggle nav-link-lg">
              <i data-feather="bell" class="bell"></i>
              <?php if ($unreadNotifCount > 0): ?>
                <span class="badge badge-warning navbar-badge"><?= $unreadNotifCount ?></span>
              <?php endif; ?>
            </a>



          </div>
        <?php endforeach; ?>
        <div class="dropdown-footer text-center">
          <a href="#">View All <i class="fas fa-chevron-right"></i></a>
        </div>
      </div>
    </li> -->

    <li class="dropdown">
      <a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
        <?php
    $profilePicture = isset($_SESSION['profile_picture']) && !empty($_SESSION['profile_picture'])
        ? $_SESSION['profile_picture']
        : '../assets/img/user.png';
?>
<img alt="image" src="<?= htmlspecialchars($profilePicture) ?>" class="user-img-radious-style">
<span class="d-sm-none d-lg-inline-block"></span>

      </a>
      <div class="dropdown-menu dropdown-menu-right pullDown">
        <div class="dropdown-title">Hello <?= $_SESSION['user_name'] ?></div>
        <a href="profile.php" class="dropdown-item has-icon"> 
          <i class="far fa-user"></i> 
          Profile
        </a> 
        
        <div class="dropdown-divider"></div>
        <a href="logout.php" class="dropdown-item has-icon text-danger" onclick="return confirmLogout()">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>

      </div>
    </li>
  </ul>
</nav>


<script>
  $(document).on('click', '.mark-all-read', function(e) {
    e.preventDefault();
    $.ajax({
      url: 'notifications/mark_all_read.php',
      method: 'POST',
      success: function(response) {
        if (response.status === 'success') {
          location.reload();
        } else {
          alert('Failed to mark as read.');
        }
      }
    });
  });
</script>
