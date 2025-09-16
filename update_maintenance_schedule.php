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
    $schedule_id = $_POST['schedule_id'] ?? '';
    $date = $_POST['date'] ?? '';
    $service_center_id = $_POST['service_center_id'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';

    // Validate required fields
    if (!$schedule_id || !$date || !$service_center_id || !$start_time || !$end_time) {
        die(json_encode(['success' => false, 'message' => 'All fields are required']));
    }

    // Get the vehicle_id and task_id for the schedule
    $query = "SELECT vehicle_id, task_id FROM maintenance_schedule WHERE schedule_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $schedule = $result->fetch_assoc();

    if (!$schedule) {
        die(json_encode(['success' => false, 'message' => 'Schedule not found']));
    }

    // Check if the new timeslot is available
    $check_query = "SELECT COUNT(*) as count FROM maintenance_schedule 
                   WHERE service_center_id = ? 
                   AND schedule_date = ? 
                   AND ((schedule_start_time <= ? AND schedule_end_time > ?) 
                   OR (schedule_start_time < ? AND schedule_end_time >= ?)
                   OR (schedule_start_time >= ? AND schedule_end_time <= ?))
                   AND schedule_id != ?
                   AND status = 'Scheduled'";
    
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("isssssssi", 
        $service_center_id, 
        $date, 
        $start_time, $start_time,
        $end_time, $end_time,
        $start_time, $end_time,
        $schedule_id
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];

    if ($count > 0) {
        die(json_encode(['success' => false, 'message' => 'Selected timeslot is not available']));
    }

    // Update the maintenance schedule
    $update_query = "UPDATE maintenance_schedule 
                    SET schedule_date = ?, 
                        service_center_id = ?, 
                        schedule_start_time = ?, 
                        schedule_end_time = ? 
                    WHERE schedule_id = ?";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sissi", 
        $date, 
        $service_center_id, 
        $start_time, 
        $end_time, 
        $schedule_id
    );

    if ($stmt->execute()) {
        // Log the rescheduling
        $logger->logMaintenanceRescheduling(
            $_SESSION['user_id'],
            $schedule['vehicle_id'],
            $schedule['task_id'],
            $date,
            $start_time,
            $end_time
        );
        echo json_encode(['success' => true, 'message' => 'Maintenance schedule updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating maintenance schedule']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 