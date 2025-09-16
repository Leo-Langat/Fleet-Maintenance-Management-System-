<?php
require_once 'db_connect.php';

function getPendingJobs($conn, $mechanic_id) {
    $today = date('Y-m-d');
    $query = "SELECT COUNT(*) as total FROM maintenance_schedule ms
              JOIN service_center_mechanics scm ON ms.service_center_id = scm.service_center_id
              WHERE ms.status = 'Scheduled' AND ms.schedule_date = ? AND scm.mechanic_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $today, $mechanic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['total'];
}

function getInProgressJobs($conn, $mechanic_id) {
    $query = "SELECT COUNT(*) as total FROM maintenance_schedule ms
              JOIN service_center_mechanics scm ON ms.service_center_id = scm.service_center_id
              WHERE ms.status = 'Admitted' AND scm.mechanic_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $mechanic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['total'];
}

function getCompletedJobs($conn, $mechanic_id) {
    $today = date('Y-m-d');
    $query = "SELECT COUNT(*) as total FROM maintenance_schedule ms
              JOIN service_center_mechanics scm ON ms.service_center_id = scm.service_center_id
              WHERE ms.status = 'Completed' AND ms.schedule_date = ? AND scm.mechanic_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $today, $mechanic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['total'];
}


function getPendingMaintenance($conn, $mechanic_id, $limit = 6) {
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
              JOIN service_center_mechanics scm ON ms.service_center_id = scm.service_center_id
              WHERE ms.status IN ('Scheduled', 'Admitted')
              AND ms.schedule_date >= ?
              AND scm.mechanic_id = ?
              ORDER BY ms.schedule_date ASC, ms.schedule_start_time ASC
              LIMIT ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", $today, $mechanic_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $maintenance = [];
    while ($row = $result->fetch_assoc()) {
        $maintenance[] = $row;
    }
    return $maintenance;
}
?> 