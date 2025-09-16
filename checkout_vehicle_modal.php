<?php
require_once 'db_connect.php';

// Check if user is logged in and is a mechanic
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mechanic') {
    die("Unauthorized access");
}

// Fetch admitted vehicles
$query = "SELECT ms.schedule_id, v.vehicle_id, v.registration_no, v.make, v.model, mt.task_name, 
                 v.mileage as initial_mileage, ms.schedule_date, ms.schedule_start_time, ms.schedule_end_time
          FROM maintenance_schedule ms
          JOIN vehicles v ON ms.vehicle_id = v.vehicle_id
          JOIN maintenance_tasks mt ON ms.task_id = mt.task_id
          JOIN service_center_mechanics scm ON ms.service_center_id = scm.service_center_id
          WHERE ms.status = 'Admitted' AND scm.mechanic_id = ?
          ORDER BY ms.schedule_date ASC, ms.schedule_start_time ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$admitted_vehicles = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Out Vehicle</title>
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

        .fmms-checkout-btn {
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .fmms-checkout-btn:hover {
            background-color: #27ae60;
        }

        .fmms-checkout-form {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 600;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .submit-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #2980b9;
        }

        .fmms-no-vehicles {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }

        .fmms-checkout-form-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1001;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .fmms-checkout-form-content {
            position: relative;
            background-color: #fff;
            padding: 20px;
            width: 50%;
            max-width: 500px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            margin: 0;
            max-height: 90vh;
            overflow-y: auto;
        }

        .fmms-checkout-form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .fmms-checkout-form-title {
            font-size: 1.3rem;
            color: #2c3e50;
            margin: 0;
        }

        .fmms-close-checkout-form {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
            transition: color 0.3s;
        }

        .fmms-close-checkout-form:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <!-- Main Modal -->
    <div class="fmms-modal-overlay" id="checkoutVehicleModal">
        <div class="fmms-modal-content">
            <div class="fmms-modal-header">
                <h2 class="fmms-modal-title">Check Out Vehicles</h2>
                <button class="fmms-close-modal" onclick="closeCheckoutModal()">&times;</button>
            </div>
            
            <div class="table-container">
                <?php if (count($admitted_vehicles) > 0): ?>
                    <table class="fmms-vehicle-table">
                        <thead>
                            <tr>
                                <th>Registration No</th>
                                <th>Make & Model</th>
                                <th>Task</th>
                                <th>Date</th>
                                <th>Time Slot</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admitted_vehicles as $vehicle): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($vehicle['registration_no']); ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['task_name']); ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['schedule_date']); ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['schedule_start_time'] . ' - ' . $vehicle['schedule_end_time']); ?></td>
                                    <td>
                                        <button class="fmms-checkout-btn" 
                                                onclick="showCheckoutForm(<?php echo $vehicle['schedule_id']; ?>, '<?php echo htmlspecialchars($vehicle['registration_no']); ?>', <?php echo $vehicle['vehicle_id']; ?>)">
                                            Check Out
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="fmms-no-vehicles">
                        No vehicles currently admitted for maintenance.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Checkout Form Modal -->
    <div class="fmms-checkout-form-modal" id="checkoutFormModal">
        <div class="fmms-checkout-form-content">
            <div class="fmms-checkout-form-header">
                <h2 class="fmms-checkout-form-title">Check Out Vehicle - <span id="vehicleRegNo"></span></h2>
                <button class="fmms-close-checkout-form" onclick="closeCheckoutForm()">&times;</button>
            </div>
            
            <form id="checkoutForm" onsubmit="return submitCheckout(event)">
                <input type="hidden" id="vehicleId" name="vehicle_id">
                <input type="hidden" id="scheduleId" name="schedule_id">
                <input type="hidden" id="initialMileage" name="initial_mileage">
                
                <div class="form-group">
                    <label for="initialMileageDisplay">Current Vehicle Mileage</label>
                    <input type="text" id="initialMileageDisplay" readonly>
                </div>
                
                <div class="form-group">
                    <label for="finalMileage">Vehicle Mileage <span style="color: red;">*</span></label>
                    <input type="number" id="finalMileage" name="final_mileage" placeholder="Enter mileage after service" required min="1">
                </div>
                
                <div class="form-group">
                    <label for="serviceNotes">Service Notes <span style="color: red;">*</span></label>
                    <textarea id="serviceNotes" name="service_notes" placeholder="Enter any notes about the service performed... (required)" required></textarea>
                </div>
                
                <button type="submit" class="submit-btn">Complete Checkout</button>
            </form>
        </div>
    </div>

    <script>
        let currentScheduleId = null;
        let currentVehicleId = null;

        function showCheckoutForm(scheduleId, regNo, vehicleId) {
            currentScheduleId = scheduleId;
            currentVehicleId = vehicleId;
            document.getElementById('vehicleRegNo').textContent = regNo;
            document.getElementById('vehicleId').value = vehicleId;
            document.getElementById('scheduleId').value = scheduleId;
            
            // Fetch initial mileage
            fetch('get_vehicle_mileage.php?vehicle_id=' + vehicleId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('initialMileage').value = data.mileage;
                        document.getElementById('initialMileageDisplay').value = data.mileage + ' km';
                    }
                });
            
            // Hide the main modal
            document.getElementById('checkoutVehicleModal').style.display = 'none';
            // Show the form modal
            document.getElementById('checkoutFormModal').style.display = 'flex';
        }

        function closeCheckoutForm() {
            document.getElementById('checkoutFormModal').style.display = 'none';
            document.getElementById('serviceNotes').value = '';
            currentScheduleId = null;
            currentVehicleId = null;
        }

        function submitCheckout(event) {
            event.preventDefault();
            
            const serviceNotes = document.getElementById('serviceNotes').value.trim();
            const vehicleId = document.getElementById('vehicleId').value;
            const scheduleId = document.getElementById('scheduleId').value;
            const finalMileage = document.getElementById('finalMileage').value;
            const initialMileage = document.getElementById('initialMileage').value;

            if (!currentScheduleId || !currentVehicleId) {
                alert('Error: No vehicle selected');
                return false;
            }

            // Validate final mileage
            if (!finalMileage || finalMileage <= 0) {
                alert('Please enter a valid final mileage.');
                document.getElementById('finalMileage').focus();
                return false;
            }

            // Validate that final mileage is greater than or equal to initial mileage
            if (parseInt(finalMileage) < parseInt(initialMileage)) {
                alert('Final mileage cannot be less than current mileage (' + initialMileage + ' km).');
                document.getElementById('finalMileage').focus();
                return false;
            }

            // Validate service notes
            if (!serviceNotes) {
                alert('Please enter service notes before completing checkout.');
                document.getElementById('serviceNotes').focus();
                return false;
            }

            const formData = new FormData();
            formData.append('schedule_id', scheduleId);
            formData.append('vehicle_id', vehicleId);
            formData.append('final_mileage', finalMileage);
            formData.append('service_notes', serviceNotes);

            fetch('checkout_vehicle.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Vehicle checked out successfully!');
                    closeCheckoutForm();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while checking out the vehicle');
            });
        }

        function showCheckoutModal() {
            document.getElementById('checkoutVehicleModal').style.display = 'flex';
        }

        // Ensure the checkout form modal is hidden on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('checkoutFormModal').style.display = 'none';
        });
    </script>
</body>
</html> 