<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service_center_id = $_POST['service_center_id'];
    $service_center_name = $_POST['service_center_name'];
    $task_name = $_POST['task_name'];

    // Retrieve task_id based on task_name
    $taskQuery = "SELECT task_id FROM Maintenance_Tasks WHERE task_name = ?";
    $stmt = $conn->prepare($taskQuery);
    $stmt->bind_param("s", $task_name);
    $stmt->execute();
    $taskResult = $stmt->get_result();
    
    if ($taskResult->num_rows > 0) {
        $taskRow = $taskResult->fetch_assoc();
        $task_id = $taskRow['task_id'];

        // Update the service center (remove mechanic_id)
        $updateQuery = "UPDATE Service_Centers SET service_center_name = ?, task_id = ? WHERE service_center_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sii", $service_center_name, $task_id, $service_center_id);
        
        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "Error updating: " . $stmt->error;
        }
    } else {
        echo "Invalid task name!";
    }
}
?>
