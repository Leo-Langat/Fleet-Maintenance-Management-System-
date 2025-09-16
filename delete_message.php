<?php
session_start();
require_once "db_connect.php";

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);
$messageId = $data['messageId'] ?? null;

if (!$messageId) {
    echo json_encode(['success' => false, 'message' => 'Message ID is required']);
    exit();
}

// Delete the message
$query = "DELETE FROM Messages WHERE message_id = ? AND receiver_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $messageId, $_SESSION['user_id']);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting message']);
}

$stmt->close();
$conn->close();
?> 