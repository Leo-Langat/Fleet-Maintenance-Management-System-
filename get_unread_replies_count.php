<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['driver', 'mechanic'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all admin user_ids
$admin_query = "SELECT user_id FROM Users WHERE role = 'admin' AND status = 'active'";
$admin_result = $conn->query($admin_query);
$admin_ids = [];
while ($row = $admin_result->fetch_assoc()) {
    $admin_ids[] = $row['user_id'];
}

if (empty($admin_ids)) {
    echo json_encode(['status' => 'success', 'unread_count' => 0]);
    exit();
}

$admin_ids_str = implode(',', array_map('intval', $admin_ids));

// Count unread replies from any admin to this user
$query = "SELECT COUNT(*) as unread_count FROM Messages WHERE sender_id IN ($admin_ids_str) AND receiver_id = ? AND is_read = 0";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$unread_count = $row ? (int)$row['unread_count'] : 0;
$stmt->close();
$conn->close();

echo json_encode(['status' => 'success', 'unread_count' => $unread_count]); 