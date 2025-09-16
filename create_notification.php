<?php
require_once 'db_connect.php';

function createNotification($user_id, $title, $message, $type = 'maintenance', $related_id = null) {
    global $conn;
    
    $query = "INSERT INTO Notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssi", $user_id, $title, $message, $type, $related_id);
    
    return $stmt->execute();
}

function markNotificationAsRead($notification_id) {
    global $conn;
    
    $query = "UPDATE Notifications SET is_read = TRUE, read_at = CURRENT_TIMESTAMP WHERE notification_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $notification_id);
    
    return $stmt->execute();
}

function getUnreadNotifications($user_id) {
    global $conn;
    
    $query = "SELECT * FROM Notifications WHERE user_id = ? AND is_read = FALSE ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getAllNotifications($user_id, $limit = 50) {
    global $conn;
    
    $query = "SELECT * FROM Notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}
?> 