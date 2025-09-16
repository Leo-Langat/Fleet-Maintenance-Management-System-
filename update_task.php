<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task_id = $_POST['task_id'];
    $task_name = $_POST['task_name'];
    $estimated_time = $_POST['estimated_time'];
    $additional_details = $_POST['additional_details'];

    $sql = "UPDATE Maintenance_Tasks SET task_name=?, estimated_time=?, additional_details=? WHERE task_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisi", $task_name, $estimated_time, $additional_details, $task_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }

    $stmt->close();
    $conn->close();
}
?>
