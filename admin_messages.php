<?php
session_start();
require_once "db_connect.php";

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch messages for admin
$query = "SELECT m.*, u.name as sender_name, u.role as sender_role 
          FROM Messages m 
          JOIN Users u ON m.sender_id = u.user_id 
          WHERE m.receiver_id = ? 
          ORDER BY m.sent_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Messages</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f6f8;
            color: #2c3e50;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 1.8rem;
            color: #2c3e50;
        }

        .search-container {
            margin-bottom: 1.5rem;
        }

        .search-box {
            position: relative;
            max-width: 400px;
            margin: 0 auto;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 0.9rem;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.2);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .search-clear {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #7f8c8d;
            cursor: pointer;
            font-size: 0.9rem;
            display: none;
        }

        .search-clear:hover {
            color: #e74c3c;
        }

        .message-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            background-color: #fff;
            color: #2c3e50;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filter-btn.active {
            background-color: #3498db;
            color: white;
        }

        .messages-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .message-item {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s ease;
            cursor: pointer;
        }

        .message-item:hover {
            background-color: #f8f9fa;
        }

        .message-item.unread {
            background-color: #f0f7ff;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .sender-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sender-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .sender-details h3 {
            font-size: 1rem;
            color: #2c3e50;
            margin-bottom: 0.2rem;
        }

        .sender-role {
            font-size: 0.8rem;
            color: #7f8c8d;
            text-transform: capitalize;
        }

        .message-time {
            font-size: 0.8rem;
            color: #7f8c8d;
        }

        .message-content {
            color: #34495e;
            line-height: 1.5;
            margin-top: 0.5rem;
        }

        .message-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .reply-btn {
            background-color: #3498db;
            color: white;
        }

        .reply-btn:hover {
            background-color: #2980b9;
        }

        .no-messages {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }

        .no-messages i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #bdc3c7;
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .modal-title {
            font-size: 1.5rem;
            color: #2c3e50;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #7f8c8d;
        }

        .reply-form {
            margin-top: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            resize: vertical;
            min-height: 100px;
        }

        .submit-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            margin-top: 1rem;
        }

        .submit-btn:hover {
            background-color: #2980b9;
        }

        @media (max-width: 768px) {
            .container {
                margin: 1rem auto;
            }

            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .search-container {
                margin-bottom: 1rem;
            }

            .search-box {
                max-width: 100%;
            }

            .message-filters {
                flex-wrap: wrap;
                justify-content: center;
            }

            .message-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .message-time {
                align-self: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <a href="admin_dashboard.php" class="back-btn" style="text-decoration: none;">
                    <button style="background: #e1e8ed; color: #2c3e50; border: none; border-radius: 6px; padding: 0.5rem 1.2rem; font-size: 1rem; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: background 0.2s;">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </button>
                </a>
                <h1 style="margin: 0;">Messages</h1>
                <button onclick="openMessageModal()" style="background: #3498db; color: white; border: none; border-radius: 6px; padding: 0.5rem 1.2rem; font-size: 1rem; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: background 0.2s;">
                    <i class="fas fa-plus"></i> New Message
                </button>
            </div>
            <div class="search-container">
                <div class="search-box">
                    <input type="text" id="searchInput" class="search-input" placeholder="Search sender or message">
                    <span class="search-icon"><i class="fas fa-search"></i></span>
                    <button class="search-clear" onclick="clearSearch()">&times;</button>
                </div>
            </div>
            <div class="message-filters">
                <button class="filter-btn active" data-filter="all">All Messages</button>
                <button class="filter-btn" data-filter="unread">Unread</button>
                <button class="filter-btn" data-filter="read">Read</button>
            </div>
        </div>

        <div class="messages-container">
            <?php if (empty($messages)): ?>
                <div class="no-messages">
                    <i class="fas fa-inbox"></i>
                    <p>No messages yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $message): ?>
                    <div class="message-item <?php echo $message['is_read'] ? '' : 'unread'; ?>" 
                         data-message-id="<?php echo $message['message_id']; ?>"
                         onclick="markMessageRead(this, <?php echo $message['message_id']; ?>)">
                        <div class="message-header">
                            <div class="sender-info">
                                <div class="sender-avatar">
                                    <?php echo strtoupper(substr($message['sender_name'], 0, 1)); ?>
                                </div>
                                <div class="sender-details">
                                    <h3><?php echo htmlspecialchars($message['sender_name']); ?></h3>
                                    <span class="sender-role"><?php echo htmlspecialchars($message['sender_role']); ?></span>
                                </div>
                            </div>
                            <span class="message-time">
                                <?php echo date('M d, Y H:i', strtotime($message['sent_at'])); ?>
                            </span>
                        </div>
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($message['message_body'])); ?>
                        </div>
                        <div class="message-actions">
                            <button class="action-btn reply-btn" onclick="event.stopPropagation(); openReplyModal(<?php echo $message['message_id']; ?>, '<?php echo htmlspecialchars($message['sender_name']); ?>', <?php echo $message['sender_id']; ?>)">
                                <i class="fas fa-reply"></i> Reply
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reply Modal -->
    <div class="modal-overlay" id="replyModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Reply to Message</h2>
                <button class="close-btn" onclick="closeReplyModal()">&times;</button>
            </div>
            <form id="replyForm" onsubmit="sendReply(event)">
                <input type="hidden" id="messageId" name="messageId">
                <input type="hidden" id="receiverId" name="receiverId">
                <div class="form-group">
                    <label class="form-label">Your Reply</label>
                    <textarea class="form-textarea" id="replyMessage" name="replyMessage" required></textarea>
                </div>
                <button type="submit" class="submit-btn">Send Reply</button>
            </form>
        </div>
    </div>

    <!-- New Message Modal -->
    <?php include 'message_modal.php'; ?>

    <script>
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const searchClear = document.querySelector('.search-clear');
        let currentFilter = 'all';
        let currentSearch = '';

        searchInput.addEventListener('input', function() {
            currentSearch = this.value.toLowerCase();
            if (currentSearch.length > 0) {
                searchClear.style.display = 'block';
            } else {
                searchClear.style.display = 'none';
            }
            filterMessages(currentFilter, currentSearch);
        });

        function clearSearch() {
            searchInput.value = '';
            currentSearch = '';
            searchClear.style.display = 'none';
            filterMessages(currentFilter, currentSearch);
        }

        // Filter messages
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentFilter = btn.dataset.filter;
                filterMessages(currentFilter, currentSearch);
            });
        });

        function filterMessages(filter, search = '') {
            const messages = document.querySelectorAll('.message-item');
            messages.forEach(message => {
                let shouldShow = true;
                
                // Apply filter
                if (filter === 'unread') {
                    shouldShow = message.classList.contains('unread');
                } else if (filter === 'read') {
                    shouldShow = !message.classList.contains('unread');
                }
                
                // Apply search
                if (shouldShow && search) {
                    const senderName = message.querySelector('.sender-details h3').textContent.toLowerCase();
                    const messageContent = message.querySelector('.message-content').textContent.toLowerCase();
                    shouldShow = senderName.includes(search) || messageContent.includes(search);
                }
                
                message.style.display = shouldShow ? 'block' : 'none';
            });
        }

        // Reply Modal Functions
        function openReplyModal(messageId, senderName, senderId) {
            document.getElementById('messageId').value = messageId;
            document.getElementById('receiverId').value = senderId;
            document.getElementById('replyModal').style.display = 'flex';
            document.querySelector('.modal-title').textContent = `Reply to ${senderName}`;
        }

        function closeReplyModal() {
            document.getElementById('replyModal').style.display = 'none';
            document.getElementById('replyForm').reset();
        }

        // Send Reply
        function sendReply(event) {
            event.preventDefault();
            const receiverId = document.getElementById('receiverId').value;
            const replyMessage = document.getElementById('replyMessage').value;
            const messageId = document.getElementById('messageId').value;
            if (!receiverId || !replyMessage) {
                alert('Missing recipient or message.');
                return;
            }
            fetch('send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `receiver_id=${encodeURIComponent(receiverId)}&message=${encodeURIComponent(replyMessage)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Mark the original message as read after reply
                    fetch('mark_message_read.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ messageId: messageId })
                    })
                    .then(() => {
                        // Optionally update UI
                        const msgElem = document.querySelector(`.message-item[data-message-id='${messageId}']`);
                        if (msgElem) msgElem.classList.remove('unread');
                    });
                    alert('Reply sent successfully!');
                    closeReplyModal();
                    location.reload();
                } else {
                    alert('Error sending reply: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error sending reply. Please try again.');
            });
        }

        // Mark message as read when clicked
        function markMessageRead(element, messageId) {
            if (element.classList.contains('unread')) {
                fetch('mark_message_read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ messageId: messageId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        element.classList.remove('unread');
                    }
                })
                .catch(error => {
                    console.error('Error marking message as read:', error);
                });
            }
        }

        // Function to open new message modal
        function openMessageModal() {
            document.getElementById('messageModal').style.display = 'flex';
            loadUsers();
        }
    </script>
</body>
</html> 