<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_center_name = trim($_POST['service_center_name']);
    $task_id = $_POST['task_id'];

    if (!empty($service_center_name) && !empty($task_id)) {
        $query = "INSERT INTO Service_Centers (name, task_id) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $service_center_name, $task_id);
        
        if ($stmt->execute()) {
            echo "<script>alert('Service center added successfully!'); window.location.href='dashboard.php';</script>";
        } else {
            echo "<script>alert('Error adding service center!'); window.history.back();</script>";
        }
        
        $stmt->close();
    }
}

$conn->close();
?>
