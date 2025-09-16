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

echo "<h2>Debugging Maintenance Booking Process</h2>";

// Test 1: Check database connection
if (!$conn->ping()) {
    echo "<p style='color: red;'>✗ Database connection failed</p>";
    exit;
}
echo "<p style='color: green;'>✓ Database connection successful</p>";

// Test 2: Check actual table names in database
echo "<h3>Actual Table Names in Database:</h3>";
$result = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
    echo "<p>Table: {$row[0]}</p>";
}

// Test 3: Check if Maintenance_Schedule table exists and its structure
if (in_array('Maintenance_Schedule', $tables)) {
    echo "<p style='color: green;'>✓ Maintenance_Schedule table exists</p>";
    
    $result = $conn->query("DESCRIBE Maintenance_Schedule");
    echo "<h4>Maintenance_Schedule Structure:</h4>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>✗ Maintenance_Schedule table does not exist</p>";
    echo "<p>Available tables: " . implode(', ', $tables) . "</p>";
}

// Test 4: Check Maintenance_Tasks data
echo "<h3>Maintenance Tasks:</h3>";
$result = $conn->query("SELECT * FROM Maintenance_Tasks");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "<p>ID: {$row['task_id']} - {$row['task_name']}</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Error querying Maintenance_Tasks: " . $conn->error . "</p>";
}

// Test 5: Check Service_Centers data
echo "<h3>Service Centers:</h3>";
$result = $conn->query("SELECT * FROM Service_Centers");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "<p>ID: {$row['service_center_id']} - {$row['service_center_name']} (Task ID: {$row['task_id']})</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Error querying Service_Centers: " . $conn->error . "</p>";
}

// Test 6: Check Vehicles with assigned drivers
echo "<h3>Vehicles with Assigned Drivers:</h3>";
$result = $conn->query("SELECT v.vehicle_id, v.registration_no, v.assigned_driver, u.name as driver_name 
                       FROM Vehicles v 
                       LEFT JOIN Users u ON v.assigned_driver = u.user_id 
                       WHERE v.assigned_driver IS NOT NULL");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "<p>Vehicle ID: {$row['vehicle_id']} - {$row['registration_no']} (Driver: {$row['driver_name']})</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Error querying Vehicles: " . $conn->error . "</p>";
}

// Test 7: Simulate the exact booking process
echo "<h3>Simulating Booking Process:</h3>";

// Get form data
$date = $_POST['date'] ?? '';
$maintenance_task = $_POST['maintenance_task'] ?? '';
$additional_info = $_POST['additional_info'] ?? '';
$service_center_id = $_POST['service_center_id'] ?? '';
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$task_id = $_POST['task_id'] ?? '';

echo "<p>Form data received:</p>";
echo "<ul>";
echo "<li>Date: $date</li>";
echo "<li>Maintenance Task: $maintenance_task</li>";
echo "<li>Service Center ID: $service_center_id</li>";
echo "<li>Start Time: $start_time</li>";
echo "<li>End Time: $end_time</li>";
echo "<li>Task ID: $task_id</li>";
echo "</ul>";

// Get vehicle_id for a test driver (using first available)
$vehicle_query = "SELECT vehicle_id FROM Vehicles WHERE assigned_driver IS NOT NULL LIMIT 1";
$result = $conn->query($vehicle_query);
$vehicle = $result->fetch_assoc();

if (!$vehicle) {
    echo "<p style='color: red;'>✗ No vehicles with assigned drivers found</p>";
    exit;
}

$vehicle_id = $vehicle['vehicle_id'];
echo "<p style='color: green;'>✓ Using vehicle ID: $vehicle_id</p>";

// Validate task_id
if ($task_id) {
    $task_query = "SELECT task_id FROM Maintenance_Tasks WHERE task_id = ?";
    $stmt = $conn->prepare($task_query);
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $task = $result->fetch_assoc();
    
    if (!$task) {
        echo "<p style='color: red;'>✗ Invalid task_id: $task_id</p>";
        exit;
    }
    echo "<p style='color: green;'>✓ Task ID $task_id is valid</p>";
} else {
    echo "<p style='color: red;'>✗ No task_id provided</p>";
    exit;
}

// Validate service_center_id
$center_query = "SELECT service_center_id FROM Service_Centers WHERE service_center_id = ?";
$stmt = $conn->prepare($center_query);
$stmt->bind_param("i", $service_center_id);
$stmt->execute();
$result = $stmt->get_result();
$center = $result->fetch_assoc();

if (!$center) {
    echo "<p style='color: red;'>✗ Invalid service_center_id: $service_center_id</p>";
    exit;
}
echo "<p style='color: green;'>✓ Service Center ID $service_center_id is valid</p>";

// Test the actual INSERT statement
echo "<h3>Testing INSERT Statement:</h3>";
$insert_query = "INSERT INTO Maintenance_Schedule 
                (vehicle_id, task_id, service_center_id, schedule_date, schedule_start_time, schedule_end_time, status, additional_info) 
                VALUES (?, ?, ?, ?, ?, ?, 'Scheduled', ?)";

$stmt = $conn->prepare($insert_query);
if (!$stmt) {
    echo "<p style='color: red;'>✗ Failed to prepare statement: " . $conn->error . "</p>";
    exit;
}
echo "<p style='color: green;'>✓ Statement prepared successfully</p>";

$stmt->bind_param("iiissss", 
    $vehicle_id, 
    $task_id, 
    $service_center_id, 
    $date, 
    $start_time, 
    $end_time, 
    $additional_info
);

echo "<p>Attempting to execute insert with:</p>";
echo "<ul>";
echo "<li>vehicle_id: $vehicle_id</li>";
echo "<li>task_id: $task_id</li>";
echo "<li>service_center_id: $service_center_id</li>";
echo "<li>date: $date</li>";
echo "<li>start_time: $start_time</li>";
echo "<li>end_time: $end_time</li>";
echo "<li>additional_info: $additional_info</li>";
echo "</ul>";

if ($stmt->execute()) {
    echo "<p style='color: green;'>✓ Insert successful! New schedule ID: " . $stmt->insert_id . "</p>";
    
    // Clean up - delete the test record
    $delete_query = "DELETE FROM Maintenance_Schedule WHERE schedule_id = ?";
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