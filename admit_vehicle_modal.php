<?php
require_once 'db_connect.php';

// Check if user is logged in and is a mechanic
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mechanic') {
    die("Unauthorized access");
}

// Get today's date
$today = date('Y-m-d');

// Fetch scheduled vehicles for today
$query = "SELECT ms.schedule_id, v.registration_no, v.make, v.model, mt.task_name, ms.schedule_start_time, ms.schedule_end_time
          FROM maintenance_schedule ms
          JOIN vehicles v ON ms.vehicle_id = v.vehicle_id
          JOIN maintenance_tasks mt ON ms.task_id = mt.task_id
          JOIN service_center_mechanics scm ON ms.service_center_id = scm.service_center_id
          WHERE ms.schedule_date = ? AND ms.status = 'Scheduled' AND scm.mechanic_id = ?
          ORDER BY ms.schedule_start_time ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("si", $today, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$scheduled_vehicles = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admit Vehicle</title>
    <style>
        .fmms-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .fmms-modal-content {
            position: relative;
            background-color: #fff;
            padding: 20px;
            width: 80%;
            max-width: 800px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            margin: 0;
        }

        .fmms-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .fmms-modal-title {
            font-size: 1.5rem;
            color: #2c3e50;
            margin: 0;
        }

        .fmms-close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
            transition: color 0.3s;
        }

        .fmms-close-modal:hover {
            color: #333;
        }

        .fmms-vehicle-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .fmms-vehicle-table th,
        .fmms-vehicle-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .fmms-vehicle-table th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
        }

        .fmms-vehicle-table tr:hover {
            background-color: #f8f9fa;
        }

        .fmms-admit-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .fmms-admit-btn:hover {
            background-color: #2980b9;
        }

        .fmms-admit-btn:disabled {
            background-color: #95a5a6;
            cursor: not-allowed;
        }

        .fmms-no-vehicles {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="fmms-modal-overlay" id="admitVehicleModal">
        <div class="fmms-modal-content">
            <div class="fmms-modal-header">
                <h2 class="fmms-modal-title">Admit Vehicles for Today</h2>
                <button class="fmms-close-modal" onclick="closeAdmitModal()">&times;</button>
            </div>
            
            <div class="table-container">
                <?php if (count($scheduled_vehicles) > 0): ?>
                    <table class="fmms-vehicle-table">
                        <thead>
                            <tr>
                                <th>Registration No</th>
                                <th>Make & Model</th>
                                <th>Task</th>
                                <th>Time Slot</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scheduled_vehicles as $vehicle): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($vehicle['registration_no']); ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['task_name']); ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['schedule_start_time'] . ' - ' . $vehicle['schedule_end_time']); ?></td>
                                    <td>
                                        <button class="fmms-admit-btn" 
                                                onclick="admitVehicle(<?php echo $vehicle['schedule_id']; ?>)">
                                            Admit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="fmms-no-vehicles">
                        No vehicles scheduled for maintenance today.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function admitVehicle(scheduleId) {
            if (confirm('Are you sure you want to admit this vehicle?')) {
                fetch('admit_vehicle.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `schedule_id=${scheduleId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Vehicle admitted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while admitting the vehicle');
                });
            }
        }

        function showAdmitModal() {
            document.getElementById('admitVehicleModal').style.display = 'flex';
        }
    </script>
</body>
</html> 