<?php
session_start();
require_once 'db_connect.php';
require_once 'utils/auto_logger.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'User not logged in']));
}

$logger = new AutoLogger();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
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
        die(json_encode(['success' => false, 'message' => 'No vehicle assigned to driver']));
    }
    
    $vehicle_id = $vehicle['vehicle_id'];
    
        // Get task_id - use the task_id sent from the form if available, otherwise look up by name
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
            die(json_encode(['success' => false, 'message' => 'Invalid maintenance task']));
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
            die(json_encode(['success' => false, 'message' => 'Invalid maintenance task ID']));
        }
    }

// Validate all required data is present
if (!$vehicle_id || !$task_id || !$service_center_id || !$date || !$start_time || !$end_time) {
    $logger->logError($user_id, "Missing required data: vehicle_id=$vehicle_id, task_id=$task_id, service_center_id=$service_center_id, date=$date, start_time=$start_time, end_time=$end_time");
    die(json_encode(['success' => false, 'message' => 'Missing required data for maintenance booking']));
}
    
    // Insert into maintenance_schedule
    $insert_query = "INSERT INTO maintenance_schedule 
                    (vehicle_id, task_id, service_center_id, schedule_date, schedule_start_time, schedule_end_time, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'Scheduled')";
    
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iiisss", 
        $vehicle_id, 
        $task_id, 
        $service_center_id, 
        $date, 
        $start_time, 
        $end_time
    );
    
    if ($stmt->execute()) {
        // Log successful maintenance booking
        $logger->logMaintenanceBooking($user_id, $vehicle_id, $maintenance_task, $date, $additional_info);
        echo json_encode(['success' => true, 'message' => 'Maintenance scheduled successfully']);
    } else {
        $error_msg = "Error scheduling maintenance: " . $stmt->error;
        $_SESSION['error'] = $error_msg;
        $logger->logError($user_id, "Failed to schedule maintenance: " . $stmt->error);
        $logger->logError($user_id, "SQL Error Code: " . $stmt->errno);
        $logger->logError($user_id, "Attempted insert with: vehicle_id=$vehicle_id, task_id=$task_id, service_center_id=$service_center_id, date=$date, start_time=$start_time, end_time=$end_time");
        echo json_encode(['success' => false, 'message' => $error_msg]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
