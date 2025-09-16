<?php
session_start();
require_once "db_connect.php"; 
require_once 'utils/auto_logger.php';

$autoLogger = new AutoLogger();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Validate form data
$current_password = trim($_POST['current_password'] ?? '');
$new_password = trim($_POST['new_password'] ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');

if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    echo json_encode(["status" => "error", "message" => "All fields are required."]);
    exit();
}

// Fetch current password securely
$stmt = $conn->prepare("SELECT password FROM Users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Check if user exists
if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    exit();
}

// Verify current password
if (!password_verify($current_password, $user['password'])) {
    $autoLogger->logPasswordChange($user_id, false);
    echo json_encode(['status' => 'error', 'message' => 'Current password is incorrect.']);
    exit();
}

// Check if new password is the same as current password
if ($current_password === $new_password) {
    $autoLogger->logPasswordChange($user_id, false);
    echo json_encode(['status' => 'error', 'message' => 'New password cannot be the same as the current password.']);
    exit();
}

// Validate new password match
if ($new_password !== $confirm_password) {
    $autoLogger->logPasswordChange($user_id, false);
    echo json_encode(['status' => 'error', 'message' => 'New passwords do not match.']);
    exit();
}

// Validate new password strength
if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/', $new_password)) {
    echo json_encode([
        "status" => "error",
        "message" => "Password must be at least 8 characters, include upper and lower case letters, a digit, and a special character."
    ]);
    exit();
}

// Hash new password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update password in the database
$update_stmt = $conn->prepare("UPDATE Users SET password = ? WHERE user_id = ?");
$update_stmt->bind_param("si", $hashed_password, $user_id);

if ($update_stmt->execute()) {
    $autoLogger->logPasswordChange($user_id, true);
    echo json_encode(['status' => 'success', 'message' => 'Password updated successfully.']);
} else {
    $autoLogger->logPasswordChange($user_id, false);
    echo json_encode(['status' => 'error', 'message' => 'Failed to update password.']);
}

$update_stmt->close();
$conn->close();
