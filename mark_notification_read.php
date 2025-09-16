<?php
session_start();
require_once 'db_connect.php';
require_once 'create_notification.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized access']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $notification_id = $data['notification_id'] ?? null;

    if (!$notification_id) {
        die(json_encode(['error' => 'Notification ID is required']));
    }

    // Mark notification as read
    if (markNotificationAsRead($notification_id)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to mark notification as read']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?> 