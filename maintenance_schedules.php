<?php
session_start();
include 'db_connect.php';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maintenance Schedules</title>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

        .container {
            width: 90%;
            margin: auto;
            padding: 20px;
        }

        h2 {
            text-align: center;
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
            width: 200px;
            text-decoration: none;
        }

        .back-btn:hover {
            background-color: #2471A3;
        }

        .search-box {
            width: 100%;
            margin: 0 auto 20px auto;
            padding: 12px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 16px;
            box-sizing: border-box;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background-color: #fff;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #34495E;
            color: white;
        }

        .status {
            padding: 5px 10px;
            border-radius: 5px;
            color: #fff;
            display: inline-block;
        }

        .Scheduled { background-color: #17a2b8; }
        .Admitted { background-color: #ffc107; }
        .Completed { background-color: #28a745; }
        .Cancelled { background-color: #dc3545; }

        .edit-btn {
            background-color: #f39c12;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 5px;
        }

        .edit-btn:hover {
            background-color: #e67e22;
        }

        /* Modal */
        .modal {
    display: none;
    position: fixed;
    z-index: 999;
    padding-top: 60px;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;              /* Enables scroll if modal content exceeds screen */
    background-color: rgba(0,0,0,0.5);
}


        .modal-content {
    background-color: #fff;
    margin: auto;
    padding: 20px;
    border-radius: 10px;
    width: 50%;
    max-height: 80vh;            /* Limit height */
    overflow-y: auto;            /* Enable vertical scrolling */
    box-sizing: border-box;
}


        .close {
            float: right;
            font-size: 24px;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 12px;
        }

        label {
            font-weight: bold;
        }

        input[type="text"], input[type="date"], input[type="time"], textarea, select {
            width: 100%;
            padding: 8px;
            margin-top: 4px;
            box-sizing: border-box;
        }

        .btn-submit {
            background-color: #27ae60;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-submit:hover {
            background-color: #219150;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Maintenance Schedule</h2>
    <a href="admin_dashboard.php" class="back-btn">Back to Dashboard</a>
    <input type="text" id="searchInput" class="search-box" placeholder="Search schedules...">
    <table id="scheduleTable">
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
                <th>Additional Info</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT 
                        ms.schedule_id,
                        v.registration_no AS vehicle,
                        mt.task_name AS task,
                        sc.service_center_name AS service_center,
                        ms.schedule_date,
                        ms.schedule_start_time,
                        ms.schedule_end_time,
                        ms.status,
                        ms.additional_info
                    FROM Maintenance_Schedule ms
                    JOIN Vehicles v ON ms.vehicle_id = v.vehicle_id
                    JOIN Maintenance_Tasks mt ON ms.task_id = mt.task_id
                    JOIN Service_Centers sc ON ms.service_center_id = sc.service_center_id
                    ORDER BY ms.schedule_date DESC";

            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>{$row['schedule_id']}</td>
                        <td>{$row['vehicle']}</td>
                        <td>{$row['task']}</td>
                        <td>{$row['service_center']}</td>
                        <td>{$row['schedule_date']}</td>
                        <td>{$row['schedule_start_time']}</td>
                        <td>{$row['schedule_end_time']}</td>
                        <td><span class='status {$row['status']}'>{$row['status']}</span></td>
                        <td>{$row['additional_info']}</td>";
                    if ($role !== 'admin') {
                        echo "<td><button class='edit-btn' onclick='openModal(" . json_encode($row) . ")'>Edit</button></td>";
                    } else {
                        echo "<td><span style='color: #888;'>Not allowed</span></td>";
                    }
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='10' style='text-align:center;'>No maintenance schedules found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<?php if ($role !== 'admin'): ?>
<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Edit Maintenance Schedule</h3>
        <form action="update_schedules.php" method="POST">
            <input type="hidden" name="schedule_id" id="schedule_id">
            <div class="form-group">
                <label>Date:</label>
                <input type="date" name="schedule_date" id="schedule_date" required>
            </div>
            <div class="form-group">
                <label>Start Time:</label>
                <input type="time" name="schedule_start_time" id="schedule_start_time" required>
            </div>
            <div class="form-group">
                <label>End Time:</label>
                <input type="time" name="schedule_end_time" id="schedule_end_time" required>
            </div>
            <div class="form-group">
                <label>Status:</label>
                <select name="status" id="status" required>
                    <option value="Scheduled">Scheduled</option>
                    <option value="Admitted">Admitted</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>
            <div class="form-group">
                <label>Additional Info:</label>
                <textarea name="additional_info" id="additional_info"></textarea>
            </div>
            <button type="submit" class="btn-submit">Update Schedule</button>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
    document.getElementById("searchInput").addEventListener("keyup", function () {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll("#scheduleTable tbody tr");
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? "" : "none";
        });
    });

    function openModal(data) {
        document.getElementById("schedule_id").value = data.schedule_id;
        document.getElementById("schedule_date").value = data.schedule_date;
        document.getElementById("schedule_start_time").value = data.schedule_start_time;
        document.getElementById("schedule_end_time").value = data.schedule_end_time;
        document.getElementById("status").value = data.status;
        document.getElementById("additional_info").value = data.additional_info;
        document.getElementById("editModal").style.display = "block";
    }

    function closeModal() {
        document.getElementById("editModal").style.display = "none";
    }

    window.onclick = function (event) {
        if (event.target === document.getElementById("editModal")) {
            closeModal();
        }
    };
</script>

</body>
</html>
