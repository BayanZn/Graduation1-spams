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
        if ($messages && count($messages) > 0):
            foreach ($messages as $message):
                $is_unread = !$message->is_read;
        ?>
        <div class="message-item <?= $is_unread ? 'unread' : '' ?>" 
             data-id="<?= $message->id ?>">
            <div class="message-sender">
                <?= htmlspecialchars($message->sender_username) ?>
                <small class="text-muted float-right">
                    <?= date('M j', strtotime($message->created_at)) ?>
                </small>
            </div>
            <div class="message-subject">
                <?= htmlspecialchars($message->subject) ?>
            </div>
            <div class="message-preview">
                <?= substr(htmlspecialchars($message->message), 0, 50) ?>...
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



<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        width: '100%',
        dropdownParent: $('#newMessageModal')
    });
    
    // New message button
    $('#newMessageBtn').click(function() {
        $('#newMessageModal').modal('show');
    });
    
    // Handle message form submission
    $('#newMessageForm').submit(function(e) {
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
            success: function(response) {
                if(response.status === 'success') {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,
                        icon: 'success'
                    }).then(() => {
                        $('#newMessageModal').modal('hide');
                        location.reload();
                    });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error!', 'Failed to send message', 'error');
            }
        });
    });
    
    // Open message when clicked
    $('.message-item').click(function() {
        const messageId = $(this).data('id');
        window.location.href = 'messages.php?view=' + messageId;
    });
});
</script>