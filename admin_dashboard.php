<?php
session_start();
require_once 'utils/session_handler.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Check session timeout
checkSessionTimeout();

include 'db_connect.php';

// Fetch Admin's Name Securely
$admin_id = $_SESSION['user_id'];
$query = "SELECT name FROM Users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$admin_name = $admin ? htmlspecialchars($admin['name']) : "Admin"; 
$stmt->close();

// Fetch Total Active Trucks
$query = "SELECT COUNT(*) AS total_trucks FROM Vehicles WHERE status = 'active'";
$stmt = $conn->prepare($query);
$stmt->execute();
$stmt->bind_result($totalTrucks);
$stmt->fetch();
$stmt->close();

// Fetch Trucks Due for Maintenance (within next 7 days)
$query = "SELECT COUNT(*) AS maintenance_due 
          FROM Maintenance_Schedule m
          JOIN Vehicles v ON m.vehicle_id = v.vehicle_id
          WHERE m.status = 'Scheduled' 
          AND m.schedule_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
$stmt = $conn->prepare($query);
$stmt->execute();
$stmt->bind_result($maintenanceDue);
$stmt->fetch();
$stmt->close();

// Fetch Active Drivers
$query = "SELECT COUNT(*) AS active_drivers FROM Users WHERE role = 'driver' AND status = 'active'";
$stmt = $conn->prepare($query);
$stmt->execute();
$stmt->bind_result($activeDrivers);
$stmt->fetch();
$stmt->close();

// Fetch Maintenance Schedule Securely
$query = "SELECT v.registration_no, t.task_name, m.schedule_date, m.status 
          FROM Maintenance_Schedule m
          JOIN Vehicles v ON m.vehicle_id = v.vehicle_id
          JOIN Maintenance_Tasks t ON m.task_id = t.task_id
          ORDER BY m.schedule_date DESC
          LIMIT 6";

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$maintenanceSchedules = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
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
            background-color: #f4f4f4;
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

        /* Submenu styling */
        .sidebar ul ul {
            display: none;
            background-color: #34495E;
            padding-left: 10px;
            position: absolute;
            left: 100%;
            top: 0;
            min-width: 200px;
        }

        /* Show submenu on hover over parent or submenu */
        .sidebar ul li:hover > ul,
        .sidebar ul ul:hover {
            display: block;
        }

        .sidebar ul ul li a {
            padding: 10px;
            white-space: nowrap;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            flex-grow: 1;
        }
        .welcome-message { 
            font-size: 20px;
             margin-bottom: 20px; 
             font-weight: bold;
             }

        header h1 {
            font-size: 28px;
            margin-bottom: 20px;
        }

        .dashboard-overview {
            display: flex;
            justify-content: space-between;
        }

        .stats-card {
            background-color: #3498DB;
            color: #fff;
            padding: 20px;
            border-radius: 8px;
            width: 30%;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .stats-card h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .stats-card p {
            font-size: 2rem;
            font-weight: bold;
            margin: 0;
        }

        .stats-card i {
            position: absolute;
            right: 10px;
            bottom: 10px;
            font-size: 2rem;
            opacity: 0.2;
        }

        .maintenance-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .maintenance-table th, .maintenance-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .maintenance-table th {
            background-color: #2C3E50;
            color: white;
            font-weight: 500;
        }

        .maintenance-table tr:hover {
            background-color: #f5f5f5;
        }

        .status-scheduled {
            color: #f39c12;
            font-weight: 500;
        }

        .status-completed {
            color: #27ae60;
            font-weight: 500;
        }

        .status-cancelled {
            color: #e74c3c;
            font-weight: 500;
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

        .dashboard-overview {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 30px;
        }

        .section-title {
            color: #2C3E50;
            margin: 30px 0 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #3498DB;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .show-more-btn {
            background: #3498DB;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }

        .show-more-btn:hover {
            background: #2980B9;
        }

        .logout {
            color: #fff;
            background-color: #E74C3C;
            border: none;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            width: 100%;
        }

        /* Change Password Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
            position: relative;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .submit-btn {
            background-color: #3498DB;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        .submit-btn:hover {
            background-color: #2980B9;
        }

        .error-message {
            color: #E74C3C;
            margin-top: 10px;
            display: none;
        }

        .success-message {
            color: #27AE60;
            margin-top: 10px;
            display: none;
        }

        .logo {
    width: 120px; 
    height: 120px;
    background-color: #fff;
    border-radius: 50%; 
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden; 
    margin: 0 auto 20px auto;
}

.logo img {
    width: 100%; 
    height: auto;
    object-fit: cover; 
    display: block;
}

        /* Message Button Styles */
        .action-btn.large {
            padding: 12px 24px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .action-btn.large i {
            font-size: 20px;
        }

        .action-btn.has-badge {
            position: relative;
        }

        .message-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #E74C3C;
            color: white;
            border-radius: 50%;
            padding: 4px 8px;
            font-size: 12px;
            display: none;
        }

    </style>
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
            <li>
                <a href="#user-management">User Management</a>
                <ul>
                    <li><a href="#" id="addUserBtn">Add Users</a></li>
                    <li><a href="manage_users.php">Manage Users</a></li>
                </ul>
            </li>
            <li>
                <a href="#fleet">Manage Fleet</a>
                <ul>
                    <li><a href="#" id="addVehicleBtn">Add New Vehicle</a></li>
                    <li><a href="manage_vehicles.php">Manage Vehicles</a></li>
                </ul>
            </li>
           
            <li>
                <a href="#Garage">Garage Management</a>
                <ul>
                    <li><a href="#"  id="addMaintenanceBtn"> Add Maintenance Task</a></li>
                    <li><a href="manage_maintenance_tasks.php">Manage Maintenance Task</a></li>
                    <li><a href="#"  id="addServiceCenterBtn">Add Service Center</a></li>
                    <li><a href="manage_service_center.php">Manage Service Center</a></li>
                    <li><a href="manage_service_center_mechanics.php">Manage Service Center Mechanics</a></li>
                </ul>
            </li>
            <li>
                <a href="maintenance_schedules.php">Maintenance Schedules</a>
                
            </li>
            <li>
                <a href="#" onclick="openLogsModal(); return false;">View Logs</a>
                
            </li>
          <li>
    <a href="#" onclick="openChangePasswordModal(); return false;">Change Password</a>
    
</li>


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
                        Welcome Admin, <?php echo $admin_name; ?>!
                    </p>
                    <h1>Admin Dashboard Overview</h1>
                </div>
                <div style="display: flex; gap: 20px; align-items: center;">
                    <button class="action-btn large has-badge" onclick="window.location.href='admin_messages.php'" style="background: #4a90e2;">
                        <i class="fas fa-envelope"></i> View Messages
                        <span class="message-badge" id="messageBadge">0</span>
                    </button>
                </div>
            </div>
        </header>

        <section class="dashboard-overview">
            <div class="stats-card">
                <i class="fas fa-car"></i>
                <h3>Active Vehicles</h3>
                <p><?php echo $totalTrucks; ?></p>
            </div>
            <div class="stats-card">
                <i class="fas fa-tools"></i>
                <h3>Maintenance Due (7 Days)</h3>
                <p><?php echo $maintenanceDue; ?> Trucks</p>
            </div>
            <div class="stats-card">
                <i class="fas fa-user-tie"></i>
                <h3>Active Drivers</h3>
                <p><?php echo $activeDrivers; ?> Drivers</p>
            </div>
        </section>

        <section>
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-calendar-alt"></i>
                    Maintenance Schedule
                </h2>
                <a href="maintenance_schedules.php" class="show-more-btn">View All Schedules</a>
            </div>
            <table class="maintenance-table">
                <thead>
                    <tr>
                        <th>Registration Number</th>
                        <th>Maintenance Type</th>
                        <th>Scheduled Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($maintenanceSchedules) > 0): ?>
                        <?php foreach ($maintenanceSchedules as $schedule): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($schedule['registration_no']); ?></td>
                                <td><?php echo htmlspecialchars($schedule['task_name']); ?></td>
                                <td><?php echo htmlspecialchars($schedule['schedule_date']); ?></td>
                                <td class="status-<?php echo strtolower($schedule['status']); ?>">
                                    <?php echo htmlspecialchars($schedule['status']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No maintenance schedules found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>

    
  <!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- Change Password Modal -->
<div id="changePasswordModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeChangePasswordModal()">&times;</span>
        <h2>Change Password</h2>
        <form id="changePasswordForm">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <div class="password-container">
                    <input type="password" id="current_password" name="current_password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('current_password')"></i>
                </div>
            </div>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <div class="password-container">
                    <input type="password" id="new_password" name="new_password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('new_password')"></i>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="password-container">
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password')"></i>
                </div>
            </div>
            <button type="submit" class="submit-btn">Change Password</button>
            <div id="errorMessage" class="error-message"></div>
            <div id="successMessage" class="success-message"></div>
        </form>
    </div>
</div>

<style>
    /* Update Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 15% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 500px;
        border-radius: 8px;
        position: relative;
    }

    .password-container {
        position: relative;
        display: flex;
        align-items: center;
    }

    .password-container input {
        width: 100%;
        padding: 8px;
        padding-right: 35px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .toggle-password {
        position: absolute;
        right: 10px;
        cursor: pointer;
        color: #666;
    }

    .toggle-password:hover {
        color: #333;
    }
</style>

<script>
    // Password visibility toggle function
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.nextElementSibling;
        
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

    // Modal functions
    function openChangePasswordModal() {
        document.getElementById('changePasswordModal').style.display = 'block';
        document.getElementById('changePasswordForm').reset();
        document.getElementById('errorMessage').style.display = 'none';
        document.getElementById('successMessage').style.display = 'none';
    }

    function closeChangePasswordModal() {
        document.getElementById('changePasswordModal').style.display = 'none';
        document.getElementById('changePasswordForm').reset();
        document.getElementById('errorMessage').style.display = 'none';
        document.getElementById('successMessage').style.display = 'none';
    }

    // Form submission handler
    document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('change_password_modal.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('errorMessage').style.display = 'none';
                document.getElementById('successMessage').textContent = data.message;
                document.getElementById('successMessage').style.display = 'block';
                setTimeout(() => {
                    closeChangePasswordModal();
                }, 2000);
            } else {
                document.getElementById('successMessage').style.display = 'none';
                document.getElementById('errorMessage').textContent = data.message;
                document.getElementById('errorMessage').style.display = 'block';
            }
        })
        .catch(error => {
            document.getElementById('errorMessage').textContent = 'An error occurred. Please try again.';
            document.getElementById('errorMessage').style.display = 'block';
        });
    });

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('changePasswordModal');
        if (event.target === modal) {
            closeChangePasswordModal();
        }
    }

    // Function to load notifications
    function loadNotifications() {
        fetch('get_message_notifications.php')
            .then(response => response.json())
            .then(data => {
                const messageBadge = document.getElementById('messageBadge');
                
                if (data.unread_count > 0) {
                    messageBadge.style.display = 'block';
                    messageBadge.textContent = data.unread_count;
                } else {
                    messageBadge.style.display = 'none';
                }
            })
            .catch(error => console.error('Error loading notifications:', error));
    }

    // Load notifications when page loads
    document.addEventListener('DOMContentLoaded', () => {
        loadNotifications();
        // Refresh notifications every 30 seconds
        setInterval(loadNotifications, 30000);
    });
</script>

<!-- Include the Add User Modal -->
<?php include 'add_user_modal.php'; ?>

<!-- Include the Add Truck Modal -->
<?php include 'add_vehicle_modal.php'; ?>

<?php include 'add_maintenance_task_modal.php'; ?>

<?php include 'add_service_center_modal.php'; ?>

<!-- Include the logs modal at the end of the file -->
<?php include 'admin/view_logs_modal.php'; ?>



</body>
</html>