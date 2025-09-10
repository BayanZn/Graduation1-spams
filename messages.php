<?php
require_once '../includes/auth_check.php';
require_once '../includes/messaging_functions.php';
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
                    <button class="btn btn-primary" data-toggle="modal" data-target="#newMessageModal">
                        <i class="fas fa-plus"></i> New Message
                    </button>
                </div>
            </div>

            <div class="section-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="messages-sidebar">
                                    <div class="messages-header">
                                        <h5>Messages</h5>
                                        <button class="btn btn-sm btn-primary" id="newMessageBtn">
                                            <i class="fas fa-plus"></i> New
                                        </button>
                                    </div>

                                    <div class="messages-search">
                                        <input type="text" class="form-control" placeholder="Search messages...">
                                    </div>

                                    <div class="messages-list">
                                        <?php
                                        $messages = getUserMessages($_SESSION['user_id'], 10);
                                        if (is_array($messages) && count($messages) > 0):
                                            foreach ($messages as $message):
                                                $is_unread = !$message->is_read;
                                                ?>
                                                <div class="message-item <?= $is_unread ? 'unread' : '' ?>" data-id="<?= $message->id ?>">
                                                    <div class="message-sender">
                                                        <?= htmlspecialchars($message->sender_username, ENT_QUOTES, 'UTF-8') ?>
                                                        <small class="text-muted float-right"><?= date('M j', strtotime($message->created_at)) ?></small>
                                                    </div>
                                                    <div class="message-subject">
                                                        <?= htmlspecialchars($message->subject, ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                    <div class="message-preview">
                                                        <?= substr(htmlspecialchars($message->message, ENT_QUOTES, 'UTF-8'), 0, 50) ?>...
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="no-messages">
                                                <i class="fas fa-inbox"></i>
                                                <p>No messages</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                                <div class="message <?= $message->sender_id == $_SESSION['user_id'] ? 'sent' : 'received' ?>">
                                                    <div class="message-header">
                                                        <strong><?= htmlspecialchars($message->sender_username, ENT_QUOTES, 'UTF-8') ?></strong>
                                                        <small class="text-muted float-right">
                                                            <?= date('M j, Y H:i', strtotime($message->created_at)) ?>
                                                        </small>
                                                    </div>
                                                    <div class="message-subject">
                                                        <?= htmlspecialchars($message->subject, ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                    <div class="message-body">
                                                        <?= nl2br(htmlspecialchars($message->message, ENT_QUOTES, 'UTF-8')) ?>
                                                    </div>
                                                    <?php if ($index === 0): ?>
                                                        <div class="message-actions">
                                                            <button class="btn btn-sm btn-primary reply-btn"
                                                                data-receiver="<?= $message->sender_id ?>"
                                                                data-subject="Re: <?= htmlspecialchars($message->subject, ENT_QUOTES, 'UTF-8') ?>">
                                                                <i class="fas fa-reply"></i> Reply
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>

                                            <div class="reply-form mt-4" style="display: none;">
                                                <form id="replyForm">
                                                    <input type="hidden" name="reply_receiver_id">
                                                    <input type="hidden" name="parent_id" value="<?= $view_message_id ?>">
                                                    <div class="form-group">
                                                        <input type="text" name="subject" class="form-control" readonly>
                                                    </div>
                                                    <div class="form-group">
                                                        <textarea name="message" class="form-control" rows="5" required></textarea>
                                                    </div>
                                                    <div class="form-group">
                                                        <button type="submit" class="btn btn-primary">Send Reply</button>
                                                        <button type="button" class="btn btn-secondary cancel-reply">Cancel</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-danger">
                                            Message not found or you don't have permission to view it.
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="empty-messages">
                                        <i class="fas fa-comments"></i>
                                        <h5>Select a message to view</h5>
                                        <p>Choose a message from the sidebar to read or reply to it</p>
                                    </div>
                                <?php endif; ?>
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
                <div class="modal-header">
                    <h5 class="modal-title">New Message</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="newMessageForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>To</label>
                            <select class="form-control select2" name="receiver_id" required>
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

                                while ($user = $stmt->fetch()):
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
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php include '../components/footer.php'; ?>
<?php include '../components/script.php'; ?>

<script>
$(document).ready(function () {
    $('.reply-btn').click(function () {
        const receiverId = $(this).data('receiver');
        const subject = $(this).data('subject');
        $('input[name="reply_receiver_id"]').val(receiverId);
        $('input[name="subject"]').val(subject);
        $('.reply-form').show();
        $('textarea[name="message"]').focus();
    });

    $('.cancel-reply').click(function () {
        $('.reply-form').hide();
    });

    $('#replyForm').submit(function (e) {
        e.preventDefault();

        Swal.fire({
            title: 'Sending Reply',
            text: 'Please wait...',
            icon: 'info',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => Swal.showLoading()
        });

        const data = $(this).serializeArray();
        data.push({name: 'receiver_id', value: $('input[name="reply_receiver_id"]').val()});

        $.ajax({
            url: 'api/send_message.php',
            type: 'POST',
            data: $.param(data),
            dataType: 'json',
            success: function (response) {
                Swal.fire({
                    title: response.status === 'success' ? 'Success!' : 'Error!',
                    text: response.message,
                    icon: response.status
                }).then(() => {
                    if (response.status === 'success') location.reload();
                });
            },
            error: function () {
                Swal.fire('Error!', 'Failed to send reply', 'error');
            }
        });
    });

    $('#newMessageForm').submit(function (e) {
        e.preventDefault();
        Swal.fire({
            title: 'Sending Message',
            text: 'Please wait...',
            icon: 'info',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: 'api/send_message.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                Swal.fire({
                    title: response.status === 'success' ? 'Success!' : 'Error!',
                    text: response.message,
                    icon: response.status
                }).then(() => {
                    if (response.status === 'success') {
                        $('#newMessageModal').modal('hide');
                        location.reload();
                    }
                });
            },
            error: function () {
                Swal.fire('Error!', 'Failed to send message', 'error');
            }
        });
    });

    $('.select2').select2({
        width: '100%',
        dropdownParent: $('#newMessageModal')
    });

    $('#newMessageBtn').click(function () {
        $('#newMessageModal').modal('show');
    });

    $('.message-item').click(function () {
        const messageId = $(this).data('id');
        window.location.href = 'message.php?view=' + messageId;
    });
});
</script>
</div>
