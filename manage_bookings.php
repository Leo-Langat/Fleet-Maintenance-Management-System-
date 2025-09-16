<?php
session_start();
require_once 'db_connect.php';
require_once 'utils/auto_logger.php';

// Check if user is logged in and is a driver
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'driver') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$logger = new AutoLogger();

// Handle cancellation
if (isset($_POST['cancel_booking'])) {
    $schedule_id = $_POST['schedule_id'];
    
    // Update the status to 'Cancelled'
    $update_query = "UPDATE maintenance_schedule SET status = 'Cancelled' WHERE schedule_id = ? AND vehicle_id IN (SELECT vehicle_id FROM vehicles WHERE assigned_driver = ?)";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $schedule_id, $user_id);
    
    if ($stmt->execute()) {
        $logger->logMaintenanceCancellation($user_id, $schedule_id);
        $_SESSION['message'] = "Booking cancelled successfully.";
    } else {
        $_SESSION['error'] = "Error cancelling booking.";
    }
    
    header("Location: manage_bookings.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Mutai Enterprises</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background-color: #f4f6f7;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 24px;
        }

        .back-btn {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background-color: #2980b9;
        }

        .bookings-table {
            width: 100%;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .bookings-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .bookings-table th,
        .bookings-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .bookings-table th {
            background-color: #34495e;
            color: white;
            font-weight: 500;
        }

        .bookings-table tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-scheduled {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-cancelled {
            background-color: #ffebee;
            color: #c62828;
        }

        .status-completed {
            background-color: #e3f2fd;
            color: #1565c0;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .reschedule-btn {
            background-color: #3498db;
            color: white;
        }

        .reschedule-btn:hover {
            background-color: #2980b9;
        }

        .cancel-btn {
            background-color: #e74c3c;
            color: white;
        }

        .cancel-btn:hover {
            background-color: #c0392b;
        }

        .no-bookings {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-bookings i {
            font-size: 48px;
            color: #bdc3c7;
            margin-bottom: 20px;
        }

        .search-container {
            position: relative;
            margin-bottom: 20px;
            width: 100%;
            max-width: 400px;
        }

        .search-input {
            width: 100%;
            padding: 10px 40px 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .no-results {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Manage My Bookings</h1>
            <a href="driver_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>

        <div class="bookings-table">
            <div class="search-container">
                <input type="text" id="searchBookings" placeholder="Search bookings..." class="search-input">
                <i class="fas fa-search search-icon"></i>
            </div>
            <table id="bookingsTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Task</th>
                        <th>Service Center</th>
                        <th>Time Slot</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
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
                    
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $status_class = strtolower($row['status']);
                            $is_past = strtotime($row['schedule_date']) < strtotime(date('Y-m-d'));
                            ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($row['schedule_date'])); ?></td>
                                <td><?php echo htmlspecialchars($row['task_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['service_center_name']); ?></td>
                                <td><?php echo date('h:i A', strtotime($row['schedule_start_time'])) . ' - ' . 
                                             date('h:i A', strtotime($row['schedule_end_time'])); ?></td>
                                <td><span class="status-badge status-<?php echo $status_class; ?>"><?php echo $row['status']; ?></span></td>
                                <td>
                                    <?php if ($row['status'] === 'Scheduled' && !$is_past): ?>
                                        <button class="action-btn reschedule-btn" onclick="openRescheduleModal(<?php echo $row['schedule_id']; ?>)">
                                            <i class="fas fa-calendar-alt"></i> Reschedule
                                        </button>
                                        <button class="action-btn cancel-btn" onclick="confirmCancellation(<?php echo $row['schedule_id']; ?>)">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="6"><div class="no-bookings"><i class="fas fa-calendar-times"></i><p>No bookings found</p></div></td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function openRescheduleModal(scheduleId) {
            window.location.href = `edit_maintenance_modal.php?schedule_id=${scheduleId}`;
        }

        function confirmCancellation(scheduleId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#7f8c8d',
                confirmButtonText: 'Yes, cancel it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create and submit form
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'manage_bookings.php';
                    
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'schedule_id';
                    input.value = scheduleId;
                    
                    const submitInput = document.createElement('input');
                    submitInput.type = 'hidden';
                    submitInput.name = 'cancel_booking';
                    submitInput.value = '1';
                    
                    form.appendChild(input);
                    form.appendChild(submitInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Show success/error messages
        <?php if (isset($_SESSION['message'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: "<?php echo $_SESSION['message']; ?>",
                confirmButtonColor: '#3498db'
            });
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: "<?php echo $_SESSION['error']; ?>",
                confirmButtonColor: '#3498db'
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchBookings');
            const table = document.getElementById('bookingsTable');
            const tbody = table.querySelector('tbody');
            const rows = tbody.getElementsByTagName('tr');

            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                let hasResults = false;

                for (let row of rows) {
                    const cells = row.getElementsByTagName('td');
                    let found = false;

                    for (let cell of cells) {
                        if (cell.textContent.toLowerCase().includes(searchTerm)) {
                            found = true;
                            break;
                        }
                    }

                    if (found) {
                        row.style.display = '';
                        hasResults = true;
                    } else {
                        row.style.display = 'none';
                    }
                }

                // Show/hide no results message
                let noResults = document.querySelector('.no-results');
                if (!hasResults) {
                    if (!noResults) {
                        noResults = document.createElement('tr');
                        noResults.className = 'no-results';
                        noResults.innerHTML = '<td colspan="6">No bookings found matching your search</td>';
                        tbody.appendChild(noResults);
                    }
                } else if (noResults) {
                    noResults.remove();
                }
            });
        });
    </script>
</body>
</html> 