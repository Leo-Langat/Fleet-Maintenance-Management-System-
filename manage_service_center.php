<?php
include 'db_connect.php';

$sql = "SELECT 
    sc.service_center_id, 
    sc.service_center_name, 
    mt.task_name
FROM Service_Centers sc
LEFT JOIN Maintenance_Tasks mt ON sc.task_id = mt.task_id";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Service Centers Management</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f4f4f4;
    }
    .container {
      width: 90%;
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
      max-height: 500px;
      overflow-y: auto;
      border: 1px solid #ddd;
      background-color: #fff;
      padding: 10px;
    }
    table {
      width: 100%;
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
    .modal {
      display: none;
      position: fixed;
      z-index: 1;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.7);
      padding-top: 60px;
    }
    .modal-content {
      background-color: #ffffff;
      margin: 5% auto;
      padding: 20px;
      border: 1px solid #888;
      width: 80%;
      max-width: 600px;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }
    .close:hover,
    .close:focus {
      color: black;
      text-decoration: none;
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
      background-color: #ffc107;
    }
    .btn-warning:hover {
      background-color: #e0a800;
    }
    .btn-danger {
      background-color: #dc3545;
    }
    .btn-danger:hover {
      background-color: #c82333;
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
      background-color: #2471A3;
    }
    select {
      width: 100%;
      padding: 8px;
      margin: 8px 0;
      box-sizing: border-box;
    }
    input[type="text"] {
      width: 100%;
      padding: 8px;
      margin: 8px 0;
      box-sizing: border-box;
    }

    .modal-content {
  background-color: #ffffff;
  margin: auto;
  padding: 25px;
  border-radius: 10px;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
  animation: fadeIn 0.3s ease-in-out;
}

.close {
  color: #333;
  float: right;
  font-size: 24px;
  font-weight: bold;
  cursor: pointer;
}

.close:hover {
  color: #e74c3c;
}

.modal-content h2 {
  text-align: center;
  margin-bottom: 20px;
  font-size: 22px;
  color: #2c3e50;
}

input[type="text"], select {
  width: 100%;
  padding: 10px;
  margin: 10px 0;
  border: 1px solid #ccc;
  border-radius: 5px;
  font-size: 16px;
}

button[type="submit"] {
  width: 100%;
  background-color: #2471A3;
  color: white;
  padding: 12px;
  font-size: 16px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  margin-top: 15px;
  transition: background 0.3s;
}

button[type="submit"]:hover {
  background-color: #2471A3;
}

@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.9); }
  to { opacity: 1; transform: scale(1); }
}


  </style>
</head>
<body>

<div class="container">
  <h2>Service Centers Management</h2>
  <a href="admin_dashboard.php" class="back-btn">Back to Dashboard</a>
  <input type="text" id="searchBar" placeholder="Search service centers..." class="search-bar">
  <table id="serviceCenterTable">
    <thead>
      <tr>
        <th>Service Center ID</th>
        <th>Name</th>
        <th>Assigned Task</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
      if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
              echo "<tr>
                  <td>{$row['service_center_id']}</td>
                  <td>{$row['service_center_name']}</td>
                  <td>{$row['task_name']}</td>
                  <td>
                      <button class='btn btn-warning editBtn' data-id='{$row['service_center_id']}'>Edit</button>
                      <button class='btn btn-danger deleteBtn' data-id='{$row['service_center_id']}'>Delete</button>
                  </td>
              </tr>";
          }
      } else {
          echo "<tr><td colspan='4' class='text-center'>No service centers found</td></tr>";
      }
      ?>
    </tbody>
  </table>
</div>

<div class="modal" id="editServiceCenterModal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h2>Edit Service Center</h2>
    <form id="editServiceCenterForm" action="update_service_center.php" method="POST">
      <input type="hidden" name="service_center_id" id="editCenterId">
      <div class="form-group">
        <label for="editCenterName">Service Center Name:</label>
        <input type="text" name="service_center_name" id="editCenterName" required>
        <div id="editNameValidationMessage" style="color: #dc3545; margin-top: 5px; display: none;"></div>
      </div>
      <div class="form-group">
        <label for="editCenterTask">Maintenance Task:</label>
        <select name="task_name" id="editCenterTask" required>
          <option value="">Select a Task</option>
          <?php
          $tasks_query = "SELECT task_id, task_name FROM Maintenance_Tasks";
          $tasks_result = $conn->query($tasks_query);
          while ($task = $tasks_result->fetch_assoc()): ?>
            <option value="<?php echo htmlspecialchars($task['task_name']); ?>">
              <?php echo htmlspecialchars($task['task_name']); ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <button type="submit" id="editSubmitBtn">Update Center</button>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<script>
    $(document).ready(function() {
    let nameTimeout;
    const editNameInput = document.getElementById('editCenterName');
    const editValidationMessage = document.getElementById('editNameValidationMessage');
    const editSubmitBtn = document.getElementById('editSubmitBtn');

    // Open edit modal and prefill data
    $(document).on("click", ".editBtn", function() {
        var centerId = $(this).data("id");
        $("#editServiceCenterModal").show();
        $("#editCenterId").val(centerId);

        // Fetch service center details via AJAX
        $.ajax({
            url: 'fetch_service_center.php',
            type: 'POST',
            data: { service_center_id: centerId },
            dataType: 'json',
            success: function(response) {
                if (!response.error) {
                    $("#editCenterName").val(response.service_center_name);
                    $("#editCenterTask").val(response.task_name);
                    editValidationMessage.style.display = 'none';
                    editSubmitBtn.disabled = false;
                } else {
                    alert("Service center not found.");
                }
            }
        });
    });

    // Real-time name validation for edit modal
    editNameInput.addEventListener('input', function() {
        clearTimeout(nameTimeout);
        const name = this.value.trim();
        const centerId = document.getElementById('editCenterId').value;
        
        if (name.length === 0) {
            editValidationMessage.style.display = 'none';
            editSubmitBtn.disabled = true;
            return;
        }

        nameTimeout = setTimeout(() => {
            fetch('check_service_center_name.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `service_center_name=${encodeURIComponent(name)}&current_id=${centerId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    editValidationMessage.textContent = 'This service center name already exists';
                    editValidationMessage.style.display = 'block';
                    editSubmitBtn.disabled = true;
                } else {
                    editValidationMessage.style.display = 'none';
                    editSubmitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                editValidationMessage.textContent = 'Error checking name availability';
                editValidationMessage.style.display = 'block';
                editSubmitBtn.disabled = true;
            });
        }, 500); // Debounce for 500ms
    });

    // Close modal
    $(".close").click(function() {
        $(".modal").hide();
    });

    // Handle delete action
    $(document).on("click", ".deleteBtn", function() {
        var centerId = $(this).data("id");
        if (confirm("Are you sure you want to delete this service center?")) {
            $.ajax({
                url: 'delete_service_center.php',
                type: 'POST',
                data: { service_center_id: centerId },
                success: function(response) {
                    alert(response);
                    location.reload();
                }
            });
        }
    });

    // Search functionality
    $("#searchBar").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#serviceCenterTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Form submission validation
    $("#editServiceCenterForm").on("submit", function(e) {
        if (editSubmitBtn.disabled) {
            e.preventDefault();
            alert('Please fix the validation errors before submitting.');
        }
    });
});

$(document).on("submit", "#editServiceCenterForm", function(e) {
    e.preventDefault();
    
    $.ajax({
        url: 'update_service_center.php',
        type: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            console.log("Server Response:", response); // Debugging
            if (response.trim() === "success") {
                alert("Service center updated successfully!");
                location.reload();
            } else {
                alert("Update failed: " + response);
            }
        }
    });
});

    </script>
</body>
</html>
