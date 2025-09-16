<?php
require_once 'db_connect.php';

function getPendingJobs($conn) {
    $today = date('Y-m-d');
    $query = "SELECT COUNT(*) as total FROM maintenance_schedule WHERE schedule_date = ? AND status = 'Scheduled'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'];
}

function getInProgressJobs($conn) {
    $query = "SELECT COUNT(*) as total FROM maintenance_schedule WHERE status = 'In Progress'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['total'];
}

function getCompletedJobs($conn) {
    $today = date('Y-m-d');
    $query = "SELECT COUNT(*) as total FROM maintenance_schedule WHERE status = 'Completed' AND schedule_date = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'];
}


function getPendingMaintenance($conn, $limit = 6) {
    $today = date('Y-m-d');
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
              WHERE ms.status IN ('Scheduled', 'Admitted')
              AND ms.schedule_date >= ?
              ORDER BY ms.schedule_date ASC, ms.schedule_start_time ASC
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $today, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $maintenance = [];
    while ($row = $result->fetch_assoc()) {
        $maintenance[] = $row;
    }
    
    return $maintenance;
}
?> 