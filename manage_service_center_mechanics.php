<?php
session_start();
require_once 'db_connect.php';
require_once 'dashboard_functions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'assign') {
            $service_center_id = $_POST['service_center_id'];
            $mechanic_id = $_POST['mechanic_id'];
            // Check if mechanic is already assigned to any service center
            $check_mechanic = "SELECT COUNT(*) as assigned_count FROM service_center_mechanics WHERE mechanic_id = ?";
            $stmt = $conn->prepare($check_mechanic);
            $stmt->bind_param("i", $mechanic_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $assigned_count = $result->fetch_assoc()['assigned_count'];
            if ($assigned_count > 0) {
                $error = "This mechanic is already assigned to a service center.";
            } else {
                // Check if mechanic is already assigned to this service center (shouldn't happen, but for safety)
                $check_duplicate = "SELECT COUNT(*) as exists_count FROM service_center_mechanics 
                                  WHERE service_center_id = ? AND mechanic_id = ?";
                $stmt = $conn->prepare($check_duplicate);
                $stmt->bind_param("ii", $service_center_id, $mechanic_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $exists = $result->fetch_assoc()['exists_count'];
                if ($exists > 0) {
                    $error = "Mechanic is already assigned to this service center";
                } else {
                    $insert_query = "INSERT INTO service_center_mechanics (service_center_id, mechanic_id) VALUES (?, ?)";
                    $stmt = $conn->prepare($insert_query);
                    $stmt->bind_param("ii", $service_center_id, $mechanic_id);
                    if ($stmt->execute()) {
                        $success = "Mechanic assigned successfully";
                    } else {
                        $error = "Error assigning mechanic: " . $stmt->error;
                    }
                }
            }
        } elseif ($_POST['action'] === 'remove') {
            $assignment_id = $_POST['assignment_id'];
            
            $delete_query = "DELETE FROM service_center_mechanics WHERE service_center_mechanic_id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $assignment_id);
            
            if ($stmt->execute()) {
                $success = "Mechanic removed successfully";
            } else {
                $error = "Error removing mechanic: " . $stmt->error;
            }
        } elseif ($_POST['action'] === 'edit') {
            $assignment_id = $_POST['assignment_id'];
            $service_center_id = $_POST['service_center_id'];
            $mechanic_id = $_POST['mechanic_id'];
            // Check if mechanic is already assigned elsewhere (except this assignment)
            $check_mechanic = "SELECT COUNT(*) as assigned_count FROM service_center_mechanics WHERE mechanic_id = ? AND service_center_mechanic_id != ?";
            $stmt = $conn->prepare($check_mechanic);
            $stmt->bind_param("ii", $mechanic_id, $assignment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $assigned_count = $result->fetch_assoc()['assigned_count'];
            if ($assigned_count > 0) {
                $error = "This mechanic is already assigned to a service center.";
            } else {
                $update_query = "UPDATE service_center_mechanics SET service_center_id = ?, mechanic_id = ? WHERE service_center_mechanic_id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("iii", $service_center_id, $mechanic_id, $assignment_id);
                if ($stmt->execute()) {
                    $success = "Assignment updated successfully.";
                    echo '<script>window.location.reload();</script>';
                    exit;
                } else {
                    $error = "Error updating assignment: " . $stmt->error;
                }
            }
        }
    }
}

// Get all service centers
$service_centers_query = "SELECT service_center_id, service_center_name FROM Service_Centers ORDER BY service_center_name";
$service_centers = $conn->query($service_centers_query);

// Get all mechanics
$mechanics_query = "SELECT user_id, name FROM Users WHERE role = 'mechanic' ORDER BY name";
$mechanics = $conn->query($mechanics_query);

// Get current assignments
$assignments_query = "SELECT 
                        scm.service_center_mechanic_id,
                        sc.service_center_id,
                        sc.service_center_name,
                        u.name as mechanic_name,
                        scm.assigned_at
                     FROM service_center_mechanics scm
                     JOIN Service_Centers sc ON scm.service_center_id = sc.service_center_id
                     JOIN Users u ON scm.mechanic_id = u.user_id
                     ORDER BY sc.service_center_id, u.name";
$assignments = $conn->query($assignments_query);

// For search functionality, fetch all assignments into an array
$assignment_rows = [];
if ($assignments && $assignments->num_rows > 0) {
    while ($row = $assignments->fetch_assoc()) {
        $assignment_rows[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Centers Mechanics Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 98vw;
            margin: 30px auto;
            background: none;
        }
        h2 {
            text-align: center;
            margin-top: 30px;
            font-size: 2rem;
            font-weight: bold;
        }
        .back-btn {
            display: block;
            margin: 20px auto 30px auto;
            background: #2980B9;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 12px 32px;
            font-size: 1rem;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background 0.2s;
        }
        .back-btn:hover {
            background: #2471A3;
        }
        .search-bar {
            width: 100%;
            max-width: 700px;
            display: block;
            margin: 0 auto 20px auto;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }
        .assignments-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            margin: 0 auto 30px auto;
        }
        .assignments-table th, .assignments-table td {
            padding: 14px 12px;
            text-align: left;
        }
        .assignments-table th {
            background: #34495E;
            color: #fff;
            font-weight: bold;
        }
        .assignments-table tr {
            border-bottom: 1px solid #eee;
        }
        .assignments-table tr:nth-child(even) {
            background: #fafbfc;
        }
        .assignments-table tr:hover {
            background: #f1f1f1;
        }
        .edit-btn {
            background: #ffc107;
            color: #222;
            border: none;
            border-radius: 5px;
            padding: 7px 18px;
            font-size: 1rem;
            margin-right: 6px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .edit-btn:hover {
            background: #e0a800;
        }
        .remove-btn {
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 7px 18px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .remove-btn:hover {
            background: #c82333;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .assignment-form {
            background: #fff;
            padding: 20px 30px 10px 30px;
            border-radius: 8px;
            margin: 0 auto 30px auto;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            max-width: 700px;
        }
        .assignment-form h3 {
            margin-top: 0;
        }
        .assignment-form .form-group {
            margin-bottom: 18px;
        }
        .assignment-form label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
        }
        .assignment-form select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }
        .assignment-form button[type="submit"] {
            background: #2980B9;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 10px 24px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .assignment-form button[type="submit"]:hover {
            background: #2471A3;
        }
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.45);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 32px rgba(44, 62, 80, 0.18);
            max-width: 400px;
            width: 95vw;
            padding: 32px 28px 22px 28px;
            position: relative;
            animation: popIn 0.25s;
        }
        .modal-title {
            margin-top: 0;
            margin-bottom: 18px;
            font-size: 1.35rem;
            font-weight: bold;
            color: #34495E;
            text-align: center;
        }
        .modal-close {
            position: absolute;
            top: 14px;
            right: 18px;
            font-size: 28px;
            color: #888;
            cursor: pointer;
            transition: color 0.2s;
            font-weight: bold;
        }
        .modal-close:hover {
            color: #e74c3c;
        }
        .modal-card .form-group {
            margin-bottom: 20px;
        }
        .modal-card label {
            display: block;
            margin-bottom: 7px;
            font-weight: 500;
            color: #34495E;
        }
        .modal-card select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
            margin-bottom: 2px;
            transition: border 0.2s;
        }
        .modal-card select:focus {
            border: 1.5px solid #2980B9;
            outline: none;
        }
        .modal-submit-btn {
            width: 100%;
            background: #2980B9;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 12px 0;
            font-size: 1.08rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: background 0.2s;
        }
        .modal-submit-btn:hover {
            background: #2471A3;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes popIn {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Service Centers Mechanics Management</h2>
        <a href="admin_dashboard.php" class="back-btn">Back to Dashboard</a>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <div class="assignment-form">
            <h3>Assign New Mechanic</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="assign">
                <div class="form-group">
                    <label for="service_center_id">Service Center:</label>
                    <select name="service_center_id" id="service_center_id" required>
                        <option value="">Select Service Center</option>
                        <?php
                        $service_centers = $conn->query($service_centers_query);
                        while ($center = $service_centers->fetch_assoc()): ?>
                            <option value="<?php echo $center['service_center_id']; ?>">
                                <?php echo htmlspecialchars($center['service_center_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="mechanic_id">Mechanic:</label>
                    <select name="mechanic_id" id="mechanic_id" required>
                        <option value="">Select Mechanic</option>
                        <?php
                        $mechanics = $conn->query($mechanics_query);
                        while ($mechanic = $mechanics->fetch_assoc()): ?>
                            <option value="<?php echo $mechanic['user_id']; ?>">
                                <?php echo htmlspecialchars($mechanic['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit">Assign Mechanic</button>
            </form>
        </div>
        <input type="text" id="searchBar" class="search-bar" placeholder="Search service centers...">
        <table class="assignments-table" id="assignmentsTable">
            <thead>
                <tr>
                    <th>Service Center ID</th>
                    <th>Name</th>
                    <th>Assigned Mechanic</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="assignmentsTableBody">
                <?php if (count($assignment_rows) > 0): ?>
                    <?php foreach ($assignment_rows as $assignment): ?>
                        <tr data-assignment-id="<?php echo $assignment['service_center_mechanic_id']; ?>"
                            data-service-center-id="<?php echo $assignment['service_center_id']; ?>"
                            data-mechanic-name="<?php echo htmlspecialchars($assignment['mechanic_name']); ?>"
                            data-mechanic-id="<?php echo htmlspecialchars($assignment['mechanic_id'] ?? ''); ?>">
                            <td><?php echo htmlspecialchars($assignment['service_center_id']); ?></td>
                            <td><?php echo htmlspecialchars($assignment['service_center_name']); ?></td>
                            <td><?php echo htmlspecialchars($assignment['mechanic_name']); ?></td>
                            <td>
                                <button class="edit-btn" type="button" onclick="openEditModal(<?php echo $assignment['service_center_mechanic_id']; ?>, <?php echo $assignment['service_center_id']; ?>, '<?php echo htmlspecialchars(addslashes($assignment['mechanic_name'])); ?>')">Edit</button>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="assignment_id" value="<?php echo $assignment['service_center_mechanic_id']; ?>">
                                    <button type="submit" class="remove-btn" onclick="return confirm('Are you sure you want to remove this mechanic?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">No mechanics assigned to service centers</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Edit Modal -->
        <div id="editModal" class="modal-overlay">
            <div class="modal-card">
                <span class="modal-close" onclick="closeEditModal()">&times;</span>
                <h3 class="modal-title">Edit Assignment</h3>
                <form id="editAssignmentForm" method="POST" action="">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="assignment_id" id="edit_assignment_id">
                    <div class="form-group">
                        <label for="edit_service_center_id">Service Center:</label>
                        <select name="service_center_id" id="edit_service_center_id" required>
                            <option value="">Select Service Center</option>
                            <?php
                            $service_centers = $conn->query($service_centers_query);
                            while ($center = $service_centers->fetch_assoc()): ?>
                                <option value="<?php echo $center['service_center_id']; ?>">
                                    <?php echo htmlspecialchars($center['service_center_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_mechanic_id">Mechanic:</label>
                        <select name="mechanic_id" id="edit_mechanic_id" required>
                            <option value="">Select Mechanic</option>
                            <?php
                            $mechanics = $conn->query($mechanics_query);
                            while ($mechanic = $mechanics->fetch_assoc()): ?>
                                <option value="<?php echo $mechanic['user_id']; ?>">
                                    <?php echo htmlspecialchars($mechanic['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="modal-submit-btn">Update Assignment</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Search functionality for the assignments table
        document.getElementById('searchBar').addEventListener('keyup', function() {
            var value = this.value.toLowerCase();
            var rows = document.querySelectorAll('#assignmentsTable tbody tr');
            rows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                row.style.display = text.indexOf(value) > -1 ? '' : 'none';
            });
        });

        // Edit modal logic
        function openEditModal(assignmentId, serviceCenterId, mechanicName) {
            var modal = document.getElementById('editModal');
            modal.classList.add('active');
            document.getElementById('edit_assignment_id').value = assignmentId;
            document.getElementById('edit_service_center_id').value = serviceCenterId;
            // Mechanic will be set by user
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
    </script>
</body>
</html> 