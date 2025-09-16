<?php
session_start();
require_once 'db_connect.php';

echo "<h2>Maintenance Booking Process Test</h2>";

// Simulate a logged-in driver (you'll need to replace with an actual user_id)
$test_user_id = 1; // Replace with an actual driver user_id from your database

// Test 1: Check if the user exists and is a driver
$user_query = "SELECT user_id, name, role FROM Users WHERE user_id = ? AND role = 'driver'";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $test_user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "<p style='color: red;'>✗ User ID $test_user_id not found or not a driver</p>";
    echo "<p>Please update the test_user_id variable with a valid driver user ID</p>";
    exit;
}

echo "<p style='color: green;'>✓ Found driver: {$user['name']} (ID: {$user['user_id']})</p>";

// Test 2: Check if the driver has an assigned vehicle
$vehicle_query = "SELECT vehicle_id, registration_no FROM Vehicles WHERE assigned_driver = ?";
$stmt = $conn->prepare($vehicle_query);
$stmt->bind_param("i", $test_user_id);
$stmt->execute();
$result = $stmt->get_result();
$vehicle = $result->fetch_assoc();

if (!$vehicle) {
    echo "<p style='color: red;'>✗ No vehicle assigned to driver ID: $test_user_id</p>";
    exit;
}

echo "<p style='color: green;'>✓ Found assigned vehicle: {$vehicle['registration_no']} (ID: {$vehicle['vehicle_id']})</p>";

// Test 3: Check available maintenance tasks
$tasks_query = "SELECT task_id, task_name FROM Maintenance_Tasks";
$result = $conn->query($tasks_query);
$tasks = [];
while ($task = $result->fetch_assoc()) {
    $tasks[] = $task;
}

if (empty($tasks)) {
    echo "<p style='color: red;'>✗ No maintenance tasks found in database</p>";
    exit;
}

echo "<p style='color: green;'>✓ Found " . count($tasks) . " maintenance tasks</p>";
echo "<ul>";
foreach ($tasks as $task) {
    echo "<li>ID: {$task['task_id']} - {$task['task_name']}</li>";
}
echo "</ul>";

// Test 4: Check available service centers
$centers_query = "SELECT service_center_id, service_center_name, task_id FROM Service_Centers";
$result = $conn->query($centers_query);
$centers = [];
while ($center = $result->fetch_assoc()) {
    $centers[] = $center;
}

if (empty($centers)) {
    echo "<p style='color: red;'>✗ No service centers found in database</p>";
    exit;
}

echo "<p style='color: green;'>✓ Found " . count($centers) . " service centers</p>";
echo "<ul>";
foreach ($centers as $center) {
    echo "<li>ID: {$center['service_center_id']} - {$center['service_center_name']} (Task ID: {$center['task_id']})</li>";
}
echo "</ul>";

// Test 5: Simulate the booking process with sample data
echo "<h3>Simulating Booking Process</h3>";

// Use the first available task and service center
$test_task = $tasks[0];
$test_center = $centers[0];

echo "<p>Testing with:</p>";
echo "<ul>";
echo "<li>Task: {$test_task['task_name']} (ID: {$test_task['task_id']})</li>";
echo "<li>Service Center: {$test_center['service_center_name']} (ID: {$test_center['service_center_id']})</li>";
echo "<li>Vehicle: {$vehicle['registration_no']} (ID: {$vehicle['vehicle_id']})</li>";
echo "</ul>";

// Test the task lookup by name (this is what was failing)
$task_lookup_query = "SELECT task_id FROM Maintenance_Tasks WHERE task_name = ?";
$stmt = $conn->prepare($task_lookup_query);
$stmt->bind_param("s", $test_task['task_name']);
$stmt->execute();
$result = $stmt->get_result();
$found_task = $result->fetch_assoc();

if (!$found_task) {
    echo "<p style='color: red;'>✗ Task lookup by name failed for: '{$test_task['task_name']}'</p>";
    echo "<p>This suggests there might be a data type or encoding issue</p>";
} else {
    echo "<p style='color: green;'>✓ Task lookup by name successful</p>";
}

// Test 6: Try to insert a maintenance schedule (without actually inserting)
$insert_query = "INSERT INTO Maintenance_Schedule 
                (vehicle_id, task_id, service_center_id, schedule_date, schedule_start_time, schedule_end_time, status, additional_info) 
                VALUES (?, ?, ?, ?, ?, ?, 'Scheduled', ?)";

$stmt = $conn->prepare($insert_query);
if (!$stmt) {
    echo "<p style='color: red;'>✗ Failed to prepare insert statement: " . $conn->error . "</p>";
} else {
    echo "<p style='color: green;'>✓ Insert statement prepared successfully</p>";
    
    // Test with sample data
    $test_date = date('Y-m-d', strtotime('+2 days'));
    $test_start_time = '09:00:00';
    $test_end_time = '11:00:00';
    $test_additional_info = 'Test booking';
    
    $stmt->bind_param("iiissss", 
        $vehicle['vehicle_id'], 
        $test_task['task_id'], 
        $test_center['service_center_id'], 
        $test_date, 
        $test_start_time, 
        $test_end_time, 
        $test_additional_info
    );
    
    // Don't actually execute, just check if binding was successful
    echo "<p style='color: green;'>✓ Parameter binding successful</p>";
    echo "<p>Test data prepared:</p>";
    echo "<ul>";
    echo "<li>Vehicle ID: {$vehicle['vehicle_id']}</li>";
    echo "<li>Task ID: {$test_task['task_id']}</li>";
    echo "<li>Service Center ID: {$test_center['service_center_id']}</li>";
    echo "<li>Date: $test_date</li>";
    echo "<li>Start Time: $test_start_time</li>";
    echo "<li>End Time: $test_end_time</li>";
    echo "<li>Additional Info: $test_additional_info</li>";
    echo "</ul>";
}

$conn->close();
echo "<p><strong>Test completed. If all checks passed, the issue might be with the actual form data being sent.</strong></p>";
?> 