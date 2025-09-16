<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['service_center_id'])) {
    $service_center_id = $_POST['service_center_id'];

    $sql = "DELETE FROM Service_Centers WHERE service_center_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $service_center_id);

    if ($stmt->execute()) {
        echo "Service center deleted successfully!";
    } else {
        echo "Error deleting service center: " . $conn->error;
    }
}
?>
