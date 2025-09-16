<?php
session_start();
require_once "db_connect.php"; 
require_once 'utils/session_handler.php';

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Check if the user has the role of 'driver'
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
    header("Location: unauthorized.php");
    exit();
}

// Check session timeout
checkSessionTimeout();

// Get the logged-in driver's ID
$driver_id = $_SESSION['user_id']; 

// Fetch Driver's Name Securely
$query = "SELECT name FROM Users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();
$driver_data = $result->fetch_assoc();
$driver_name = $driver_data ? htmlspecialchars($driver_data['name']) : "Driver"; 
$stmt->close();

// Fetch assigned vehicle details
$sql_vehicle = "SELECT * FROM Vehicles WHERE assigned_driver = ?";
$stmt_vehicle = $conn->prepare($sql_vehicle);
$stmt_vehicle->bind_param("i", $driver_id);
$stmt_vehicle->execute();
$result_vehicle = $stmt_vehicle->get_result();
$vehicle = $result_vehicle->fetch_assoc();
$stmt_vehicle->close();

// Default vehicle details if none assigned
if (!$vehicle) {
    $vehicle = [
        'vehicle_id' => null,
        'registration_no' => 'No vehicle assigned',
        'make' => '-',
        'model' => '-',
        'year' => '-',
        'vin' => '-',
        'mileage' => '-',
        'fuel_type' => '-'
    ];
}

$_SESSION['vehicle_id'] = $vehicle['vehicle_id'] ?? null;

// Fetch service history only if the driver has a vehicle
$service_history = [];
if (!empty($vehicle['vehicle_id'])) {
    $sql_service = "SELECT sh.*, mt.task_name, sc.service_center_name 
                    FROM Service_History sh
                    JOIN Maintenance_Tasks mt ON sh.task_id = mt.task_id
                    JOIN Service_Centers sc ON sh.service_center_id = sc.service_center_id
                    WHERE sh.vehicle_id = ?";
    $stmt_service = $conn->prepare($sql_service);
    $stmt_service->bind_param("i", $vehicle['vehicle_id']);
    $stmt_service->execute();
    $result_service = $stmt_service->get_result();
    $service_history = $result_service->fetch_all(MYSQLI_ASSOC);
    $stmt_service->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            display: flex;
            min-height: 100vh;
            background-color: #F4F6F7;
        }

        .sidebar {
            background-color: #2C3E50;
            color: #fff;
            width: 250px;
            padding: 20px;
            height: 100vh;
            position: fixed;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }

        .sidebar ul li {
            position: relative;
        }

        .sidebar ul li a {
            color: #fff;
            text-decoration: none;
            display: block;
            padding: 10px;
            font-size: 16px;
            transition: background 0.3s ease;
        }

        .sidebar ul li a:hover {
            background-color: #1A252F;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            flex-grow: 1;
        }

        header h1 {
            font-size: 28px;
            margin-bottom: 20px;
        }

        /* Styling for the vehicle details section */
        .vehicle-details, .service-history {
            background-color: #FFFFFF;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .vehicle-details h2, .service-history h2 {
            color: #2C3E50;
            margin-bottom: 15px;
            border-bottom: 2px solid #3498DB;
            padding-bottom: 5px;
        }

        .vehicle-details p {
            font-size: 16px;
            color: #34495E;
            margin-bottom: 8px;
        }

        /* Styling for the service history table */
        .service-history table {
            width: 100%;
            border-collapse: collapse;
            background-color: #FFFFFF;
            border-radius: 8px;
            overflow: hidden;
        }

        .service-history th, .service-history td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        .service-history th {
            background-color: #3498DB;
            color: white;
        }

        .service-history tr:nth-child(even) {
            background-color: #F2F3F4;
        }

        .service-history tr:hover {
            background-color: #D5DBDB;
        }

        /* Logout button */
        .logout {
            color: #fff;
            background-color: #E74C3C;
            border: none;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s ease;
        }

        .logout:hover {
            background-color: #C0392B;
        }

        .logout {
            color: #fff;
            background-color: #E74C3C;
            border: none;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s ease;
        }

        .logout:hover {
            background-color: #C0392B;
        }
    
        .logo {
    width: 120px; /* Adjust size */
    height: 120px;
    background-color: #fff;
    border-radius: 50%; /* Make it circular */
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden; /* Ensures the image stays within the boundary */
    margin: 0 auto 20px auto; /* Centering */
}

.logo img {
    width: 100%; /* Ensure it scales properly */
    height: auto;
    object-fit: cover; /* Crop to fit */
    display: block;
}
.welcome-message { 
    font-size: 20px;
    margin-bottom: 20px; 
    font-weight: bold;
    color: #2C3E50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.welcome-message i {
    color: #3498DB;
}

/* Simple Notification Styles */
.notification-container {
    position: relative;
}

.notification-bell {
    position: relative;
    cursor: pointer;
    font-size: 24px;
    color: #2C3E50;
    padding: 10px;
    transition: color 0.3s ease;
}

.notification-bell:hover {
    color: #3498DB;
}

.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    background-color: #E74C3C;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 12px;
    display: none;
    min-width: 18px;
    text-align: center;
}

/* Notification Modal Styles */
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.modal-header h2 {
    margin: 0;
    color: #2C3E50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-header h2 i {
    color: #3498DB;
}

.modal-body {
    padding: 20px;
    max-height: 400px;
    overflow-y: auto;
}

.notification-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    border: 1px solid #eee;
    border-radius: 8px;
    margin-bottom: 15px;
    background-color: #f8f9fa;
    transition: background-color 0.3s ease;
}

.notification-item:hover {
    background-color: #e9ecef;
}

.notification-icon {
    font-size: 24px;
    color: #3498DB;
    display: flex;
    align-items: center;
}

.notification-content h4 {
    margin: 0 0 10px 0;
    color: #2C3E50;
    font-size: 16px;
}

.notification-content p {
    margin: 5px 0;
    color: #555;
    font-size: 14px;
}

.notification-time {
    color: #7f8c8d;
    font-size: 12px;
    font-style: italic;
}

.no-notifications {
    text-align: center;
    padding: 40px 20px;
    color: #7f8c8d;
}

.no-notifications i {
    font-size: 48px;
    color: #27ae60;
    margin-bottom: 15px;
}

.error-message {
    text-align: center;
    padding: 20px;
    color: #e74c3c;
    background-color: #fdf2f2;
    border-radius: 8px;
}

    </style>



<script>
    function openTripLogsModal() {
        document.getElementById("tripLogsModal").style.display = "block";
    }

    function closeTripLogsModal() {
        document.getElementById("tripLogsModal").style.display = "none";
    }

    // Close modal if clicked outside
    window.onclick = function(event) {
        var modal = document.getElementById("tripLogsModal");
        if (event.target == modal) {
            closeTripLogsModal();
        }
    };
</script>
<script>
$(document).ready(function() {
    $("#openModal").click(function() {
        $("#scheduleModal").fadeIn();
    });

    $(".close").click(function() {
        $(".modal").fadeOut();
    });
});
</script>

<script>
// Session timeout warning
function checkSession() {
    var remainingTime = <?php echo getRemainingSessionTime(); ?>;
    if (remainingTime <= 5) {
        alert('Your session will expire in ' + remainingTime + ' minutes. Please save your work.');
    }
}

// Check session every minute
setInterval(checkSession, 60000);
</script>

</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
    <div class="logo">
            <img src="logo.png" alt="Company Logo">
        </div>
        <h2>Mutai Enterprises Limited</h2>
        <ul>
        <li><a href="#overview">Dashboard Overview</a></li>
            
            <li><a href="book_schedule.php">Maintenance Booking</a></li>
            <li><a href="manage_bookings.php">Manage Bookings</a></li>
            
            <li><a href="#"  onclick="openTripLogsModal()">VehicleTrip Logs</a></li>
            <li><a href="#" onclick="openModal('changePasswordModal')">Change Password</a></li>
            <li><a href="logout.php" class="logout">Log Out</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <header>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    
                    <p class="welcome-message">
            <i class="fas fa-user-circle"></i>    
                Welcome Driver, <?php echo $driver_name; ?>!
            </p>
            <h1>Driver Dashboard Overview</h1>
                </div>
                <div style="display: flex; gap: 20px; align-items: center;">
                    <button class="action-btn has-badge" style="background: #4a90e2; position: relative;" onclick="openRepliesModal()">
                        <i class="fas fa-envelope-open-text"></i> View Admin Replies
                        <span class="reply-badge" id="replyBadge" style="display:none; position:absolute; top:-8px; right:-8px; background:#e74c3c; color:#fff; border-radius:50%; padding:4px 8px; font-size:12px;">0</span>
                    </button>
                    <button class="action-btn" onclick="openMessageModal()" style="background: #4a90e2;">
                        <i class="fas fa-envelope"></i> Send Message
                    </button>
                    <div class="notification-container">
                        <div class="notification-bell" onclick="toggleMaintenanceNotifications()">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge" id="maintenanceNotificationCount">0</span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Stats Section -->
        <section class="dashboard-stats">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-car"></i>
                </div>
                <div class="stats-info">
                    <h3>Vehicle Status</h3>
                    <p class="stats-value"><?php echo $vehicle['status'] ?? 'Not Assigned'; ?></p>
                </div>
            </div>
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-tachometer-alt"></i>
                </div>
                <div class="stats-info">
                    <h3>Current Mileage</h3>
                    <p class="stats-value"><?php echo $vehicle['mileage'] ?? '0'; ?> km</p>
                </div>
            </div>
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stats-info">
                    <h3>Last Service Date</h3>
                    <p class="stats-value"><?php echo !empty($service_history) ? date('d M Y', strtotime(end($service_history)['date_of_service'])) : 'Not Scheduled'; ?></p>
                </div>
            </div>
        </section>

        <!-- Vehicle Details Section -->
        <section class="vehicle-details">
            <div class="section-header">
                <h2>My Vehicle Details</h2>
                <button class="action-btn" onclick="openTripLogsModal()">
                    <i class="fas fa-plus"></i> Add Trip Log
                </button>
            </div>
            <?php if ($vehicle['registration_no'] !== 'No vehicle assigned'): ?>
                <div class="vehicle-info-grid">
                    <div class="info-card">
                        <h4>Registration Number</h4>
                        <p><?= htmlspecialchars($vehicle['registration_no']) ?></p>
                    </div>
                    <div class="info-card">
                        <h4>Make & Model</h4>
                        <p><?= htmlspecialchars($vehicle['make']) ?> <?= htmlspecialchars($vehicle['model']) ?></p>
                    </div>
                    <div class="info-card">
                        <h4>Year</h4>
                        <p><?= htmlspecialchars($vehicle['year']) ?></p>
                    </div>
                    <div class="info-card">
                        <h4>VIN</h4>
                        <p><?= htmlspecialchars($vehicle['vin']) ?></p>
                    </div>
                    <div class="info-card">
                        <h4>Fuel Type</h4>
                        <p><?= htmlspecialchars($vehicle['fuel_type']) ?></p>
                    </div>
                    <div class="info-card">
                        <h4>Status</h4>
                        <p class="status-badge <?= strtolower($vehicle['status']) ?>"><?= htmlspecialchars($vehicle['status']) ?></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-vehicle">
                    <i class="fas fa-car-crash"></i>
                    <p>No vehicle assigned to you yet.</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Service History Section -->
        <section class="service-history">
            <div class="section-header">
                <h2>Service History</h2>
                <button class="action-btn" onclick="window.location.href='book_schedule.php'">
                    <i class="fas fa-calendar-plus"></i> Book Service
                </button>
            </div>
            <?php if (!empty($service_history)): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Service Task</th>
                                <th>Mileage</th>
                                <th>Service Center</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($service_history as $service): ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($service['date_of_service'])) ?></td>
                                    <td><?= htmlspecialchars($service['task_name']) ?></td>
                                    <td><?= htmlspecialchars($service['mileage_at_service']) ?> km</td>
                                    <td><?= htmlspecialchars($service['service_center_name']) ?></td>
                                    <td><?= htmlspecialchars($service['service_notes']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-service-history">
                    <i class="fas fa-history"></i>
                    <p>No service history available.</p>
                </div>
            <?php endif; ?>
        </section>

        <style>
            /* Stats Section */
            .dashboard-stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }

            .stats-card {
                background-color: #3498DB;
                color: #fff;
                padding: 20px;
                border-radius: 8px;
                width: 100%;
                text-align: center;
                transition: transform 0.3s ease, box-shadow 0.3s ease;
                position: relative;
                overflow: hidden;
            }

            .stats-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            }

            .stats-icon {
                position: absolute;
                right: 10px;
                bottom: 10px;
                font-size: 2rem;
                opacity: 0.2;
            }

            .stats-info h3 {
                font-size: 1.2rem;
                margin-bottom: 10px;
                color: #fff;
            }

            .stats-value {
                font-size: 2rem;
                font-weight: bold;
                margin: 0;
                color: #fff;
            }

            /* Vehicle Details Section */
            .vehicle-info-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }

            .info-card {
                background: white;
                border-radius: 8px;
                padding: 15px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .info-card h4 {
                margin: 0 0 10px;
                font-size: 14px;
                color: #666;
            }

            .info-card p {
                margin: 0;
                font-size: 16px;
                color: #333;
            }

            /* Service History Section */
            .table-container {
                overflow-x: auto;
                margin-top: 20px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .service-history table {
                width: 100%;
                border-collapse: collapse;
            }

            .service-history th {
                background: #f8f9fa;
                padding: 12px;
                text-align: left;
                font-weight: 600;
                color: #2c3e50;
                border-bottom: 2px solid #e9ecef;
            }

            .service-history td {
                padding: 12px;
                border-bottom: 1px solid #e9ecef;
            }

            .service-history tr:hover {
                background: #f8f9fa;
            }

            /* Status Badges */
            .status-badge {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 500;
            }

            .status-badge.active {
                background: #e8f5e9;
                color: #2e7d32;
            }

            .status-badge.inactive {
                background: #ffebee;
                color: #c62828;
            }

            .status-badge.maintenance {
                background: #fff3e0;
                color: #ef6c00;
            }

            /* Empty States */
            .no-vehicle, .no-service-history {
                text-align: center;
                padding: 40px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .no-vehicle i, .no-service-history i {
                font-size: 48px;
                color: #bdc3c7;
                margin-bottom: 20px;
            }

            .no-vehicle p, .no-service-history p {
                color: #666;
                font-size: 16px;
            }

            /* Section Headers */
            .section-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }

            .section-header h2 {
                margin: 0;
                color: #2c3e50;
                font-size: 24px;
            }

            .action-btn {
                background: #1976d2;
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 4px;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 14px;
                transition: background 0.3s;
            }

            .action-btn:hover {
                background: #1565c0;
            }

            .action-btn i {
                font-size: 16px;
            }
        </style>
    </div>




<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- Change Password Modal -->
<div id="changePasswordModal" class="modalc" onclick="closeModal(event)">
    <div class="modal-contentc" onclick="event.stopPropagation()">
        <h2>Change Your Password</h2>
        <form id="changePasswordForm">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <div class="password-container">
                    <input type="password" id="current_password" name="current_password" required placeholder="Current Password">
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('current_password')"></i>
                </div>
            </div>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <div class="password-container">
                    <input type="password" id="new_password" name="new_password" required placeholder="New Password">
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('new_password')"></i>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-container">
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm Password">
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password')"></i>
                </div>
            </div>
            <span id="passwordError" class="error-message"></span>
            <div class="button-group">
                <button type="submit" class="submit-btn">Change Password</button>
                <button type="button" class="cancel-btn" onclick="closeModalDirect()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- CSS for Modal -->
<style>
.modalc {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    justify-content: center;
    align-items: center;
}

.modal-contentc {
    background-color: #fff;
    padding: 30px;
    border-radius: 10px;
    width: 400px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.modal-contentc h2 {
    margin: 0 0 25px 0;
    font-size: 24px;
    color: #2c3e50;
    text-align: center;
    font-weight: 600;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #34495e;
    font-weight: 500;
    font-size: 14px;
}

.password-container {
    position: relative;
    display: flex;
    align-items: center;
}

.password-container input {
    width: 100%;
    padding: 12px;
    padding-right: 40px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.password-container input:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
}

.toggle-password {
    position: absolute;
    right: 12px;
    cursor: pointer;
    color: #666;
    transition: color 0.3s ease;
}

.toggle-password:hover {
    color: #333;
}

.button-group {
    display: flex;
    gap: 10px;
    margin-top: 25px;
}

.submit-btn, .cancel-btn {
    flex: 1;
    padding: 12px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.submit-btn {
    background-color: #3498db;
    color: white;
}

.submit-btn:hover {
    background-color: #2980b9;
}

.cancel-btn {
    background-color: #e74c3c;
    color: white;
}

.cancel-btn:hover {
    background-color: #c0392b;
}

.error-message {
    color: #e74c3c;
    font-size: 13px;
    margin-top: 5px;
    display: block;
    text-align: center;
}

/* Add animation for modal */
@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-contentc {
    animation: modalFadeIn 0.3s ease-out;
}
</style>

<!-- JavaScript for Modal and Form Submission -->
<script>
document.getElementById("changePasswordForm").addEventListener("submit", function(event) {
    event.preventDefault();

    let currentPassword = document.getElementById("current_password").value;
    let newPassword = document.getElementById("new_password").value;
    let confirmPassword = document.getElementById("confirm_password").value;
    let passwordError = document.getElementById("passwordError");

    // Reset error message
    passwordError.textContent = "";

    // Validate password match
    if (newPassword !== confirmPassword) {
        Swal.fire({
            icon: 'error',
            title: 'Password Mismatch',
            text: 'New password and confirm password do not match.',
            confirmButtonColor: '#e74c3c'
        });
        return;
    }

    // Send data to PHP backend
    let formData = new FormData();
    formData.append("current_password", currentPassword);
    formData.append("new_password", newPassword);
    formData.append("confirm_password", confirmPassword);

    fetch("change_password_modal.php", {
        method: "POST",
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Your password has been changed successfully.',
                confirmButtonColor: '#3498db'
            }).then((result) => {
                if (result.isConfirmed) {
                    closeModalDirect();
                }
            });
        } else {
            // Handle different types of errors with specific messages
            let errorTitle = 'Error';
            let errorIcon = 'error';
            
            if (data.message.includes('Current password is incorrect')) {
                errorTitle = 'Invalid Current Password';
            } else if (data.message.includes('New password cannot be the same')) {
                errorTitle = 'Invalid New Password';
            } else if (data.message.includes('Password must be at least 8 characters')) {
                errorTitle = 'Password Requirements Not Met';
            }

            Swal.fire({
                icon: errorIcon,
                title: errorTitle,
                text: data.message,
                confirmButtonColor: '#e74c3c'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'System Error',
            text: 'An unexpected error occurred. Please try again.',
            confirmButtonColor: '#e74c3c'
        });
    });
});

function openModal() {
    document.getElementById("changePasswordModal").style.display = "flex";
}

function closeModal(event) {
    // If the event is passed and it's not from the cancel button, ensure it doesn't close unexpectedly
    if (event && event.target.id !== "changePasswordModal") return;
    document.getElementById("changePasswordModal").style.display = "none";
}

function closeModalDirect() {
    document.getElementById("changePasswordModal").style.display = "none";
    // Clear the form
    document.getElementById("changePasswordForm").reset();
}

// Ensure clicking outside the modal closes it
document.addEventListener("click", function(event) {
    let modal = document.getElementById("changePasswordModal");
    if (event.target === modal) {
        closeModal();
    }
});

function togglePassword(inputId) {
    let input = document.getElementById(inputId);
    let icon = input.nextElementSibling; // Get the adjacent icon

    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}
</script>

<!-- Add SweetAlert2 CSS and JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

<style>
/* Add styles for password requirements tooltip */
.password-container {
    position: relative;
}

.password-requirements {
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    background: white;
    padding: 10px;
    border-radius: 4px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-top: 5px;
    font-size: 12px;
    color: #666;
    display: none;
    z-index: 1000;
}

.password-container input:focus + .password-requirements {
    display: block;
}

.requirement {
    margin: 5px 0;
    display: flex;
    align-items: center;
    gap: 5px;
}

.requirement i {
    font-size: 12px;
}

.requirement.met {
    color: #27ae60;
}

.requirement.unmet {
    color: #e74c3c;
}
</style>

<?php include 'trip_logs_modal.php'; ?>
<?php include 'driver_schedule.php'; ?>
<?php include 'message_modal.php'; ?>
<?php include 'view_replies_modal.php'; ?>

<!-- Maintenance Notification Modal -->
<div id="maintenanceNotificationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-bell"></i> Maintenance Reminders</h2>
            <span class="close" onclick="closeMaintenanceNotificationModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div id="maintenanceNotificationList">
                <!-- Notifications will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- My Bookings Modal -->
<div id="myBookingsModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('myBookingsModal')">&times;</span>
        <h2>My Maintenance Bookings</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Task</th>
                        <th>Service Center</th>
                        <th>Time Slot</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $user_id = $_SESSION['user_id'];
                    $query = "SELECT ms.*, mt.task_name, sc.service_center_name 
                                             FROM maintenance_schedule ms
                JOIN maintenance_tasks mt ON ms.task_id = mt.task_id
                JOIN service_centers sc ON ms.service_center_id = sc.service_center_id
                JOIN vehicles v ON ms.vehicle_id = v.vehicle_id
                             WHERE v.assigned_driver = ?
                             ORDER BY ms.schedule_date DESC, ms.schedule_start_time DESC";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($row = $result->fetch_assoc()):
                        $status_class = strtolower($row['status']);
                    ?>
                    <tr>
                        <td><?php echo date('d M Y', strtotime($row['schedule_date'])); ?></td>
                        <td><?php echo htmlspecialchars($row['task_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['service_center_name']); ?></td>
                        <td><?php echo date('h:i A', strtotime($row['schedule_start_time'])) . ' - ' . 
                                     date('h:i A', strtotime($row['schedule_end_time'])); ?></td>
                        <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $row['status']; ?></span></td>
                        <td>
                            <?php if ($row['status'] === 'Scheduled'): ?>
                            <button class="edit-btn" onclick="openEditModal(<?php echo $row['schedule_id']; ?>)">Reschedule</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function openEditModal(scheduleId) {
    window.location.href = `edit_maintenance_modal.php?schedule_id=${scheduleId}`;
}
</script>

<script>
function loadReplyBadge() {
    fetch('get_unread_replies_count.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('replyBadge');
            if (data.status === 'success' && data.unread_count > 0) {
                badge.style.display = 'inline-block';
                badge.textContent = data.unread_count;
            } else {
                badge.style.display = 'none';
            }
        });
}
document.addEventListener('DOMContentLoaded', function() {
    loadReplyBadge();
    setInterval(loadReplyBadge, 30000);
});
</script>

<script>
// Maintenance Notification Functions
function toggleMaintenanceNotifications() {
    const modal = document.getElementById('maintenanceNotificationModal');
    if (modal.style.display === 'block') {
        closeMaintenanceNotificationModal();
    } else {
        openMaintenanceNotificationModal();
    }
}

function openMaintenanceNotificationModal() {
    const modal = document.getElementById('maintenanceNotificationModal');
    modal.style.display = 'block';
    loadMaintenanceNotifications();
}

function closeMaintenanceNotificationModal() {
    const modal = document.getElementById('maintenanceNotificationModal');
    modal.style.display = 'none';
}

function loadMaintenanceNotifications() {
    fetch('get_notifications.php')
        .then(response => response.json())
        .then(data => {
            const notificationList = document.getElementById('maintenanceNotificationList');
            const notificationCount = document.getElementById('maintenanceNotificationCount');
            
            if (data.notifications && data.notifications.length > 0) {
                notificationCount.style.display = 'block';
                notificationCount.textContent = data.notifications.length;
                
                let html = '';
                data.notifications.forEach(notification => {
                    html += `
                        <div class="notification-item" onclick="handleNotificationClick(${notification.id}, '${notification.type}', ${notification.related_id})">
                            <div class="notification-icon">
                                <i class="fas fa-${getNotificationIcon(notification.type)}"></i>
                            </div>
                            <div class="notification-content">
                                <h4>${notification.title}</h4>
                                <p>${notification.message}</p>
                                <small class="notification-time">${notification.created_at}</small>
                            </div>
                        </div>
                    `;
                });
                notificationList.innerHTML = html;
            } else {
                notificationCount.style.display = 'none';
                notificationList.innerHTML = '<div class="no-notifications"><i class="fas fa-check-circle"></i><p>No maintenance reminders at this time.</p></div>';
            }
        })
        .catch(error => {
            console.error('Error loading maintenance notifications:', error);
            document.getElementById('maintenanceNotificationList').innerHTML = '<div class="error-message"><p>Error loading notifications. Please try again.</p></div>';
        });
}

function getNotificationIcon(type) {
    switch(type) {
        case 'maintenance':
            return 'tools';
        case 'message':
            return 'envelope';
        case 'system':
            return 'info-circle';
        default:
            return 'bell';
    }
}

function handleNotificationClick(notificationId, type, relatedId) {
    // Mark notification as read
    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            notification_id: notificationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the clicked notification from the display immediately
            const notificationElement = document.querySelector(`[onclick*="${notificationId}"]`);
            if (notificationElement) {
                notificationElement.remove();
            }
            
            // Update the notification count
            const notificationCount = document.getElementById('maintenanceNotificationCount');
            const currentCount = parseInt(notificationCount.textContent) || 0;
            const newCount = Math.max(0, currentCount - 1);
            
            if (newCount === 0) {
                notificationCount.style.display = 'none';
                // If no more notifications, show the "no notifications" message
                const notificationList = document.getElementById('maintenanceNotificationList');
                notificationList.innerHTML = '<div class="no-notifications"><i class="fas fa-check-circle"></i><p>No maintenance reminders at this time.</p></div>';
            } else {
                notificationCount.textContent = newCount;
            }
            
            // Handle different notification types
            if (type === 'maintenance' && relatedId) {
                // For maintenance notifications, you could redirect to maintenance details
                // or perform other actions here
            }
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

// Load notifications when page loads
document.addEventListener('DOMContentLoaded', () => {
    loadMaintenanceNotifications();
    // Refresh notifications every 5 minutes
    setInterval(loadMaintenanceNotifications, 300000);
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('maintenanceNotificationModal');
    if (event.target === modal) {
        closeMaintenanceNotificationModal();
    }
}
</script>

</body>
</html>
