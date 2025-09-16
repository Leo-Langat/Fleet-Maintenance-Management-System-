<?php
require_once 'db_connect.php';

echo "<h2>Database Connection Test</h2>";

// Test 1: Check if we can connect to the database
if ($conn->ping()) {
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} else {
    echo "<p style='color: red;'>✗ Database connection failed</p>";
    exit;
}

// Test 2: Check if tables exist
$tables = ['Users', 'Vehicles', 'Maintenance_Tasks', 'Service_Centers', 'Maintenance_Schedule'];

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Table '$table' exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Table '$table' does not exist</p>";
    }
}

// Test 3: Check if there are any maintenance tasks
$result = $conn->query("SELECT COUNT(*) as count FROM Maintenance_Tasks");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>Number of maintenance tasks: " . $row['count'] . "</p>";
    
    if ($row['count'] > 0) {
        echo "<p style='color: green;'>✓ Maintenance tasks found</p>";
        // Show the tasks
        $tasks = $conn->query("SELECT task_id, task_name FROM Maintenance_Tasks");
        echo "<h3>Available Maintenance Tasks:</h3>";
        echo "<ul>";
        while ($task = $tasks->fetch_assoc()) {
            echo "<li>ID: {$task['task_id']} - {$task['task_name']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ No maintenance tasks found</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Error querying maintenance tasks</p>";
}

// Test 4: Check if there are any service centers
$result = $conn->query("SELECT COUNT(*) as count FROM Service_Centers");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>Number of service centers: " . $row['count'] . "</p>";
    
    if ($row['count'] > 0) {
        echo "<p style='color: green;'>✓ Service centers found</p>";
        // Show the service centers
        $centers = $conn->query("SELECT service_center_id, service_center_name, task_id FROM Service_Centers");
        echo "<h3>Available Service Centers:</h3>";
        echo "<ul>";
        while ($center = $centers->fetch_assoc()) {
            echo "<li>ID: {$center['service_center_id']} - {$center['service_center_name']} (Task ID: {$center['task_id']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ No service centers found</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Error querying service centers</p>";
}

// Test 5: Check if there are any vehicles with assigned drivers
$result = $conn->query("SELECT COUNT(*) as count FROM Vehicles WHERE assigned_driver IS NOT NULL");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>Number of vehicles with assigned drivers: " . $row['count'] . "</p>";
    
    if ($row['count'] > 0) {
        echo "<p style='color: green;'>✓ Vehicles with assigned drivers found</p>";
        // Show the vehicles
        $vehicles = $conn->query("SELECT v.vehicle_id, v.registration_no, v.assigned_driver, u.name as driver_name 
                                 FROM Vehicles v 
                                 LEFT JOIN Users u ON v.assigned_driver = u.user_id 
                                 WHERE v.assigned_driver IS NOT NULL");
        echo "<h3>Vehicles with Assigned Drivers:</h3>";
        echo "<ul>";
        while ($vehicle = $vehicles->fetch_assoc()) {
            echo "<li>Vehicle ID: {$vehicle['vehicle_id']} - {$vehicle['registration_no']} (Driver: {$vehicle['driver_name']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ No vehicles with assigned drivers found</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Error querying vehicles</p>";
}

$conn->close();
?> 