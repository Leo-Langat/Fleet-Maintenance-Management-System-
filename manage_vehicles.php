<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Management</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f4f4f4;
    }

    .container {
        width: 90%; /* Adjusted width to make more room */
        margin: auto;
        overflow: hidden;
        padding: 20px;
    }

    h2 {
        text-align: center;
    }

    .search-bar {
        width: 100%;
        padding: 10px;
        margin-bottom: 20px;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

    .table-container {
        max-height: 500px; /* Set a maximum height */
        overflow-y: auto; /* Enable vertical scrolling */
        border: 1px solid #ddd; /* Add a border to container */
        background-color: #fff;
        padding: 0; /* Remove padding to avoid scrollbar issues */
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background-color: #fff;
    }

    thead th {
        background-color: #34495E;
        color: #fff;
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
        z-index: 2; /* Ensure it stays above the rows */
    }

    tbody td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .status-button {
        padding: 5px 10px;
        border-radius: 5px;
        color: #fff;
        text-align: center;
    }

    .status-active {
        background-color: #28a745; /* Green for active */
    }

    .status-inactive {
        background-color: #ffc107; /* Yellow for maintenance */
    }

    .status-retired {
        background-color: #dc3545; /* Red for retired */
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.7);
        justify-content: center;
        align-items: center;
    }

    .modal-content {
        background-color: #ffffff;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 600px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        margin: 0;
        max-height: 80vh; /* Added for scrollability */
        overflow-y: auto;  /* Added for scrollability */
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }

    .btn {
        padding: 8px 12px;
        border: none;
        border-radius: 5px;
        color: white;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .btn-warning {
        background-color: #ffc107; /* Yellow for edit */
    }

    .btn-warning:hover {
        background-color: #e0a800; /* Darker yellow on hover */
    }

    .btn-danger {
        background-color: #dc3545; /* Red for delete */
    }

    .btn-danger:hover {
        background-color: #c82333; /* Darker red on hover */
    }

    /* Modal Form Styles */
    #editVehicleForm {
        display: flex;
        flex-direction: column;
    }

    #editVehicleForm input,
    #editVehicleForm select {
        margin: 10px 0;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

    #editVehicleForm button {
        background-color: #2980B9;
        color: #fff;
        border: none;
        padding: 10px;
        border-radius: 5px;
        cursor: pointer;
    }

    #editVehicleForm button:hover {
        background-color: #2471A3; /* Darker blue on hover */
    }

    .back-btn {
        background-color: #2980B9;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-align: center;
        display: block;
        margin: 20px auto;
        width: 150px;
        text-decoration: none;
    }

    .back-btn:hover {
        background-color: #2471A3; /* Darker blue on hover */
    }

    .error-message {
        color: red;
        font-size: 12px;
    }
</style>

</head>
<body>

<div class="container">
    <h2>Vehicle Management</h2>
    
    <a href="admin_dashboard.php" class="back-btn">Back to Dashboard</a>

    <input type="text" id="searchBar" placeholder="Search vehicles..." class="search-bar">
<div class="table-container">
    <table id="vehicleTable">
        <thead>
            <tr>
                <th>Vehicle ID</th>
                <th>Registration No</th>
                <th>Make</th>
                <th>Model</th>
                <th>Year</th>
                <th>VIN</th>
                <th>Mileage</th>
                <th>Fuel Type</th>
                <th>Status</th>
                <th>Assigned Driver</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        include 'db_connect.php';
        $sql = "SELECT v.*, u.name AS driver_name FROM vehicles v 
        LEFT JOIN Users u ON v.assigned_driver = u.user_id";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $statusClass = $row['status'] === 'active' ? 'status-active' : ($row['status'] === 'inactive' ? 'status-inactive' : 'status-retired');
 // Determine assigned driver's name or show "Not Assigned"
 $assignedDriver = $row['driver_name'] ? $row['driver_name'] : 'Not Assigned';
                // Set delete button based on the status
                $deleteButton = $row['status'] === 'maintenance' 
                    ? "<button class='btn btn-danger deleteBtn' data-id='{$row['vehicle_id']}' disabled>Delete</button>" 
                    : "<button class='btn btn-danger deleteBtn' data-id='{$row['vehicle_id']}'>Delete</button>";

                echo "<tr>
                    <td>{$row['vehicle_id']}</td>
                    <td>{$row['registration_no']}</td>
                    <td>{$row['make']}</td>
                    <td>{$row['model']}</td>
                    <td>{$row['year']}</td>
                    <td>{$row['vin']}</td>
                    <td>{$row['mileage']}</td>
                    <td>{$row['fuel_type']}</td>
                    <td><span class='status-button $statusClass'>{$row['status']}</span></td>
                      <td>{$assignedDriver}</td>
                    <td>
                        <div style='display: flex; gap: 10px;'>
                            <button class='btn btn-warning editBtn' data-id='{$row['vehicle_id']}'>Edit</button>
                            $deleteButton
                        </div>
                    </td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='10' class='text-center'>No vehicles found</td></tr>";
        }
        ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Edit Vehicle Modal -->
<div class="modal" id="editVehicleModal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Edit Vehicle</h2>
        <form id="editVehicleForm">
            <input type="hidden" name="vehicle_id" id="editVehicleId">
            <input type="text" name="registration_no" id="editVehicleRegistrationNo" placeholder="Registration No" readonly>
            <div id="regNoError" class="error-message"></div>
            <input type="text" name="make" id="editVehicleMake" placeholder="Make" readonly>
            <input type="text" name="model" id="editVehicleModel" placeholder="Model" readonly>
            <input type="number" name="year" id="editVehicleYear" placeholder="Year" readonly>
            <div id="yearError" class="error-message"></div>
            <input type="text" name="vin" id="editVehicleVin" placeholder="VIN" readonly>
            <div id="vinError" class="error-message"></div>
            <input type="number" name="mileage" id="editVehicleMileage" placeholder="Mileage" readonly>
            <div id="mileageError" class="error-message"></div>
            <input type="text" name="fuel_type" id="editVehicleFuelType" placeholder="Fuel Type" readonly>
            <select name="status" id="editVehicleStatus" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="retired">Retired</option>
            </select>
            <select name="assigned_driver" id="editVehicleDriver">
    <option value="">Assign Driver</option>
    <?php
    $sql = "SELECT * FROM Users WHERE role = 'driver'";
    $drivers = $conn->query($sql);
    while($driver = $drivers->fetch_assoc()) {
        echo "<option value='{$driver['user_id']}'>{$driver['name']}</option>";
    }
    ?>
</select>
<div id="driverError" class="error-message"></div>

            <button type="submit">Update Vehicle</button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Search functionality
    $("#searchBar").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#vehicleTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Real-time validation functions
    function validateRegistrationNo() {
        // Registration number is readonly, no validation needed
        $('#regNoError').text("");
    }

    function validateYear() {
        // Year is readonly, no validation needed
        $('#yearError').text("");
    }

    function validateVIN() {
        // VIN is readonly, no validation needed
        $('#vinError').text("");
    }

    function validateMileage() {
        // Mileage is readonly, no validation needed
        $('#mileageError').text("");
    }

    // Add event listeners to validate as the user types
    $('#editVehicleRegistrationNo').on('input', validateRegistrationNo);
    $('#editVehicleYear').on('input', validateYear);
    $('#editVehicleVin').on('input', validateVIN);
    $('#editVehicleMileage').on('input', validateMileage);

    // Open Edit Vehicle Modal
    $(".editBtn").on("click", function() {
        var vehicleId = $(this).data("id");
        // Fetch vehicle data and fill the form
        $.ajax({
            type: "GET",
            url: "get_vehicle.php",
            data: { id: vehicleId },
            success: function(vehicle) {
                const data = JSON.parse(vehicle);
                $("#editVehicleId").val(data.vehicle_id);
                $("#editVehicleRegistrationNo").val(data.registration_no);
                $("#editVehicleMake").val(data.make);
                $("#editVehicleModel").val(data.model);
                $("#editVehicleYear").val(data.year);
                $("#editVehicleVin").val(data.vin);
                $("#editVehicleMileage").val(data.mileage);
                $("#editVehicleFuelType").val(data.fuel_type);
                $("#editVehicleStatus").val(data.status);
                $('#editVehicleDriver').val(vehicle.assigned_driver);
                $("#editVehicleModal").css("display", "flex");
            }
        });
    });

    // Close Modal
    $(".close").on("click", function() {
        $("#editVehicleModal").css("display", "none");
    });
    // Close the modal when clicking outside the modal-content area
$(window).on("click", function(event) {
    var modal = $("#editVehicleModal");
    if ($(event.target).is(modal)) {
        modal.css("display", "none");
    }
});


  // Update Vehicle
$("#editVehicleForm").on("submit", function(e) {
    e.preventDefault();
    // Validate all fields before submitting
    validateRegistrationNo();
    validateYear();
    validateVIN();
    validateMileage();

    const isFormValid = !$('.error-message:not(:empty)').length;
    
    if (!isFormValid) {
        alert("Please correct the errors in the form.");
        return;
    }

    $.ajax({
        type: "POST",
        url: "update_vehicle.php",
        data: $(this).serialize(),
        success: function(response) {
            if (response.startsWith("Error:")) {
                // Display the error message for Registration Number or VIN separately
                $('#regNoError').text('');
                $('#vinError').text('');
                $('#driverError').text(''); 

                if (response.includes("Registration number")) {
                    $('#regNoError').text("Registration number already exists.");
                }
                if (response.includes("VIN")) {
                    $('#vinError').text("VIN already exists.");
                }
                if (response.includes("Driver")) {
                        $('#driverError').text("Driver is already assigned to another vehicle.");
                    }
            } else {
                alert(response);
                location.reload();
            }
        }
    });
});



    // Delete Vehicle
    $(".deleteBtn").on("click", function() {
        var vehicleId = $(this).data("id");
        if (confirm("Are you sure you want to delete this vehicle?")) {
            $.ajax({
                type: "POST",
                url: "delete_vehicle.php",
                data: { id: vehicleId },
                success: function(response) {
                    alert(response);
                    location.reload();
                }
            });
        }
    });
});
</script>

</body>
</html>