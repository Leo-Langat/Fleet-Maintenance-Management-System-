<?php
require_once 'db_connect.php';

// Check if user is logged in and is driver or mechanic
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['driver', 'mechanic'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$user_id = $_SESSION['user_id'];

// Get all admin user_ids and names
$admin_query = "SELECT user_id, name FROM Users WHERE role = 'admin'";
$admin_result = $conn->query($admin_query);
$admin_ids = [];
$admin_names = [];
while ($row = $admin_result->fetch_assoc()) {
    $admin_ids[] = $row['user_id'];
    $admin_names[$row['user_id']] = $row['name'];
}
$admin_ids_str = implode(',', array_map('intval', $admin_ids));

// Fetch replies from any admin to this user
$replies = [];
if (!empty($admin_ids)) {
    $query = "SELECT * FROM Messages WHERE sender_id IN ($admin_ids_str) AND receiver_id = ? ORDER BY sent_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $replies = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
$conn->close();
?>

<!-- Replies Modal -->
<div class="modal-overlay" id="repliesModal">
    <div class="modal-content">
        <button class="close-button" onclick="closeRepliesModal()" style="float:right; margin-bottom: 1rem;">&times;</button>
        <h2 class="modal-title" style="margin-bottom: 1rem;">Replies from Admin</h2>
        <div class="replies-list">
            <?php if (empty($replies)): ?>
                <div class="no-replies">
                    <i class="fas fa-inbox"></i>
                    <p>No replies from admin yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($replies as $reply): ?>
                    <?php if ($reply['is_read'] == 1) continue; ?>
                    <div class="reply-item <?php echo $reply['is_read'] ? '' : 'unread'; ?>" 
                         data-reply-id="<?php echo $reply['message_id']; ?>"
                         onclick="removeReply(this)">
                        <div class="reply-header">
                            <span class="admin-avatar">A</span>
                            <span class="admin-name"><?php echo htmlspecialchars($admin_names[$reply['sender_id']] ?? 'Admin'); ?> (Admin)</span>
                            <span class="reply-time"><?php echo date('M d, Y H:i', strtotime($reply['sent_at'])); ?></span>
                        </div>
                        <div class="reply-content">
                            <?php echo nl2br(htmlspecialchars($reply['message_body'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    justify-content: center;
    align-items: center;
}
.modal-content {
    background: #fff;
    border-radius: 10px;
    padding: 2rem;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    position: relative;
    animation: modalSlideIn 0.3s ease-out;
}
@keyframes modalSlideIn {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.modal-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #2C3E50;
    text-align: left;
    margin-bottom: 1rem;
}
.close-button {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #7f8c8d;
    float: right;
}
.replies-list {
    max-height: 350px;
    overflow-y: auto;
}
.reply-item {
    background: #f4f6f8;
    border-radius: 8px;
    margin-bottom: 1rem;
    padding: 1rem;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    transition: background 0.2s, box-shadow 0.2s;
    cursor: pointer;
}
.reply-item.unread {
    background: #eaf6ff;
}
.reply-item:hover {
    background: #e1e8ed;
    box-shadow: 0 2px 8px rgba(52,152,219,0.08);
}
.reply-header {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    margin-bottom: 0.5rem;
}
.admin-avatar {
    width: 32px;
    height: 32px;
    background: #3498db;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.1rem;
}
.admin-name {
    font-weight: 500;
    color: #2c3e50;
}
.reply-time {
    margin-left: auto;
    font-size: 0.9rem;
    color: #7f8c8d;
}
.reply-content {
    color: #34495e;
    line-height: 1.5;
    margin-top: 0.2rem;
}
.no-replies {
    text-align: center;
    color: #7f8c8d;
    padding: 2rem 0;
}
.no-replies i {
    font-size: 2.5rem;
    color: #bdc3c7;
    margin-bottom: 0.5rem;
}
.reply-badge {
    display: none;
    position: absolute;
    top: -8px;
    right: -8px;
    background: #e74c3c;
    color: #fff;
    border-radius: 50%;
    padding: 4px 8px;
    font-size: 12px;
}
@media (max-width: 600px) {
    .modal-content { padding: 1rem; }
    .replies-list { max-height: 200px; }
}
</style>
<script>
function openRepliesModal() {
    document.getElementById('repliesModal').style.display = 'flex';
}
function closeRepliesModal() {
    document.getElementById('repliesModal').style.display = 'none';
}
function removeReply(element) {
    var messageId = element.getAttribute('data-reply-id');
    // Send AJAX request to mark as read
    fetch('mark_message_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ messageId: messageId })
    })
    .then(response => response.json())
    .then(data => {
        // Refresh the reply badge count after marking as read
        refreshReplyBadge();
        // Optionally handle response
    })
    .catch(error => {
        console.error('Error marking message as read:', error);
    });
    // Fade out and remove from DOM
    element.style.transition = 'opacity 0.3s';
    element.style.opacity = 0;
    setTimeout(function() {
        element.remove();
        // Check if there are no more replies and show "no replies" message
        const repliesList = document.querySelector('.replies-list');
        const replyItems = repliesList.querySelectorAll('.reply-item');
        if (replyItems.length === 0) {
            repliesList.innerHTML = `
                <div class="no-replies">
                    <i class="fas fa-inbox"></i>
                    <p>No replies from admin yet.</p>
                </div>
            `;
        }
    }, 300);
}
function refreshReplyBadge() {
    fetch('get_unread_replies_count.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('replyBadge');
            if (data.status === 'success' && data.unread_count > 0) {
                badge.style.display = 'inline-block';
                badge.textContent = data.unread_count;
            } else {
                badge.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error refreshing reply badge:', error);
        });
}
function loadReplyBadge() {
    fetch('get_unread_replies_count.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('replyBadge');
            if (data.status === 'success' && data.unread_count > 0) {
                badge.style.display = 'inline-block';
                badge.textContent = data.unread_count;
            } else {
                badge.style.display = 'none';
            }
        });
}
document.addEventListener('DOMContentLoaded', function() {
    loadReplyBadge();
    // Refresh reply badge every 30 seconds
    setInterval(loadReplyBadge, 30000);
});
</script> 