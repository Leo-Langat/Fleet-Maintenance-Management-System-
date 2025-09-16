<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

$current_user_role = $_SESSION['role'];
$sql = "";

// If user is admin, show only drivers and mechanics
if ($current_user_role === 'admin') {
    $sql = "SELECT user_id, name, role FROM Users 
            WHERE status = 'active' 
            AND role IN ('driver', 'mechanic')
            ORDER BY role, name ASC";
} 
// If user is driver or mechanic, show only admins
else if ($current_user_role === 'driver' || $current_user_role === 'mechanic') {
    $sql = "SELECT user_id, name, role FROM Users 
            WHERE status = 'active' 
            AND role = 'admin'
            ORDER BY name ASC";
}

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = [
        'id' => $row['user_id'],
        'name' => $row['name'],
        'role' => $row['role']
    ];
}

echo json_encode(['status' => 'success', 'users' => $users]);

$stmt->close();
$conn->close();
?> 