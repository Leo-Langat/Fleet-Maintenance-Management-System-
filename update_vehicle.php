<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $vehicle_id = $_POST['vehicle_id'];
    $registration_no = $_POST['registration_no'];
    $make = $_POST['make'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $vin = $_POST['vin'];
    $mileage = $_POST['mileage'];
    $fuel_type = $_POST['fuel_type'];
    $status = $_POST['status'];
    $assigned_driver = $_POST['assigned_driver'] ? $_POST['assigned_driver'] : NULL;

    // Check for existing registration number
    $checkRegNoSql = "SELECT * FROM vehicles WHERE registration_no = ? AND vehicle_id != ?";
    $checkRegNoStmt = $conn->prepare($checkRegNoSql);
    $checkRegNoStmt->bind_param("si", $registration_no, $vehicle_id);
    $checkRegNoStmt->execute();
    $regNoResult = $checkRegNoStmt->get_result();

    // Check for existing VIN
    $checkVinSql = "SELECT * FROM vehicles WHERE vin = ? AND vehicle_id != ?";
    $checkVinStmt = $conn->prepare($checkVinSql);
    $checkVinStmt->bind_param("si", $vin, $vehicle_id);
    $checkVinStmt->execute();
    $vinResult = $checkVinStmt->get_result();

    // Check if the driver is already assigned to another vehicle
    $checkDriverSql = "SELECT * FROM vehicles WHERE assigned_driver = ? AND vehicle_id != ?";
    $checkDriverStmt = $conn->prepare($checkDriverSql);
    $checkDriverStmt->bind_param("si", $assigned_driver, $vehicle_id);
    $checkDriverStmt->execute();
    $driverResult = $checkDriverStmt->get_result();

    // Determine if there are any conflicts and generate a detailed error message
    $errorMessage = "";
    if ($regNoResult->num_rows > 0) {
        $errorMessage .= "Error: Registration number already exists. ";
    }
    if ($vinResult->num_rows > 0) {
        $errorMessage .= "Error: VIN already exists.";
    }
    if ($driverResult->num_rows) {
        echo "Error: Driver is already assigned to another vehicle.";
        exit;
    }
    

    // Check if there is any error message
    if (!empty($errorMessage)) {
        echo $errorMessage;
    } else {
        // Proceed with the update if no duplicates are found
        $sql = "UPDATE vehicles SET registration_no=?, make=?, model=?, year=?, vin=?, mileage=?, fuel_type=?, assigned_driver = ?, status=? WHERE vehicle_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssisisssi", $registration_no, $make, $model, $year, $vin, $mileage, $fuel_type,$assigned_driver, $status, $vehicle_id);

        if ($stmt->execute()) {
            echo "Vehicle updated successfully.";
        } else {
            echo "Error updating vehicle: " . $stmt->error;
        }

        $stmt->close();
    }

    $checkRegNoStmt->close();
    $checkVinStmt->close();
    $checkDriverStmt->close(); 
} else {
    echo "Invalid request method.";
}


$conn->close();
