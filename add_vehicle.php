<?php
session_start();
require_once 'utils/auto_logger.php';

// Include the database connection file
include 'db_connect.php';

// Function to validate VIN
function isValidVin($vin) {
    // Check if the VIN is 17 characters long and contains valid characters
    return preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $vin);
}

// Function to validate registration number format
function isValidRegistrationNo($registration_no) {
    // Match the format like KDQ 111T (3 letters, space, 3 digits, 1 letter)
    return preg_match('/^[A-Z]{3} [0-9]{3}[A-Z]$/', $registration_no);
}

// Function to validate year
function isValidYear($year) {
    // Get the current year
    $currentYear = date('Y');
    
    // Check if the year is a valid 4-digit number and not in the future
    return is_numeric($year) && strlen($year) == 4 && $year <= $currentYear;
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$autoLogger = new AutoLogger();

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data safely
    $registration_no = strtoupper(trim($_POST['registration_no'] ?? ''));
    $model = $_POST['model'] ?? '';
    $make = $_POST['make'] ?? '';
    $year = $_POST['year'] ?? '';
    $vin = strtoupper(trim($_POST['vin'] ?? ''));
    $mileage = $_POST['mileage'] ?? '';
    $fuel_type = $_POST['fuel_type'] ?? '';
    $status = $_POST['status'] ?? 'active'; // Default to 'active' if no status is selected

    // Validate required fields
    if (empty($registration_no) || empty($model) || empty($make) || empty($year) || empty($vin) || empty($mileage) || empty($fuel_type)) {
        $_SESSION['message'] = "All fields are required.";
        header("Location: admin_dashboard.php");
        exit();
    }

    // Validate registration number format
    if (!isValidRegistrationNo($registration_no)) {
        $_SESSION['message'] = "Invalid registration number format. Use format like KDQ 111T.";
        header("Location: admin_dashboard.php");
        exit();
    }

    // Validate VIN
    if (!isValidVin($vin)) {
        $_SESSION['message'] = "Invalid VIN. VIN must be exactly 17 characters long and contain only allowed characters.";
        header("Location: admin_dashboard.php");
        exit();
    }

    // Validate year
    if (!isValidYear($year)) {
        $_SESSION['message'] = "Invalid year. Year must be a valid 4-digit number and not in the future.";
        header("Location: admin_dashboard.php");
        exit();
    }

    // Check if the registration number or VIN already exists in the database
    $check_sql = "SELECT * FROM vehicles WHERE registration_no = ? OR vin = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $registration_no, $vin);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['message2'] = "The registration number or VIN already exists.";
        $check_stmt->close();
        header("Location: admin_dashboard.php");
        exit();
    }

    $check_stmt->close();

    // Insert vehicle data into the database
    $sql = "INSERT INTO vehicles (registration_no, model, make, year, vin, mileage, fuel_type, status, assigned_driver, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, NOW(), NOW())";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Bind parameters
        $stmt->bind_param("sssissss", $registration_no, $model, $make, $year, $vin, $mileage, $fuel_type, $status);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Vehicle added successfully.";
            $autoLogger->logVehicleCreation($_SESSION['user_id'], $stmt->insert_id, $registration_no);
        } else {
            $_SESSION['message'] = "Error: " . $stmt->error;
            $autoLogger->logError($_SESSION['user_id'], "Failed to add vehicle: " . $_SESSION['message']);
        }

        $stmt->close();
    } 

    $conn->close();

    // Redirect back to the main page
    header("Location: admin_dashboard.php");
    exit();
}