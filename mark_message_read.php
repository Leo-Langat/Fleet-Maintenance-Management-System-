<?php
session_start();
require_once "db_connect.php";

// Check if user is logged in and is driver or mechanic
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['driver', 'mechanic','admin'])) {
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

// Update the message status
$query = "UPDATE Messages SET is_read = 1 WHERE message_id = ? AND receiver_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $messageId, $_SESSION['user_id']);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating message status']);
}

$stmt->close();
$conn->close();
?> 