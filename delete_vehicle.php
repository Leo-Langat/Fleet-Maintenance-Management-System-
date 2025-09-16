<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $vehicle_id = $_POST['id'];

    $sql = "DELETE FROM vehicles WHERE vehicle_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $vehicle_id);

    if ($stmt->execute()) {
        echo "Vehicle deleted successfully.";
    } else {
        echo "Error deleting vehicle: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Invalid request.";
}

$conn->close();

