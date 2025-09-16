<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is a mechanic
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mechanic') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_id'])) {
    $schedule_id = $_POST['schedule_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get vehicle_id from maintenance_schedule
        $get_vehicle_query = "SELECT ms.vehicle_id 
                            FROM maintenance_schedule ms 
                            WHERE ms.schedule_id = ?";
        $stmt = $conn->prepare($get_vehicle_query);
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $schedule = $result->fetch_assoc();
        
        if (!$schedule) {
            throw new Exception("Schedule not found");
        }
        
        $vehicle_id = $schedule['vehicle_id'];
        
        // Update maintenance_schedule status to 'Admitted' (no mileage update)
        $update_schedule_query = "UPDATE maintenance_schedule SET status = 'Admitted' WHERE schedule_id = ?";
        $stmt = $conn->prepare($update_schedule_query);
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        
        // Update vehicle status to 'inactive' (keep existing mileage)
        $update_vehicle_query = "UPDATE vehicles SET status = 'inactive' WHERE vehicle_id = ?";
        $stmt = $conn->prepare($update_vehicle_query);
        $stmt->bind_param("i", $vehicle_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Vehicle admitted successfully']);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?> 