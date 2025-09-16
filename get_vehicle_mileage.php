<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is a mechanic
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mechanic') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

if (isset($_GET['vehicle_id'])) {
    $vehicle_id = $_GET['vehicle_id'];
    
    // Get vehicle mileage
    $query = "SELECT mileage FROM vehicles WHERE vehicle_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $vehicle = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'mileage' => $vehicle['mileage']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Vehicle not found'
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Vehicle ID not provided'
    ]);
}

$conn->close();
?> 