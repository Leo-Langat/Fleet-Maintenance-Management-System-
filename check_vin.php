<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vin'])) {
    $vin = strtoupper(trim($_POST['vin']));
    
    // Check if VIN exists
    $query = "SELECT COUNT(*) as count FROM vehicles WHERE vin = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $vin);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo json_encode(['exists' => $row['count'] > 0]);
    
    $stmt->close();
    $conn->close();
}
?> 