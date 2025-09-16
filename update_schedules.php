<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['schedule_id'];
    $date = $_POST['schedule_date'];
    $start = $_POST['schedule_start_time'];
    $end = $_POST['schedule_end_time'];
    $status = $_POST['status'];
    $info = $_POST['additional_info'];

    $stmt = $conn->prepare("UPDATE Maintenance_Schedule 
                            SET schedule_date = ?, schedule_start_time = ?, schedule_end_time = ?, status = ?, additional_info = ?
                            WHERE schedule_id = ?");
    $stmt->bind_param("sssssi", $date, $start, $end, $status, $info, $id);

    if ($stmt->execute()) {
        header("Location: maintenance_schedules.php?updated=1");
    } else {
        echo "Error updating record.";
    }

    $stmt->close();
    $conn->close();
}
?>
