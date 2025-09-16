<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Task Management</title>
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            width: 80%;
            margin: auto;
            overflow: hidden;
            padding: 20px;
        }

        h2 {
            text-align: center;
            color: #34495E;
            margin-bottom: 20px;
        }

        /* Search Bar */
        .search-bar {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        /* Table Styles */
        table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
            background-color: #fff;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #34495E;
            color: #fff;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        /* Button Styles */
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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.7);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #ffffff;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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

        /* Modal Form Styles */
        #editForm {
            display: flex;
            flex-direction: column;
        }

        #editForm input {
            margin: 10px 0;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        #editForm button {
            background-color: #2980B9;
            color: #fff;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        #editForm button:hover {
            background-color: #2471A3; /* Darker blue on hover */
        }

        /* Success Message */
        #successMessage {
            display: none;
            color: #28a745;
            text-align: center;
            margin-bottom: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Maintenance Task Management</h2>
    <a href="admin_dashboard.php" class="back-btn">Back to Dashboard</a>

    <input type="text" id="searchBar" placeholder="Search users..." class="search-bar">



    <table>
        <thead>
            <tr>
                <th>Task ID</th>
                <th>Task Name</th>
                <th>Estimated Time (hrs)</th>
                <th>Additional Details</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            include 'db_connect.php';
            $sql = "SELECT * FROM Maintenance_Tasks";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>{$row['task_id']}</td>
                        <td>{$row['task_name']}</td>
                        <td>{$row['estimated_time']}</td>
                        <td>{$row['additional_details']}</td>
                        
                        <td>
    <button class='btn btn-warning editBtn' data-id='{$row['task_id']}'
        data-name='{$row['task_name']}' data-time='{$row['estimated_time']}' 
        data-details='{$row['additional_details']}'>Edit</button>
    <button class='btn btn-danger deleteBtn' data-id='{$row['task_id']}'>Delete</button>
</td>

                    </tr>";
                }
            } else {
                echo "<tr><td colspan='5'>No tasks found</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>
</div>
<!-- Edit Task Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Edit Task</h2>
    

        <form id="editForm" action="update_task.php" method="POST">
            <input type="hidden" id="editTaskId" name="task_id">
            <label>Task Name:</label>
            <input type="text" id="editTaskName" name="task_name" required>
            <label>Estimated Time (hrs):</label>
            <input type="number" id="editEstimatedTime" name="estimated_time" required>
            <label>Additional Details:</label>
            <input type="text" id="editAdditionalDetails" name="additional_details">
            <button type="submit" class="btn btn-warning">Update Task</button>
        </form>
    </div>
</div>
<!-- Add this inside the modal -->
<div id="successMessage" style="display:none; color: green; text-align: center; margin-bottom: 10px;">
    Task updated successfully!
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let modal = document.getElementById("editModal");
        let closeModal = document.querySelector(".close");
        let editButtons = document.querySelectorAll(".editBtn");
        let taskId = document.getElementById("editTaskId");
        let taskName = document.getElementById("editTaskName");
        let estimatedTime = document.getElementById("editEstimatedTime");
        let additionalDetails = document.getElementById("editAdditionalDetails");
        let successMessage = document.getElementById("successMessage");
        let editForm = document.getElementById("editForm");

        editButtons.forEach(button => {
            button.addEventListener("click", function() {
                taskId.value = this.getAttribute("data-id");
                taskName.value = this.getAttribute("data-name");
                estimatedTime.value = this.getAttribute("data-time");
                additionalDetails.value = this.getAttribute("data-details");
                modal.style.display = "block";
                successMessage.style.display = "none"; // Hide previous success message
            });
        });

        closeModal.addEventListener("click", function() {
            modal.style.display = "none";
        });

        window.addEventListener("click", function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        });

        // Handle form submission via AJAX
        editForm.addEventListener("submit", function(event) {
            event.preventDefault(); // Prevent full page reload

            let formData = new FormData(editForm);

            fetch('update_task.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json()) // Expect JSON response
            .then(data => {
                if (data.success) {
                    successMessage.style.display = "block"; // Show success message
                    setTimeout(() => {
                        modal.style.display = "none";
                        location.reload(); // Reload table after update
                    }, 2000);
                } else {
                    alert("Error updating task. Please try again.");
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });

    document.addEventListener("DOMContentLoaded", function() {
    let deleteButtons = document.querySelectorAll(".deleteBtn");

    deleteButtons.forEach(button => {
        button.addEventListener("click", function() {
            let taskId = this.getAttribute("data-id");

            if (confirm("Are you sure you want to delete this task?")) {
                fetch('delete_task.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'task_id=' + taskId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Task deleted successfully!");
                        location.reload();
                    } else {
                        alert("Error deleting task. Please try again.");
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    });
});

document.getElementById("searchInput").addEventListener("keyup", function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll("#taskTable tr");
        
        rows.forEach(row => {
            let taskName = row.querySelector(".task-name").textContent.toLowerCase();
            if (taskName.includes(filter)) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    });

</script>




</body>
</html>
