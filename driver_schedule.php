<?php

include 'db_connect.php';

$driver_id = $_SESSION['user_id']; // Assuming the session holds the logged-in driver's ID

$query = "
    SELECT ms.schedule_id, v.registration_no, mt.task_name, sc.service_center_name, 
           ms.schedule_date, ms.schedule_start_time, ms.schedule_end_time, ms.status
    FROM Maintenance_Schedule ms
    JOIN Vehicles v ON ms.vehicle_id = v.vehicle_id
    JOIN Maintenance_Tasks mt ON ms.task_id = mt.task_id
    JOIN Service_Centers sc ON ms.service_center_id = sc.service_center_id
    WHERE v.assigned_driver = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Schedules</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
 /* General Button Styling */
button {
    padding: 10px 20px;
    background-color: #007bff;
    color: white;
    border: none;
    cursor: pointer;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}

button:hover {
    background-color: #0056b3;
}

/* Table Styling */
table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    table-layout: auto;
}

/* Responsive Table Container */
.modal-content {
    overflow-x: auto;
    border-radius: 10px;
}

/* Table Headers & Cells */
th, td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: center;
    word-wrap: break-word;
    font-size: 14px;
}

th {
    background-color: #007bff;
    color: white;
    font-size: 16px;
    white-space: nowrap;
}

/* Alternate Row Styling */
tr:nth-child(even) {
    background-color: #f2f2f2;
}

.modal {
    visibility: hidden; /* Initially hidden */
    opacity: 0;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    
    display: flex;
    align-items: center; /* Vertically center */
    justify-content: center; /* Horizontally center */
    transition: visibility 0.3s ease-in-out, opacity 0.3s ease-in-out;
}

/* Make the modal scrollable */
.modal-content {
    position: relative;
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
    width: 90%;
    max-width: 1200px;
    max-height: 80vh; /* Limits the height to 80% of the viewport height */
    overflow-y: auto; /* Enables vertical scrolling if content exceeds max height */
}


/* Show the modal when active */
.modal.show {
    visibility: visible;
    opacity: 1;
}


/* Larger modal for schedules */
#scheduleModal .modal-content {
    max-width: 1200px;
}


/* Edit Modal - Smaller */
#editModal .modal-content {
    width: 50%;
    max-width: 500px;
}


/* Close Button */
.close {
    position: absolute;
    right: 15px;
    top: 10px;
    font-size: 22px;
    cursor: pointer;
    color: #333;
    transition: color 0.3s ease;
}

.close:hover {
    color: red;
}

/* Edit Button */
.edit-btn {
    padding: 6px 12px;
    background-color: #007bff;
    color: white;
    border: none;
    cursor: pointer;
    border-radius: 5px;
    font-size: 14px;
    transition: background-color 0.3s ease;
}

.edit-btn:hover {
    background-color: #0056b3;
}

/* Edit Modal Form */
#editForm {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

#editForm label {
    font-weight: bold;
    text-align: left;
}

#editForm input {
    padding: 8px;
    width: 100%;
    border: 1px solid #ccc;
    border-radius: 5px;
}

#editForm button {
    background-color: #007bff;
    color: white;
    padding: 10px;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}

#editForm button:hover {
    background-color: #0056b3;
}

/* Fade-In Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive Design */
@media screen and (max-width: 768px) {
    th, td {
        padding: 8px;
        font-size: 12px;
    }

    button, .edit-btn {
        padding: 8px 16px;
        font-size: 12px;
    }

    #editModal .modal-content {
        width: 80%;
        max-width: 400px;
    }
}

    </style>
</head>
<body>



<!-- Schedule Modal -->
<div id="scheduleModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>My Maintenance Schedules</h2>
        
        <table>
            <thead>
                <tr>
                    <th>Schedule ID</th>
                    <th>Vehicle</th>
                    <th>Task</th>
                    <th>Service Center</th>
                    <th>Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { 
                    $schedule_date = $row['schedule_date'];
                    $is_past = (strtotime($schedule_date) < strtotime(date('Y-m-d'))); // Check if the schedule date is in the past
                ?>
                <tr>
                    <td><?= $row['schedule_id'] ?></td>
                    <td><?= $row['registration_no'] ?></td>
                    <td><?= $row['task_name'] ?></td>
                    <td><?= $row['service_center_name'] ?></td>
                    <td><?= $schedule_date ?></td>
                    <td><?= $row['schedule_start_time'] ?></td>
                    <td><?= $row['schedule_end_time'] ?></td>
                    <td><?= $row['status'] ?></td>
                    <td>
                        <?php if (!$is_past) { ?>
                            <button class="edit-btn" data-id="<?= $row['schedule_id'] ?>" 
                                    data-date="<?= $schedule_date ?>" 
                                    data-start="<?= $row['schedule_start_time'] ?>" 
                                    data-end="<?= $row['schedule_end_time'] ?>">
                                Edit
                            </button>
                        <?php } else { ?>
                            <button class="edit-btn" disabled style="background-color: gray; cursor: not-allowed;">Past</button>
                        <?php } ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Schedule Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Edit Schedule</h2>
        <form id="editForm">
            <input type="hidden" id="schedule_id" name="schedule_id">
            
            <label>Date:</label>
            <input type="date" id="schedule_date" name="schedule_date" required>

            <label>Start Time:</label>
            <input type="time" id="schedule_start_time" name="schedule_start_time" required>

            <label>End Time:</label>
            <input type="time" id="schedule_end_time" name="schedule_end_time" required>

            <button type="submit">Update</button>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    $(document).ready(function() {
    // Open the schedule modal
    $("#openModal").click(function() {
        $("#scheduleModal").addClass("show");
    });

    // Close modals when clicking the close button
    $(".close").click(function() {
        $(this).closest(".modal").removeClass("show");
    });

    // Open edit modal when clicking the edit button
    $(".edit-btn").click(function() {
        $("#schedule_id").val($(this).data("id"));
        $("#schedule_date").val($(this).data("date"));
        $("#schedule_start_time").val($(this).data("start"));
        $("#schedule_end_time").val($(this).data("end"));
        $("#editModal").addClass("show");
    });
});

    // Prevent form from reloading the page when submitting updates
    $("#editForm").submit(function(e) {
        e.preventDefault();
        $.post("update_schedule.php", $(this).serialize(), function(response) {
            alert(response);
            location.reload();
        });
    });

    // Close modal when clicking outside the modal content
    $(window).click(function(event) {
        if ($(event.target).hasClass("modal")) {
            $(".modal").removeClass("show");
        }
    });
});

</script>

</body>
</html>
