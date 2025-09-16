<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is a driver
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'driver') {
    die("Unauthorized access");
}

// Get the schedule ID from the request
$schedule_id = $_GET['schedule_id'] ?? null;
if (!$schedule_id) {
    die("Schedule ID is required");
}

// Fetch the maintenance schedule details
$query = "SELECT ms.*, mt.task_name, mt.estimated_time, sc.service_center_name 
          FROM maintenance_schedule ms
            JOIN maintenance_tasks mt ON ms.task_id = mt.task_id
            JOIN service_centers sc ON ms.service_center_id = sc.service_center_id
            JOIN vehicles v ON ms.vehicle_id = v.vehicle_id
          WHERE ms.schedule_id = ? AND v.assigned_driver = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $schedule_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$schedule = $result->fetch_assoc();

if (!$schedule) {
    die("Schedule not found or unauthorized");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Maintenance</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .modal {
            display: block;
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
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 8px;
            max-height: 80vh;
            overflow-y: auto;
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

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .service-centers {
            margin: 15px 0;
            border: 1px solid #ccc;
            padding: 15px;
            border-radius: 4px;
        }

        .service-center {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 4px;
        }

        .service-center h6 {
            margin-bottom: 10px;
            color: #333;
            font-weight: bold;
        }

        .timeslots {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .timeslot {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .timeslot:hover {
            background-color: #f0f0f0;
        }

        .timeslot.selected {
            background-color: #3498db;
            color: white;
            border-color: #2980b9;
        }

        .timeslot.unavailable {
            background-color: #f8f9fa;
            color: #6c757d;
            cursor: not-allowed;
            border-color: #dee2e6;
        }

        .submit-btn {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }

        .submit-btn:hover {
            background-color: #2980b9;
        }

        .error-message {
            color: #dc3545;
            margin-top: 10px;
            display: none;
        }

        #service-center-container {
            max-height: 300px;
            overflow-y: auto;
            padding-right: 15px;
        }
    </style>
</head>
<body>
    <div class="modal">
        <div class="modal-content">
            <span class="close" onclick="window.location.href='manage_bookings.php'">&times;</span>
            <h2>Reschedule Maintenance</h2>
            <form id="rescheduleForm">
                <input type="hidden" name="schedule_id" value="<?php echo $schedule_id; ?>">
                <input type="hidden" name="task_id" value="<?php echo $schedule['task_id']; ?>">
                
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="service-centers">
                    <h5><b>Available Timeslots</b></h5>
                    <div id="service-center-container"></div>
                </div>

                <input type="hidden" name="service_center_id" id="selected_service_center_id" required>
                <input type="hidden" name="start_time" id="selected_start_time" required>
                <input type="hidden" name="end_time" id="selected_end_time" required>

                <button type="submit" class="submit-btn">Reschedule Maintenance</button>
                <div id="errorMessage" class="error-message"></div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Set minimum date to today + 1 day
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const tomorrowFormatted = tomorrow.toISOString().split('T')[0];
            $('#date').attr('min', tomorrowFormatted);

            // Fetch available timeslots when date is selected
            $('#date').change(function() {
                const selectedDate = new Date($(this).val());
                const currentDate = new Date();
                const hoursDiff = (selectedDate - currentDate) / (1000 * 60 * 60);

                if (hoursDiff < 24) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Date',
                        text: 'Maintenance can only be rescheduled if it\'s at least 24 hours away',
                    });
                    $(this).val('');
                    return;
                }

                const date = $(this).val();
                const taskId = $('input[name="task_id"]').val();
                
                if (date) {
                    $.ajax({
                        url: 'fetch_timeslots.php',
                        type: 'POST',
                        data: {
                            date: date,
                            task_id: taskId
                        },
                        success: function(response) {
                            $('#service-center-container').html(response);
                        },
                        error: function() {
                            $('#errorMessage').text('Error fetching timeslots').show();
                        }
                    });
                }
            });

            // Handle timeslot selection
            $(document).on('click', '.timeslot:not(.unavailable)', function() {
                $('.timeslot').removeClass('selected');
                $(this).addClass('selected');
                
                const serviceCenterId = $(this).data('center-id');
                const startTime = $(this).data('start-time');
                const endTime = $(this).data('end-time');
                
                $('#selected_service_center_id').val(serviceCenterId);
                $('#selected_start_time').val(startTime);
                $('#selected_end_time').val(endTime);
            });

            // Handle form submission
            $('#rescheduleForm').submit(function(e) {
                e.preventDefault();
                
                const formData = {
                    schedule_id: $('input[name="schedule_id"]').val(),
                    date: $('#date').val(),
                    service_center_id: $('#selected_service_center_id').val(),
                    start_time: $('#selected_start_time').val(),
                    end_time: $('#selected_end_time').val()
                };

                if (!formData.date || !formData.service_center_id || !formData.start_time || !formData.end_time) {
                    $('#errorMessage').text('Please select a date and timeslot').show();
                    return;
                }

                // Check if the selected date and time is at least 24 hours away
                const selectedDateTime = new Date(formData.date + 'T' + formData.start_time);
                const currentDateTime = new Date();
                const hoursDiff = (selectedDateTime - currentDateTime) / (1000 * 60 * 60);

                if (hoursDiff < 24) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Schedule',
                        text: 'Maintenance can only be rescheduled if it\'s at least 24 hours away',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                $.ajax({
                    url: 'update_maintenance_schedule.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Maintenance has been successfully rescheduled.',
                                confirmButtonText: 'OK'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'manage_bookings.php';
                                }
                            });
                        } else {
                            $('#errorMessage').text(response.message).show();
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#errorMessage').text('An error occurred. Please try again.').show();
                    }
                });
            });
        });
    </script>
</body>
</html> 