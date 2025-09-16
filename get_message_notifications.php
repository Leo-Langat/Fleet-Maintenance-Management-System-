<?php
session_start();
require_once "db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['error' => 'Unauthorized']));
}

$admin_id = $_SESSION['user_id'];

// Get unread messages count and details
$query = "SELECT m.*, u.name as sender_name, u.role as sender_role 
          FROM Messages m 
          JOIN Users u ON m.sender_id = u.user_id 
          WHERE m.receiver_id = ? 
          AND m.is_read = 0 
          ORDER BY m.sent_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id' => $row['message_id'],
        'message' => "New message from " . $row['sender_name'] . " (" . ucfirst($row['sender_role']) . ")",
        'sender_name' => $row['sender_name'],
        'sender_role' => $row['sender_role'],
        'sent_at' => $row['sent_at'],
        'preview' => substr($row['message_body'], 0, 50) . (strlen($row['message_body']) > 50 ? '...' : '')
    ];
}

$unread_count = count($notifications);

echo json_encode([
    'notifications' => $notifications,
    'unread_count' => $unread_count
]); 