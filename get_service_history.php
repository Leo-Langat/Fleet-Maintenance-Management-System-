<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registration_no = $_POST['registration_no'] ?? '';
    
    if (empty($registration_no)) {
        echo json_encode(['error' => 'Registration number is required']);
        exit;
    }

    // Get vehicle_id from registration number
    $vehicle_query = "SELECT vehicle_id FROM Vehicles WHERE registration_no = ?";
    $stmt = $conn->prepare($vehicle_query);
    $stmt->bind_param("s", $registration_no);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Vehicle not found']);
        exit;
    }
    
    $vehicle = $result->fetch_assoc();
    $vehicle_id = $vehicle['vehicle_id'];
    
    // Get service history for the vehicle
    $history_query = "SELECT 
                        sh.date_of_service,
                        sh.checkout_time,
                        mt.task_name,
                        sc.service_center_name,
                        sh.mileage_at_service,
                        sh.service_notes
                    FROM Service_History sh
                    JOIN Maintenance_Tasks mt ON sh.task_id = mt.task_id
                    JOIN Service_Centers sc ON sh.service_center_id = sc.service_center_id
                    WHERE sh.vehicle_id = ?
                    ORDER BY sh.date_of_service DESC, sh.checkout_time DESC";
    
    $stmt = $conn->prepare($history_query);
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    
    echo json_encode($history);
    exit;
}

echo json_encode(['error' => 'Invalid request method']);
?> 