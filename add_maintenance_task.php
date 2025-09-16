<?php
include 'db_connect.php';

$task_name = "";
$estimated_time = "";
$additional_details = "";
$response = ["status" => "", "message" => ""];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task_name = trim($_POST['task_name']);
    $estimated_time = intval($_POST['estimated_time']);
    $additional_details = trim($_POST['additional_details']);

    if (empty($task_name) || empty($estimated_time)) {
        $response["status"] = "error";
        $response["message"] = "Task name and estimated time are required.";
    } else {
        // Check if the task_name already exists
        $check_query = "SELECT COUNT(*) FROM Maintenance_Tasks WHERE task_name = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $task_name);
        $check_stmt->execute();
        $check_stmt->bind_result($count);
        $check_stmt->fetch();
        $check_stmt->close();

        if ($count > 0) {
            $response["status"] = "error";
            $response["message"] = "Task name already exists. Please choose a different name.";
        } else {
            // Insert the new task if the name is unique
            $query = "INSERT INTO Maintenance_Tasks (task_name, estimated_time, additional_details) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sis", $task_name, $estimated_time, $additional_details);

            if ($stmt->execute()) {
                $response["status"] = "success";
                $response["message"] = "Maintenance task added successfully.";
            } else {
                $response["status"] = "error";
                $response["message"] = "Error adding maintenance task.";
            }
            $stmt->close();
        }
    }
}

$conn->close();
echo json_encode($response);
?>
