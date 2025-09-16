<?php
session_start();
require_once "db_connect.php"; 
require_once "mechanic_dashboard_functions.php";
require_once 'utils/session_handler.php';

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Check if the user has the role of 'mecahnic'
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mechanic') {
    header("Location: unauthorized.php");
    exit();
}

// Check session timeout
checkSessionTimeout();

// Get the logged-in driver's ID
$mechanic_id = $_SESSION['user_id']; 

// Fetch Mechanic's Name Securely
$query = "SELECT name FROM Users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $mechanic_id);
$stmt->execute();
$result = $stmt->get_result();
$mechanic_data = $result->fetch_assoc();
$mechanic_name = $mechanic_data ? htmlspecialchars($mechanic_data['name']) : "Mechanic"; 
$stmt->close();

// Fetch mechanic's service center
$query = "SELECT sc.service_center_name 
          FROM Service_Center_Mechanics scm 
          JOIN Service_Centers sc ON scm.service_center_id = sc.service_center_id 
          WHERE scm.mechanic_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $mechanic_id);
$stmt->execute();
$result = $stmt->get_result();
$service_center = $result->fetch_assoc();
$service_center_name = $service_center ? $service_center['service_center_name'] : 'Not Assigned';
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mechanic Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <!-- Add Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

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

        header h1 {
            font-size: 28px;
            margin-bottom: 20px;
        }

        .dashboard-overview {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin: 30px 0;
            padding: 0 20px;
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

        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin: 0;
            color: #fff;
        }

        .stats-change {
            font-size: 12px;
            margin: 0;
        }

        .stats-change.positive {
            color: #4caf50;
        }

        .stats-change.negative {
            color: #f44336;
        }

        .stats-change.neutral {
            color: #666;
        }

        .pending-maintenance {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-header h2 {
            margin: 0;
            color: #333;
        }

        .show-more-btn {
            background: #1976d2;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .show-more-btn:hover {
            background: #1565c0;
        }

        .maintenance-table {
            width: 100%;
            border-collapse: collapse;
        }

        .maintenance-table th,
        .maintenance-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .maintenance-table th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-badge.scheduled {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .status-badge.in-progress {
            background-color: #fff3e0;
            color: #f57c00;
        }

        .status-badge.completed {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .action-btn {
            background: #1976d2;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .action-btn:hover {
            background: #1565c0;
        }

        .table-container {
            max-height: 400px;
            overflow-y: auto;
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
    width: 100%; 
    height: auto;
    object-fit: cover; 
    display: block;
}
.welcome-message { 
            font-size: 20px;
             margin-bottom: 20px; 
             font-weight: bold;
             }

        .no-pending-maintenance {
            text-align: center;
            padding: 40px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }

        .no-pending-maintenance p {
            color: #666;
            font-size: 16px;
            font-style: italic;
             }

        /* Change Password Modal Styles */
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
            <li><a href="#" onclick="showAdmitModal(); return false;">Admit Vehicle</a></li>
            <li><a href="#" onclick="showCheckoutModal(); return false;">Checkout Vehicle</a></li>
            <li><a href="#" onclick="showServiceHistoryModal(); return false;">Service History</a></li>
            
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
                        Welcome Mechanic, <?php echo $mechanic_name; ?>!
                        <br>
                        <small style="color: #666;"> <?php echo htmlspecialchars($service_center_name); ?></small>
                    </p>
                    <h1>Mechanic Dashboard Overview</h1>
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
        <section class="dashboard-overview">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-wrench"></i>
                </div>
                <div class="stats-info">
                <h3>Today's Pending Jobs</h3>
                    <p class="stats-number"><?php echo getPendingJobs($conn, $mechanic_id); ?></p>
                </div>
            </div>
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stats-info">
                    <h3>Jobs In Progress</h3>
                    <p class="stats-number"><?php echo getInProgressJobs($conn, $mechanic_id); ?></p>
                </div>
            </div>
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stats-info">
                    <h3>Today's Completed Jobs</h3>
                    <p class="stats-number"><?php echo getCompletedJobs($conn, $mechanic_id); ?></p>
                </div>
            </div>
           
        </section>

        <!-- Pending Maintenance Section -->
        <section class="pending-maintenance">
            <div class="section-header">
                <h2>Pending Maintenance</h2>
                <button class="show-more-btn" id="showMoreBtn" onclick="toggleMaintenanceView()">Show All</button>
            </div>
            <div class="table-container">
                <?php
                $pending_maintenance = getPendingMaintenance($conn, $mechanic_id, 6);
                if (count($pending_maintenance) > 0):
                ?>
                <table class="maintenance-table">
                    <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>Task</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="maintenanceTableBody">
                        <?php foreach ($pending_maintenance as $maintenance): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($maintenance['registration_no']); ?></td>
                            <td><?php echo htmlspecialchars($maintenance['task_name']); ?></td>
                            <td><?php echo htmlspecialchars($maintenance['schedule_date']); ?></td>
                            <td><?php echo htmlspecialchars($maintenance['schedule_start_time'] . ' - ' . $maintenance['schedule_end_time']); ?></td>
                            <td><span class="status-badge <?php echo strtolower($maintenance['status']); ?>"><?php echo $maintenance['status']; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-pending-maintenance">
                    <p>No pending maintenances scheduled.</p>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <script>
        let isShowingAll = false;
        
        function toggleMaintenanceView() {
            const button = document.getElementById('showMoreBtn');
            const tbody = document.getElementById('maintenanceTableBody');
            
            if (!isShowingAll) {
                fetch('get_all_maintenance.php')
                    .then(response => response.json())
                    .then(data => {
                        tbody.innerHTML = '';
                        data.forEach(maintenance => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${maintenance.registration_no}</td>
                                <td>${maintenance.task_name}</td>
                                <td>${maintenance.schedule_date}</td>
                                <td>${maintenance.schedule_start_time} - ${maintenance.schedule_end_time}</td>
                                <td><span class="status-badge ${maintenance.status.toLowerCase()}">${maintenance.status}</span></td>
                            `;
                            tbody.appendChild(row);
                        });
                        button.textContent = 'Show Less';
                        isShowingAll = true;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while fetching maintenance data');
                    });
            } else {
                // Reload the page to show only 6 items
                location.reload();
                }
        }

        // Modal Functions
        function showAdmitModal() {
            document.getElementById('admitVehicleModal').style.display = 'block';
        }

        function closeAdmitModal() {
            document.getElementById('admitVehicleModal').style.display = 'none';
        }

        function showCheckoutModal() {
            document.getElementById('checkoutVehicleModal').style.display = 'block';
        }

        function closeCheckoutModal() {
            document.getElementById('checkoutVehicleModal').style.display = 'none';
        }

        function showServiceHistoryModal() {
            document.getElementById('serviceHistoryModal').style.display = 'block';
        }

        function closeServiceHistoryModal() {
            document.getElementById('serviceHistoryModal').style.display = 'none';
            document.getElementById('historyTableBody').innerHTML = '';
            document.getElementById('registrationNo').value = '';
            document.querySelector('.table-search').style.display = 'none';
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(event) {
            // Admit Vehicle Modal
            if (event.target == document.getElementById('admitVehicleModal')) {
                closeAdmitModal();
            }
            
            // Checkout Vehicle Modal
            if (event.target == document.getElementById('checkoutVehicleModal')) {
                closeCheckoutModal();
            }
            
            // Service History Modal
            if (event.target == document.getElementById('serviceHistoryModal')) {
                closeServiceHistoryModal();
            }
        });
    </script>

    <!-- Include the admit vehicle modal -->
    <?php include 'admit_vehicle_modal.php'; ?>

    <!-- Include the checkout vehicle modal -->
    <?php include 'checkout_vehicle_modal.php'; ?>

    <!-- Include the service history modal -->
    <?php include 'service_history_modal.php'; ?>

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
        function openModal() {
            document.getElementById("changePasswordModal").style.display = "flex";
        }

        function closeModal(event) {
            if (event && event.target.id === "changePasswordModal") {
                document.getElementById("changePasswordModal").style.display = "none";
            }
        }

        function closeModalDirect() {
            document.getElementById("changePasswordModal").style.display = "none";
        }

        // Ensure clicking outside the modal closes it
        document.addEventListener("click", function(event) {
            let modal = document.getElementById("changePasswordModal");
            if (event.target === modal) {
                closeModal();
            }
        });

        // Form submission handler
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
                            closeModal();
                            // Clear the form
                            document.getElementById("changePasswordForm").reset();
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
    </script>

    <!-- Add SweetAlert2 CSS and JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

    <!-- Include the message modal -->
    <?php include 'message_modal.php'; ?>

    <!-- Add Font Awesome for the bell icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Include the view replies modal -->
    <?php include 'view_replies_modal.php'; ?>

    <!-- Maintenance Notification Modal -->
    <div id="maintenanceNotificationModal" class="modal" style="display: none;">
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

    <script>
        document.getElementById("serviceHistoryModal").addEventListener("click", function(event) {
            if (event.target === this) {
                this.style.display = "none";
            }
        });
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
    // Maintenance Notification Functions for Mechanic
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
