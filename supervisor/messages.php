<?php
require_once '../includes/auth_check.php';
require_once '../includes/messaging_functions.php';

$sender_id = $_SESSION['user_id'];

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $receiver_id = (int)$_POST['receiver_id'];
    $parent_id = (int)$_POST['parent_id'];
    $subject = trim($_POST['subject']);
    $message_text = trim($_POST['message']);

    if ($receiver_id && $subject !== '' && $message_text !== '') {
        if (sendMessage($sender_id, $receiver_id, $subject, $message_text, $parent_id)) {
            $_SESSION['message_status'] = ['type' => 'success', 'text' => 'Reply sent successfully.'];
            header('Location: messages.php?view=' . $parent_id);
            exit;
        } else {
            $_SESSION['message_status'] = ['type' => 'error', 'text' => 'Failed to send reply.'];
            header('Location: messages.php?view=' . $parent_id);
            exit;
        }
    } else {
        $_SESSION['message_status'] = ['type' => 'error', 'text' => 'All fields are required.'];
        header('Location: messages.php?view=' . $parent_id);
        exit;
    }
}

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = (int)$_POST['receiver_id'];
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (!$receiver_id || $subject === '' || $message === '') {
        $_SESSION['message_status'] = ['type' => 'error', 'text' => 'All fields are required.'];
        header('Location: messages.php');
        exit;
    }

    if (sendMessage($sender_id, $receiver_id, $subject, $message)) {
        $_SESSION['message_status'] = ['type' => 'success', 'text' => 'Message sent successfully.'];
    } else {
        $_SESSION['message_status'] = ['type' => 'error', 'text' => 'Failed to send message.'];
    }

    header('Location: messages.php');
    exit;
}

require_once '../components/head.php';


$view_message_id = isset($_GET['view']) ? (int)$_GET['view'] : null;
$unread_count = getUnreadCount($_SESSION['user_id']);

?>

<div class="main-wrapper main-wrapper-1">
    <div class="navbar-bg"></div>
    <?php include '../components/navbar.php'; ?>
    <?php include '../components/sidebar.php'; ?>

    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>
                    Messages <?= $unread_count > 0 ? '<span class="badge badge-danger">' . $unread_count . '</span>' : '' ?>
                </h1>
                <div class="section-header-breadcrumb">
                    <!-- Button triggers new message modal -->
                    <button class="btn btn-primary" data-toggle="modal" data-target="#newMessageModal">
                        <i class="fas fa-plus"></i> New Message
                    </button>
                </div>
            </div>

            <div class="section-body">
                <?php if (isset($_SESSION['message_status'])): ?>
                    <div class="alert alert-<?= $_SESSION['message_status']['type'] === 'success' ? 'success' : 'danger' ?>">
                        <?= htmlspecialchars($_SESSION['message_status']['text'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php unset($_SESSION['message_status']); ?>
                <?php endif; ?>

                <div class="row">
                    <!-- Message list -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="messages-sidebar">
                                    <div class="messages-header">
                                        <h5>Messages</h5>
                                        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#newMessageModal">
                                            <i class="fas fa-plus"></i> New
                                        </button>
                                    </div>

                                    <div class="messages-search">
                                        <form method="GET" action="messages.php">
                                            <input type="text" name="search" class="form-control" placeholder="Search messages..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                                        </form>
                                    </div>

                                    <div class="messages-list mt-2">
                                        <?php
                                        $messages = getUserMessages($_SESSION['user_id'], 10);
                                        if (is_array($messages) && count($messages) > 0):
                                            foreach ($messages as $message):
                                                $is_unread = !$message->is_read;
                                                ?>
                                                <a href="messages.php?view=<?= $message->id ?>" class="message-item d-block p-2 mb-1 <?= $is_unread ? 'bg-light font-weight-bold' : '' ?>" style="border: 1px solid #ddd; border-radius: 4px; text-decoration:none; color:inherit;">
                                                    <div class="message-sender">
                                                        <?= htmlspecialchars($message->sender_username, ENT_QUOTES, 'UTF-8') ?>
                                                        <small class="text-muted float-right"><?= date('M j', strtotime($message->created_at)) ?></small>
                                                    </div>
                                                    <div class="message-subject">
                                                        <?= htmlspecialchars($message->subject, ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                    <div class="message-preview text-truncate">
                                                        <?= substr(htmlspecialchars($message->message, ENT_QUOTES, 'UTF-8'), 0, 50) ?>...
                                                    </div>
                                                </a>

                                            <?php endforeach; ?>
                                            
                                      <?php else: ?>
                                        <div class="no-messages text-center py-4">
                                            <i class="fas fa-inbox fa-2x text-muted"></i>
                                            <p class="mt-2 text-muted">No messages</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message thread / details -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <?php if ($view_message_id):
                                $thread = getMessageThread($view_message_id);
                                if ($thread && count($thread) > 0):
                                    markAsRead($view_message_id);
                                    ?>
                                    <div class="message-thread">
                                        <?php foreach ($thread as $index => $message): ?>
                                            <div class="message mb-3 p-3 border rounded <?= $message->sender_id == $_SESSION['user_id'] ? 'bg-primary text-white' : 'bg-light' ?>">
                                                <div class="message-header mb-1">
                                                    <strong><?= htmlspecialchars($message->sender_username, ENT_QUOTES, 'UTF-8') ?></strong>
                                                    <small class="float-right"><?= date('M j, Y H:i', strtotime($message->created_at)) ?></small>
                                                </div>
                                                <div class="message-subject font-weight-bold mb-2">
                                                    <?= htmlspecialchars($message->subject, ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                                <div class="message-body mb-2">
                                                    <?= nl2br(htmlspecialchars($message->message, ENT_QUOTES, 'UTF-8')) ?>
                                                </div>
                                                <?php if ($index === 0): ?>
                                                    <!-- Reply form trigger -->
                                                    <form action="messages.php?view=<?= $view_message_id ?>" method="POST" class="reply-form">
                                                        <input type="hidden" name="receiver_id" value="<?= $message->sender_id ?>">
                                                        <input type="hidden" name="parent_id" value="<?= $view_message_id ?>">
                                                        <input type="hidden" name="subject" value="Re: <?= htmlspecialchars($message->subject, ENT_QUOTES, 'UTF-8') ?>">

                                                        <div class="form-group">
                                                            <label for="reply-message">Reply:</label>
                                                            <textarea id="reply-message" name="message" class="form-control" rows="4" required></textarea>
                                                        </div>
                                                        <button type="submit" name="send_reply" class="btn btn-primary">Send Reply</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                <?php else: ?>
                                    <div class="alert alert-danger">
                                        Message not found or you don't have permission to view it.
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="empty-messages text-center py-5">
                                    <i class="fas fa-comments fa-3x text-muted"></i>
                                    <h5 class="mt-3">Select a message to view</h5>
                                    <p>Choose a message from the sidebar to read or reply to it</p>
                                </div>
                            <?php endif; ?>

                            <?php
                                // Handle reply submission
                            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
                                $receiver_id = (int)$_POST['receiver_id'];
                                $parent_id = (int)$_POST['parent_id'];
                                $subject = trim($_POST['subject']);
                                $message_text = trim($_POST['message']);
                                $sender_id = $_SESSION['user_id'];

                                if ($receiver_id && $subject !== '' && $message_text !== '') {
                                    if (sendMessage($sender_id, $receiver_id, $subject, $message_text, $parent_id)) {
                                        $_SESSION['message_status'] = ['type' => 'success', 'text' => 'Reply sent successfully.'];
                                            // Redirect to avoid form resubmission
                                        header('Location: messages.php?view=' . $parent_id);
                                        exit;
                                    } else {
                                        echo '<div class="alert alert-danger mt-3">Failed to send reply.</div>';
                                    }
                                } else {
                                    echo '<div class="alert alert-danger mt-3">All fields are required.</div>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- New Message Modal -->
<div class="modal fade" id="newMessageModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="messages.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">New Message</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>To</label>
                        <select class="form-control" name="receiver_id" required>
                            <option value="">Select Recipient</option>
                            <?php
                            $query = "SELECT id, username, role FROM users WHERE id != ?";
                            $params = [$_SESSION['user_id']];

                            if ($_SESSION['user_role'] == 'Student') {
                                $query .= " AND role IN ('Supervisor', 'Coordinator')";
                            } elseif ($_SESSION['user_role'] == 'Supervisor') {
                                $query .= " AND role IN ('Student', 'Supervisor', 'Coordinator')";
                            }

                            $stmt = $db->prepare($query);
                            $stmt->execute($params);

                            while ($user = $stmt->fetch(PDO::FETCH_OBJ)):
                                ?>
                                <option value="<?= $user->id ?>">
                                    <?= htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8') ?> (<?= $user->role ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" class="form-control" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="send_message" class="btn btn-primary">Send Message</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../components/footer.php'; ?>
<?php include '../components/script.php'; ?>

<script>
    // Initialize Select2 only for modal
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%',
            dropdownParent: $('#newMessageModal')
        });
    });
</script>

</div>

<?php
// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (!$receiver_id || $subject === '' || $message === '') {
        $_SESSION['message_status'] = ['type' => 'error', 'text' => 'All fields are required.'];
        header('Location: messages.php');
        exit;
    }

    if (sendMessage($sender_id, $receiver_id, $subject, $message)) {
        $_SESSION['message_status'] = ['type' => 'success', 'text' => 'Message sent successfully.'];
    } else {
        $_SESSION['message_status'] = ['type' => 'error', 'text' => 'Failed to send message.'];
    }
    header('Location: messages.php');
    exit;
}
?>
