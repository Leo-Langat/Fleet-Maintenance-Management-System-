<?php
require_once 'db_connect.php';
require_once 'dashboard_functions.php';

header('Content-Type: application/json');

try {
    $query = "SELECT 
                ms.schedule_id,
                v.registration_no,
                mt.task_name,
                ms.schedule_date,
                ms.schedule_start_time,
                ms.schedule_end_time,
                ms.status
              FROM maintenance_schedule ms
              JOIN vehicles v ON ms.vehicle_id = v.vehicle_id
              JOIN maintenance_tasks mt ON ms.task_id = mt.task_id
              WHERE ms.status IN ('Scheduled', 'In Progress')
              ORDER BY ms.schedule_date ASC, ms.schedule_start_time ASC";
    
    $result = $conn->query($query);
    
    $maintenance = [];
    while ($row = $result->fetch_assoc()) {
        $maintenance[] = $row;
    }
    
    echo json_encode($maintenance);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?> 