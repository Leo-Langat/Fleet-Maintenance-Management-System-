<?php
include 'db_connect.php'; // Ensure you have a database connection file

$query = "SELECT task_id, task_name FROM maintenance_tasks";
$result = mysqli_query($conn, $query);

$options = "";
while ($row = mysqli_fetch_assoc($result)) {
    $options .= "<option value='{$row['task_id']}:{$row['task_name']}'>{$row['task_name']}</option>
";
}

echo $options;
?>
