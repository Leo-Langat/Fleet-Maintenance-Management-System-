<?php
session_start();
require_once "db_connect.php";
require_once "utils/auto_logger.php";

$autoLogger = new AutoLogger();

header('Content-Type: application/json'); // Ensure JSON response

// Check if user is logged in and has the driver role.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'driver') {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit();
}

// Check if the form was submitted with POST.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['mileage']) || empty(trim($_POST['mileage']))) {
        echo json_encode(["status" => "error", "message" => "Mileage is required."]);
        exit();
    }

    if (!isset($_POST['vehicle_id']) || empty($_POST['vehicle_id'])) {
        echo json_encode(["status" => "error", "message" => "Vehicle not found."]);
        exit();
    }

    $newMileage = filter_var(trim($_POST['mileage']), FILTER_VALIDATE_INT);
    $vehicle_id = filter_var($_POST['vehicle_id'], FILTER_VALIDATE_INT);

    if ($newMileage === false || $vehicle_id === false) {
        echo json_encode(["status" => "error", "message" => "Invalid input."]);
        exit();
    }

    // Fetch the current mileage
    $sql = "SELECT mileage FROM Vehicles WHERE vehicle_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
        exit();
    }
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        echo json_encode(["status" => "error", "message" => "Vehicle not found."]);
        exit();
    }

    $vehicle = $result->fetch_assoc();
    $currentMileage = (int) $vehicle['mileage'];
    $stmt->close();

    // Check mileage validation
    if ($newMileage <= $currentMileage) {
        echo json_encode(["status" => "error", "message" => "Mileage entered must be greater than the current mileage ($currentMileage km)."]);
        exit();
    }

    // Update the vehicle's mileage
    $sqlUpdate = "UPDATE Vehicles SET mileage = ? WHERE vehicle_id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    if (!$stmtUpdate) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
        exit();
    }
    $stmtUpdate->bind_param("ii", $newMileage, $vehicle_id);

    if ($stmtUpdate->execute()) {
        // Log the mileage update
        $autoLogger->logTripUpdate($_SESSION['user_id'], $vehicle_id, $currentMileage, $newMileage);
        echo json_encode(["status" => "success", "message" => "Mileage updated successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error updating mileage: " . $stmtUpdate->error]);
    }
    $stmtUpdate->close();
}
$conn->close();
?>
