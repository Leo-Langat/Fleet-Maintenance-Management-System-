<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_center_name'])) {
    $service_center_name = trim($_POST['service_center_name']);
    $current_id = isset($_POST['current_id']) ? $_POST['current_id'] : null;
    
    // Check if name exists, excluding current service center if editing
    $query = "SELECT COUNT(*) as count FROM Service_Centers WHERE service_center_name = ?";
    $params = [$service_center_name];
    $types = "s";
    
    if ($current_id !== null) {
        $query .= " AND service_center_id != ?";
        $params[] = $current_id;
        $types .= "i";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo json_encode(['exists' => $row['count'] > 0]);
    
    $stmt->close();
    $conn->close();
}
?> 