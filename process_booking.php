<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST['date'];
    $timeSlot = $_POST['timeSlot'];

    if (!empty($date) && !empty($timeSlot)) {
        // Connect to database (Update with your DB credentials)
        $conn = new mysqli("localhost", "root", "", "fleet_management");

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Insert booking into database
        $stmt = $conn->prepare("INSERT INTO bookings (date, time_slot) VALUES (?, ?)");
        $stmt->bind_param("ss", $date, $timeSlot);
        if ($stmt->execute()) {
            echo "Booking confirmed for $date at $timeSlot.";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
        $conn->close();
    } else {
        echo "Please select a date and time slot.";
    }
} else {
    echo "Invalid request.";
}
?>
