<?php
session_start();
require_once 'db_connect.php';

// Simulate POST data for testing
$_POST = [
    'date' => date('Y-m-d', strtotime('+2 days')),
    'maintenance_task' => 'Oil Change',
    'additional_info' => 'Test booking',
    'service_center_id' => '1',
    'start_time' => '09:00:00',
    'end_time' => '11:00:00',
    'task_id' => '1'
];

// Simulate a logged-in driver (using a driver that has a vehicle assigned)
$_SESSION['user_id'] = 2; // James Patrick has vehicle assigned

echo "<h2>Final Booking Test</h2>";

// Test the actual booking process
$date = $_POST['date'] ?? '';
$maintenance_task = $_POST['maintenance_task'] ?? '';
$additional_info = $_POST['additional_info'] ?? '';
$service_center_id = $_POST['service_center_id'] ?? '';
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';

// Get the vehicle_id for the logged-in driver
$user_id = $_SESSION['user_id'];
$vehicle_query = "SELECT vehicle_id FROM vehicles WHERE assigned_driver = ?";
$stmt = $conn->prepare($vehicle_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$vehicle = $result->fetch_assoc();

if (!$vehicle) {
    echo "<p style='color: red;'>✗ No vehicle assigned to driver</p>";
    exit;
}

$vehicle_id = $vehicle['vehicle_id'];
echo "<p style='color: green;'>✓ Found vehicle ID: $vehicle_id</p>";

// Get task_id
$task_id = $_POST['task_id'] ?? null;

if (!$task_id) {
    // Fallback to looking up by task name
    $task_query = "SELECT task_id FROM maintenance_tasks WHERE task_name = ?";
    $stmt = $conn->prepare($task_query);
    $stmt->bind_param("s", $maintenance_task);
    $stmt->execute();
    $result = $stmt->get_result();
    $task = $result->fetch_assoc();
    
    if (!$task) {
        echo "<p style='color: red;'>✗ Invalid maintenance task</p>";
        exit;
    }
    
    $task_id = $task['task_id'];
} else {
    // Validate that the provided task_id exists
    $task_query = "SELECT task_id FROM maintenance_tasks WHERE task_id = ?";
    $stmt = $conn->prepare($task_query);
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $task = $result->fetch_assoc();
    
    if (!$task) {
        echo "<p style='color: red;'>✗ Invalid maintenance task ID</p>";
        exit;
    }
}

echo "<p style='color: green;'>✓ Task ID: $task_id</p>";

// Validate all required data is present
if (!$vehicle_id || !$task_id || !$service_center_id || !$date || !$start_time || !$end_time) {
    echo "<p style='color: red;'>✗ Missing required data</p>";
    exit;
}

// Insert into maintenance_schedule
$insert_query = "INSERT INTO maintenance_schedule 
                (vehicle_id, task_id, service_center_id, schedule_date, schedule_start_time, schedule_end_time, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'Scheduled')";

$stmt = $conn->prepare($insert_query);
if (!$stmt) {
    echo "<p style='color: red;'>✗ Failed to prepare statement: " . $conn->error . "</p>";
    exit;
}

$stmt->bind_param("iiisss", 
    $vehicle_id, 
    $task_id, 
    $service_center_id, 
    $date, 
    $start_time, 
    $end_time
);

echo "<p>Attempting to insert with:</p>";
echo "<ul>";
echo "<li>vehicle_id: $vehicle_id</li>";
echo "<li>task_id: $task_id</li>";
echo "<li>service_center_id: $service_center_id</li>";
echo "<li>date: $date</li>";
echo "<li>start_time: $start_time</li>";
echo "<li>end_time: $end_time</li>";
echo "</ul>";

if ($stmt->execute()) {
    echo "<p style='color: green;'>✓ SUCCESS! Maintenance scheduled successfully!</p>";
    echo "<p>New schedule ID: " . $stmt->insert_id . "</p>";
    
    // Clean up - delete the test record
    $delete_query = "DELETE FROM maintenance_schedule WHERE schedule_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $stmt->insert_id);
    $delete_stmt->execute();
    echo "<p style='color: blue;'>Test record cleaned up</p>";
} else {
    echo "<p style='color: red;'>✗ Insert failed: " . $stmt->error . "</p>";
    echo "<p>Error code: " . $stmt->errno . "</p>";
}

$conn->close();
?> 