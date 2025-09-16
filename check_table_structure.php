<?php
require_once 'db_connect.php';

echo "Maintenance_Schedule table structure:\n";
$result = $conn->query("DESCRIBE maintenance_schedule");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

$conn->close();
?> 