<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Maintenance Booking</title>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div id="modalDialog" class="modal" style="display: block">
        <div class="modal-content animate-top">
            <div class="modal-header">
                <h5 class="modal-title">Vehicle Maintenance Booking</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
    <span aria-hidden="true">&times;</span>
</button>

            </div>
            <div class="modal_container">
                <div id="message-container"></div>
                <form action="" method="POST" id="maintenanceBookingForm">
                    <div class="details">
                        <div class="input-box">
                            <span class="details">Date</span>
                            <input type="date" name="date" id="date" required />
                        </div>
                        <div class="input-box">
                            <span class="details">Maintenance Task</span>
                            <select name="maintenance_task" id="maintenance_task" required>
                                <option value="" disabled selected>Select a maintenance task</option>
                                <!-- Options will be populated by JavaScript -->
                            </select>
                        </div>
                        <div class="input-box full-width">
                            <span class="details">Additional Information</span>
                            <textarea name="additional_info" id="additional_info" placeholder="Enter additional information" maxlength="500"></textarea>
                        </div>
                        <input type="hidden" name="service_center_id" id="selected_service_center_id" required />
                        <input type="hidden" name="start_time" id="selected_start_time" required />
                        <input type="hidden" name="end_time" id="selected_end_time" required />
                    </div>
                    <div class="service-centers">
                        <h5><b>Available timeslots</b></h5>
                        <div id="service-center-container"></div>
                    </div>
                    <div class="button">
                        <input type="submit" value="Submit Maintenance" />
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script>
        
        $(document).ready(function() {
            // Function to fetch maintenance tasks from the server
            function fetchMaintenanceTasks() {
                $.ajax({
                    url: 'fetch_maintenance_tasks.php',
                    type: 'GET',
                    success: function(data) {
                        $('#maintenance_task').append(data); // Append options to select
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching maintenance tasks:', error);
                    }
                });
            }

            // Function to fetch available timeslots based on selected date and task
            function fetchAvailableTimeslots() {
                const date = $('#date').val();
                const selectedTaskValue = $('#maintenance_task').val();

                if (date && selectedTaskValue) {
                    // Extract task_id from the combined value (task_id:task_name)
                    const taskParts = selectedTaskValue.split(':');
                    const taskId = taskParts[0];

                    $.ajax({
                        url: 'fetch_timeslots.php',
                        type: 'POST',
                        data: {
                            date: date,
                            task_id: taskId // Send task_id instead of task name
                        },
                        success: function(data) {
                            $('#service-center-container').html(data); // Display available timeslots
                        },
                        error: function(xhr, status, error) {
                            console.error('Error fetching timeslots:', error);
                             Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error fetching timeslots. Please try again.',
                            });
                        }
                    });
                }
            }

            // Validate selected date to be within the allowed range
            function validateDate() {
                const selectedDate = new Date($('#date').val());
                const today = new Date();
                const minDate = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 1);
                const maxDate = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 90);

                if (selectedDate < minDate || selectedDate > maxDate) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Date',
                        text: 'Date must be at least 24 hours in the future and not more than 90 days ahead.',
                    });
                    $('#date').val('');
                    return false;
                }
                return true;
            }

            // Initialize form with maintenance tasks and attach event listeners
            fetchMaintenanceTasks();

            $('#date').change(function() {
                if (validateDate()) {
                    fetchAvailableTimeslots();
                }
            });

            $('#maintenance_task').change(fetchAvailableTimeslots);

            // Handle click on available timeslot to select it
            $('#service-center-container').on('click', '.timeslot.available', function() {
                $('.timeslot').removeClass('selected');
                $(this).addClass('selected');

                // Set hidden inputs with selected timeslot details
                $('#selected_service_center_id').val($(this).data('center-id'));
                $('#selected_start_time').val($(this).data('start-time'));
                $('#selected_end_time').val($(this).data('end-time'));
            });

            // Handle form submission to book maintenance
            $('#maintenanceBookingForm').submit(function(e) {
                e.preventDefault();
                
                // Get form data
                const selectedTaskValue = $('#maintenance_task').val();
                console.log('Selected task value:', selectedTaskValue);
                
                const taskParts = selectedTaskValue.split(':');
                const taskId = taskParts[0]; // Get task_id for booking
                const taskName = taskParts[1]; // Get task_name for logging or other use if needed
                
                console.log('Task ID:', taskId, 'Task Name:', taskName);

                var formData = {
                    date: $('#date').val(),
                    maintenance_task: taskName, // Send task name for booking process if needed
                    task_id: taskId, // Also send task_id
                    additional_info: $('#additional_info').val(),
                    service_center_id: $('#selected_service_center_id').val(),
                    start_time: $('#selected_start_time').val(),
                    end_time: $('#selected_end_time').val()
                };
                
                console.log('Form data being sent:', formData);
                
                // Validate form data
                if (!formData.date || !formData.task_id || !formData.service_center_id || !formData.start_time || !formData.end_time) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Missing Information',
                        text: 'Please fill in all required fields and select a timeslot.',
                    });
                    return;
                }
                
                // Send AJAX request
                $.ajax({
                    type: 'POST',
                    url: 'book_maintenance.php',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                confirmButtonText: 'OK'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = "driver_dashboard.php";
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'Error scheduling maintenance'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        console.error('Status:', status);
                        console.error('Response:', xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error scheduling maintenance. Please try again.'
                        });
                    }
                });
            });
        });

        $(document).ready(function() {
    // Close modal and redirect when 'X' button is clicked
    $(".close").click(function() {
        window.location.href = "driver_dashboard.php";
    });

    // Close modal and redirect when clicking outside the modal content
    $(window).click(function(event) {
        if ($(event.target).is("#modalDialog")) {
            window.location.href = "driver_dashboard.php";
        }
    });
});

    </script>

</body>

</html>




<!--css-->
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Poppins", sans-serif;
    }

    body {
        display: flex;
        height: 100vh;
        justify-content: center;
        align-items: center;
        padding: 10px;
        background-color: #f5f5f5;
    }

    .modal_container {
        padding: 20px;
    }

    ::-webkit-input-placeholder {
        color:rgb(16, 16, 16);
    }

    ::-moz-placeholder {
        color:rgb(16, 16, 16);
    }

    :-ms-input-placeholder {
        color:rgb(16, 16, 16);
    }

    :-moz-placeholder {
        color:rgb(16, 16, 16);
    }

    .animate-top {
        position: relative;
        animation: animatetop 0.4s;
    }

    @keyframes animatetop {
        from {
            top: -300px;
            opacity: 0;
        }

        to {
            top: 0;
            opacity: 1;
        }
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(5px);
    }

    .modal-content {
        margin: 8% auto;
        border: 1px solid #888;
        max-width: 65%;
        width: auto;
        background-color: #fff;
        border: 1px solid rgba(0, 0, 0, .2);
        border-radius: 10px;
        outline: 0;
    }

    .modal-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        padding: 1rem;
        border-bottom: 1px solid #34495E;
        border-top-left-radius: 0.3rem;
        border-top-right-radius: 0.3rem;
    }

    .modal-title {
        margin: 0;
        line-height: 1.5;
        font-size: 1.25rem;
        color: #666;
    }

    .close {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1;
        color: #000;
        text-shadow: 0 1px 0 #fff;
        opacity: 0.5;
        background-color: transparent;
        border: none;
        cursor: pointer;
    }

    .modal-body {
        flex: 1 1 auto;
        padding: 1rem;
    }

    .modal-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding: 1rem;
        border-top: 1px solid #34495E;
    }

    .modal_container form .details {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
    }

    form .details .input-box {
        margin-bottom: 15px;
        width: calc(50% - 10px);
    }

    .details .input-box.full-width {
        width: 100%;
    }

    .details .input-box .details {
        display: block;
        font-weight: 500;
        margin-bottom: 5px;
    }

    h5 {
        padding-bottom: 8px;
    }

    form .details .input-box input,
    .details .input-box textarea,
    .details .input-box select {
        height: 45px;
        width: 100%;
        outline: none;
        border-radius: 5px;
        border: 1px solid #ccc;
        padding-left: 15px;
        font-size: 16px;
        border-bottom-width: 2px;
        transition: all 0.3s ease;
    }

    .details .input-box textarea {
        height: auto;
        padding-top: 10px;
        resize: vertical;
    }

    .details .input-box input:focus,
    .details .input-box textarea:focus,
    .details .input-box select:focus,
    .details .input-box input:valid,
    .details .input-box textarea:valid,
    .details .input-box select:valid {
        border-color:rgb(172, 130, 135);
    }

    .service-centers {
        margin: 15px 0;
        border: 1px solid #ccc;
        padding: 5px;
    }

    .service-center {
        margin-bottom: 15px;
        padding: 10px;
        border-radius: 5px;
    }

    .service-center h6 {
        margin-bottom: 10px;
        color: #333;
        font-weight: bold;
        font-size: 0.95rem;
    }

    .timeslots {
        display: flex;
        flex-wrap: wrap;
    }

    .timeslot {
        padding: 12px;
        margin: 5px;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .timeslot.available {
        background-color:#34495E;
        color: white;
    }

    .timeslot.unavailable {
        background-color: #ccc;
        color: #666;
        cursor: not-allowed;
    }

    .timeslot.selected {
        background-color: #757575;
    }

    form .button {
        height: 45px;
        width: 90%;
        margin: 0 auto;
        padding: 2px;
    }

    form .button input {
        height: 100%;
        width: 100%;
        outline: none;
        color: #fff;
        border: none;
        font-size: 18px;
        font-weight: 500;
        border-radius: 5px;
        letter-spacing: 1px;
        background: #2980B9;
    }

    form .button input:hover {
        background: linear-gradient(-135deg, #71b7e6, #2980B9);
    }

    @media (max-width: 584px) {
        .modal_container {
            max-width: 100%;
        }

        .modal_container form .details {
            max-height: 300px;
            overflow-y: scroll;
        }

        .details::-webkit-scrollbar {
            width: 0;
        }

        form .details .input-box {
            width: 100%;
        }
    }

    .close {
    font-size: 24px;
    font-weight: bold;
    border: none;
    background: none;
    cursor: pointer;
}

</style>
<!--css-->