<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is a mechanic
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mechanic') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_id = $_POST['schedule_id'] ?? null;
    $vehicle_id = $_POST['vehicle_id'] ?? null;
    $final_mileage = $_POST['final_mileage'] ?? null;
    $service_notes = $_POST['service_notes'] ?? '';
    
    if (!$schedule_id || !$vehicle_id || !$final_mileage) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Validate final mileage
    $final_mileage = intval($final_mileage);
    if ($final_mileage <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid mileage value']);
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get maintenance task details and current vehicle mileage
        $get_task_query = "SELECT ms.task_id, ms.schedule_date, ms.schedule_start_time, ms.schedule_end_time, ms.service_center_id, v.mileage as current_mileage 
                          FROM maintenance_schedule ms 
                          JOIN vehicles v ON ms.vehicle_id = v.vehicle_id
                          WHERE ms.schedule_id = ?";
        $stmt = $conn->prepare($get_task_query);
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $schedule = $result->fetch_assoc();
        
        if (!$schedule) {
            throw new Exception("Schedule not found");
        }
        
        // Validate that final mileage is greater than or equal to current mileage
        if ($final_mileage < $schedule['current_mileage']) {
            throw new Exception("Final mileage cannot be less than current mileage (" . $schedule['current_mileage'] . ")");
        }
        
        // Insert into service_history using final_mileage
        $insert_history_query = "INSERT INTO Service_History 
                               (vehicle_id, task_id, service_center_id, date_of_service, mileage_at_service, service_notes) 
                               VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_history_query);
        $stmt->bind_param("iiisis", 
            $vehicle_id, 
            $schedule['task_id'],
            $schedule['service_center_id'],
            $schedule['schedule_date'],
            $final_mileage,
            $service_notes
        );
        $stmt->execute();
        
        // Update maintenance_schedule status to 'Completed'
        $update_schedule_query = "UPDATE maintenance_schedule SET status = 'Completed' WHERE schedule_id = ?";
        $stmt = $conn->prepare($update_schedule_query);
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        
        // Update vehicle status to 'active' and update mileage
        $update_vehicle_query = "UPDATE vehicles SET status = 'active', mileage = ? WHERE vehicle_id = ?";
        $stmt = $conn->prepare($update_vehicle_query);
        $stmt->bind_param("ii", $final_mileage, $vehicle_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Vehicle checked out successfully']);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 