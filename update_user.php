<?php
include 'db_connect.php';
session_start();
require_once 'utils/auto_logger.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Set an error message if needed, although redirecting is usually enough
    // $_SESSION['error'] = "Unauthorized access.";
    header("Location: login.php");
    exit();
}

$autoLogger = new AutoLogger();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate inputs
    $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $dob = isset($_POST['dob']) ? trim($_POST['dob']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'active'; // Default or handle as needed

    // Basic validation
    if ($userId <= 0 || empty($name) || empty($username) || empty($email) || empty($role) || empty($status)) {
        $_SESSION['error'] = "Invalid input data provided.";
        header("Location: manage_users.php");
        exit();
    }

    // Enforce unique username (except for this user)
    $checkUsernameQuery = "SELECT user_id FROM users WHERE username = ? AND user_id != ?";
    $stmt = $conn->prepare($checkUsernameQuery);
    $stmt->bind_param("si", $username, $userId);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $_SESSION['error'] = "The username is already taken by another user.";
        header("Location: manage_users.php");
        exit();
    }
    $stmt->close();

    // Enforce unique phone number (except for this user)
    $checkPhoneQuery = "SELECT user_id FROM users WHERE phone = ? AND user_id != ?";
    $stmt = $conn->prepare($checkPhoneQuery);
    $stmt->bind_param("si", $phone, $userId);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $_SESSION['error'] = "The phone number is already registered to another user.";
        header("Location: manage_users.php");
        exit();
    }
    $stmt->close();

    try {
        // Get the current user data before update
        $stmt = $conn->prepare("SELECT name, username, email, role, phone, dob, status FROM users WHERE user_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed (SELECT): " . $conn->error);
        }
        
        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed (SELECT): " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $old_data = $result->fetch_assoc();
        $stmt->close();

        if (!$old_data) {
            $_SESSION['error'] = "User not found.";
            header("Location: manage_users.php");
            exit();
        }

        // Prepare update statement
        $sql = "UPDATE users SET name = ?, username = ?, email = ?, role = ?, phone = ?, dob = ?, status = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed (UPDATE): " . $conn->error);
        }

        $stmt->bind_param("sssssssi", $name, $username, $email, $role, $phone, $dob, $status, $userId);

    if ($stmt->execute()) {
            // Track changes
            $changes = [];
            if ($old_data['name'] !== $name) $changes['name'] = ['old' => htmlspecialchars($old_data['name']), 'new' => htmlspecialchars($name)];
            if ($old_data['username'] !== $username) $changes['username'] = ['old' => htmlspecialchars($old_data['username']), 'new' => htmlspecialchars($username)];
            if ($old_data['email'] !== $email) $changes['email'] = ['old' => htmlspecialchars($old_data['email']), 'new' => htmlspecialchars($email)];
            if ($old_data['role'] !== $role) $changes['role'] = ['old' => htmlspecialchars($old_data['role']), 'new' => htmlspecialchars($role)];
            if ($old_data['phone'] !== $phone) $changes['phone'] = ['old' => htmlspecialchars($old_data['phone']), 'new' => htmlspecialchars($phone)];
            if ($old_data['dob'] !== $dob) $changes['dob'] = ['old' => htmlspecialchars($old_data['dob']), 'new' => htmlspecialchars($dob)];
            if ($old_data['status'] !== $status) $changes['status'] = ['old' => htmlspecialchars($old_data['status']), 'new' => htmlspecialchars($status)];

            // Log the update if there were any changes
            if (!empty($changes)) {
                $autoLogger->logUserUpdate($_SESSION['user_id'], $userId, $changes);
    }

            $_SESSION['success'] = "User updated successfully.";
} else {
            // Throw exception if execute fails
             throw new Exception("Execute failed (UPDATE): " . $stmt->error);
        }
        
        $stmt->close();

    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating user: " . $e->getMessage();
        // Log the detailed error
        $adminUserId = $_SESSION['user_id'] ?? 'UnknownAdmin';
        $autoLogger->logError($adminUserId, "Failed to update user (ID: $userId): " . $e->getMessage());
}

    // Close connection before redirecting
    if ($conn) {
$conn->close();
    }
    
    // Redirect back to manage_users.php regardless of success or error
    header("Location: manage_users.php");
    exit();
}

// If the script is accessed without POST method, redirect
$_SESSION['error'] = "Invalid request method.";
header("Location: manage_users.php");
exit();
// No closing ?> tag is needed and can prevent whitespace issues

