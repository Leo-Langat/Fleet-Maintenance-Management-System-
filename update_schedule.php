<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $schedule_id = $_POST['schedule_id'];
    $schedule_date = $_POST['schedule_date'];
    $schedule_start_time = $_POST['schedule_start_time'];
    $schedule_end_time = $_POST['schedule_end_time'];

    $query = "UPDATE Maintenance_Schedule 
              SET schedule_date = ?, schedule_start_time = ?, schedule_end_time = ? 
              WHERE schedule_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $schedule_date, $schedule_start_time, $schedule_end_time, $schedule_id);

    if ($stmt->execute()) {
        echo "Schedule updated successfully!";
    } else {
        echo "Error updating schedule.";
    }
}
?>
