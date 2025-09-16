<?php
include 'db_connect.php';

if (isset($_GET['id'])) {
    $vehicle_id = $_GET['id'];

    $sql = "SELECT * FROM vehicles WHERE vehicle_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $vehicle = $result->fetch_assoc();
        echo json_encode($vehicle);
    } else {
        echo json_encode(['error' => 'Vehicle not found.']);
    }
} else {
    echo json_encode(['error' => 'No vehicle ID provided.']);
}

$stmt->close();
$conn->close();

