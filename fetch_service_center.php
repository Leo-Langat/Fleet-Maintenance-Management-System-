<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_center_id'])) {
    $service_center_id = $_POST['service_center_id'];

    $query = "SELECT sc.service_center_id, sc.service_center_name, mt.task_name 
            FROM Service_Centers sc
            LEFT JOIN Maintenance_Tasks mt ON sc.task_id = mt.task_id
            WHERE sc.service_center_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $service_center_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'service_center_id' => $row['service_center_id'],
            'service_center_name' => $row['service_center_name'],
            'task_name' => $row['task_name']
        ]);
    } else {
        echo json_encode(['error' => 'Service center not found']);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>
