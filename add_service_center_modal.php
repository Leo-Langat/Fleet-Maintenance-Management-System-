<?php
include 'db_connect.php';

// Fetch Maintenance Tasks for Dropdown
$query = "SELECT task_id, task_name FROM Maintenance_Tasks";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_center_name = trim($_POST['service_center_name']);
    $task_id = $_POST['task_id'];

    if (!empty($service_center_name) && !empty($task_id)) {
        // First check if the name already exists
        $check_query = "SELECT COUNT(*) as count FROM Service_Centers WHERE service_center_name = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $service_center_name);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            echo "<script>alert('Service center name already exists!'); window.history.back();</script>";
            exit;
        }
        
        $check_stmt->close();

        $query = "INSERT INTO Service_Centers (service_center_name, task_id) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $service_center_name, $task_id);
        
        if ($stmt->execute()) {
            echo "<script>alert('Service center added successfully!'); window.location.href='admin_dashboard.php';</script>";
        } else {
            echo "<script>alert('Error adding service center: " . $stmt->error . "'); window.history.back();</script>";
        }
        
        $stmt->close();
    }
}
$conn->close();
?>

<!-- Add Service Center Modal -->
<div id="serviceCenterModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5);">
    <div class="modal-content" style="background: #fff; padding: 20px; width: 40%; margin: 10% auto; border-radius: 8px;">
        <span class="close" style="float: right; cursor: pointer; font-size: 24px;">&times;</span>
        <h2>Add Service Center</h2>
        <form action="add_service_center_modal.php" method="POST" id="addServiceCenterForm">
            <div class="form-group">
                <label for="service_center_name">Service Center Name:</label>
                <input type="text" id="service_center_name" name="service_center_name" required>
                <div id="nameValidationMessage" style="color: #dc3545; margin-top: 5px; display: none;"></div>
            </div>
            
            <div class="form-group">
                <label for="task_id">Assign Maintenance Task:</label>
                <select id="task_id" name="task_id" required>
                    <option value="">Select a Task</option>
                    <?php foreach ($tasks as $task): ?>
                        <option value="<?php echo $task['task_id']; ?>"><?php echo htmlspecialchars($task['task_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" id="submitBtn">Add Service Center</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('serviceCenterModal');
    const addServiceBtn = document.getElementById('addServiceCenterBtn');
    const closeBtn = modal.querySelector('.close');
    const nameInput = document.getElementById('service_center_name');
    const validationMessage = document.getElementById('nameValidationMessage');
    const submitBtn = document.getElementById('submitBtn');
    let nameTimeout;

    if (addServiceBtn) {
        addServiceBtn.addEventListener('click', function (event) {
            event.preventDefault();
            modal.style.display = 'block';
        });
    }

    closeBtn.addEventListener('click', function () {
        modal.style.display = 'none';
    });

    window.addEventListener('click', function (event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Real-time name validation
    nameInput.addEventListener('input', function() {
        clearTimeout(nameTimeout);
        const name = this.value.trim();
        
        if (name.length === 0) {
            validationMessage.style.display = 'none';
            return;
        }

        nameTimeout = setTimeout(() => {
            fetch('check_service_center_name.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'service_center_name=' + encodeURIComponent(name)
            })
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    validationMessage.textContent = 'This service center name already exists';
                    validationMessage.style.display = 'block';
                } else {
                    validationMessage.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                validationMessage.textContent = 'Error checking name availability';
                validationMessage.style.display = 'block';
            });
        }, 500); // Debounce for 500ms
    });

    // Form submission validation
    document.getElementById('addServiceCenterForm').addEventListener('submit', function(e) {
        const name = nameInput.value.trim();
        const taskId = document.getElementById('task_id').value;
        
        if (!name || !taskId) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return;
        }
        
        if (validationMessage.style.display === 'block') {
            e.preventDefault();
            alert('Please fix the validation errors before submitting.');
            return;
        }
    });
});
</script>
