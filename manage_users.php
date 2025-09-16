<?php
session_start(); // Ensure session is started at the very top
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <!-- Add SweetAlert2 CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        overflow: auto;
        border: 1px solid #ddd;
        border-radius: 5px;
        background-color: #fff;
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
        min-width: 120px;
    }

    thead th {
        background-color: #34495E;
        color: #fff;
        z-index: 2;
    }

    .status-button {
        padding: 5px 10px;
        border-radius: 5px;
        color: #fff;
        text-align: center;
    }

    .status-active {
        background-color: #28a745;
    }

    .status-inactive {
        background-color: #dc3545;
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
        max-width: 500px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
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

    #editUserForm {
        display: flex;
        flex-direction: column;
    }

    #editUserForm input,
    #editUserForm select {
        margin: 10px 0;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

    #editUserForm button {
        background-color: #2980B9;
        color: #fff;
        border: none;
        padding: 10px;
        border-radius: 5px;
        cursor: pointer;
    }

    #editUserForm button:hover {
        background-color: #2471A3;
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
</style>


</head>
<body>

<div class="container">
    <h2>User Management</h2>
    
    <a href="admin_dashboard.php" class="back-btn">Back to Dashboard</a>

    <input type="text" id="searchBar" placeholder="Search users..." class="search-bar">

    <div class="table-container">
    <table id="userTable">
        <thead>
            <tr>
                <th>User ID</th>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Phone</th>
                <th>DOB</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            include 'db_connect.php';
            $sql = "SELECT * FROM Users";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $statusClass = $row['status'] === 'active' ? 'status-active' : 'status-inactive';
                    echo "<tr>
                        <td>{$row['user_id']}</td>
                        <td>{$row['name']}</td>
                        <td>{$row['username']}</td>
                        <td>{$row['email']}</td>
                        <td>{$row['role']}</td>
                        <td>{$row['phone']}</td>
                        <td>{$row['dob']}</td>
                        <td><span class='status-button $statusClass'>{$row['status']}</span></td>
                        <td>
                            <button class='btn btn-warning editBtn' data-id='{$row['user_id']}'>Edit</button>
                            <button class='btn btn-danger deleteBtn' data-id='{$row['user_id']}'>Delete</button>
                        </td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='9' class='text-center'>No users found</td></tr>";
            }
            ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal" id="editUserModal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Edit User</h2>
        <form id="editUserForm" action="update_user.php" method="POST">
            <input type="hidden" name="user_id" id="editUserId">
            <input type="text" name="name" id="editUserName" placeholder="Name" required>
            <span id="nameError" style="color:red;"></span>
            
            <input type="text" name="username" id="editUserUsername" placeholder="Username" required>
            <span id="usernameError" style="color:red;"></span>
            
            <input type="email" name="email" id="editUserEmail" placeholder="Email" required>
            <span id="emailError" style="color:red;"></span>
            
            <select name="role" id="editUserRole" required>
                <option value="admin">Admin</option>
                <option value="driver">Driver</option>
                <option value="mechanic">Mechanic</option>
            </select>
            
            <input type="text" name="phone" id="editUserPhone" placeholder="Phone" required>
            <span id="phoneError" style="color:red;"></span>
            
            <input type="date" name="dob" id="editUserDob" required>
            <span id="dobError" style="color:red;"></span>
            
            <select name="status" id="editUserStatus" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            
            <button type="submit">Update User</button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Real-time validation functions
    function validateName(name) {
        const regex = /^[a-zA-Z\s]+$/; // Only letters and spaces allowed
        return regex.test(name);
    }

    function validateEmail(email) {
        const regex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
        return regex.test(email);
    }

    function validatePhone(phone) {
        const regex = /^[0-9]{10}$/; // 10-digit phone number
        return regex.test(phone);
    }

    function validateAge(dob) {
        const birthDate = new Date(dob);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDifference = today.getMonth() - birthDate.getMonth();
        if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        return age >= 18;
    }

    // Real-time input validation event listeners
    $("#editUserName").on("input", function() {
        const name = $(this).val();
        if (!validateName(name)) {
            $("#nameError").text("Name must contain only letters and spaces.");
        } else {
            $("#nameError").text("");
        }
    });

    $("#editUserEmail").on("input", function() {
        const email = $(this).val();
        if (!validateEmail(email)) {
            $("#emailError").text("Please enter a valid email address.");
        } else {
            $("#emailError").text("");
        }
    });

    $("#editUserPhone").on("input", function() {
        const phone = $(this).val();
        if (!validatePhone(phone)) {
            $("#phoneError").text("Phone number must be 10 digits.");
        } else {
            $("#phoneError").text("");
        }
    });

    $("#editUserDob").on("change", function() {
        const dob = $(this).val();
        if (!validateAge(dob)) {
            $("#dobError").text("User must be above 18 years old.");
        } else {
            $("#dobError").text("");
        }
    });

    // Real-time username uniqueness check
    $("#editUserUsername").on("input", function() {
        const username = $(this).val();
        const userId = $("#editUserId").val();
        if (username.length < 3 || !/^[a-zA-Z0-9_]{3,20}$/.test(username)) {
            $("#usernameError").text("Username must be 3-20 characters, letters, numbers, or underscores.");
            return;
        }
        $.get('get_user.php', { username_check: username, exclude_id: userId }, function(data) {
            const res = JSON.parse(data);
            if (res.taken) {
                $("#usernameError").text("This username is already taken.");
            } else {
                $("#usernameError").text("");
            }
        });
    });

    // Real-time phone uniqueness check
    $("#editUserPhone").on("input", function() {
        const phone = $(this).val();
        const userId = $("#editUserId").val();
        if (!/^\d{10}$/.test(phone)) {
            $("#phoneError").text("Phone number must be 10 digits.");
            return;
        }
        $.get('get_user.php', { phone_check: phone, exclude_id: userId }, function(data) {
            const res = JSON.parse(data);
            if (res.taken) {
                $("#phoneError").text("This phone number is already registered.");
            } else {
                $("#phoneError").text("");
            }
        });
    });

    // Search functionality
    $("#searchBar").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#userTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Open Edit User Modal
    $(".editBtn").on("click", function() {
        var userId = $(this).data("id");
        // Fetch user data and fill the form
        $.ajax({
            type: "GET",
            url: "get_user.php",
            data: { id: userId },
            success: function(user) {
                const data = JSON.parse(user);
                $("#editUserId").val(data.user_id);
                $("#editUserName").val(data.name);
                $("#editUserUsername").val(data.username);
                $("#editUserEmail").val(data.email);
                $("#editUserRole").val(data.role);
                $("#editUserPhone").val(data.phone);
                $("#editUserDob").val(data.dob);
                $("#editUserStatus").val(data.status);
                $("#editUserModal").show();
            }
        });
    });

    // Handle Edit User Form Submission
    $("#editUserForm").on("submit", function(e) {
        e.preventDefault();

        // Perform final validation before submission
        const name = $("#editUserName").val();
        const username = $("#editUserUsername").val();
        const usernameError = $("#usernameError").text();
        const email = $("#editUserEmail").val();
        const phone = $("#editUserPhone").val();
        const phoneError = $("#phoneError").text();
        const dob = $("#editUserDob").val();

        if (validateName(name) && usernameError === '' && username.length >= 3 && validateEmail(email) && phoneError === '' && validatePhone(phone) && validateAge(dob)) {
            // Submit form using regular form submission instead of AJAX
            this.submit();
        } else {
            alert("Please correct the form errors before submitting.");
        }
    });

    // Handle Delete User
    $(".deleteBtn").on("click", function() {
        var userId = $(this).data("id");
        if (confirm("Are you sure you want to delete this user?")) {
            $.ajax({
                type: "POST",
                url: "delete_user.php",
                data: { id: userId },
                success: function(response) {
                    alert(response);
                    location.reload(); // Refresh the page to remove the deleted user
                }
            });
        }
    });

    // Close modal
    $(".close").on("click", function() {
        $(this).closest(".modal").hide();
    });

    // Display success/error messages using SweetAlert2
    <?php if (isset($_SESSION['success'])): ?>
        Swal.fire({
            title: 'Success!',
            text: '<?php echo addslashes($_SESSION['success']); ?>',
            icon: 'success',
            confirmButtonColor: '#2980B9'
        });
        <?php unset($_SESSION['success']); // Clear the message after displaying
              ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        Swal.fire({
            title: 'Error!',
            text: '<?php echo addslashes($_SESSION['error']); ?>',
            icon: 'error',
            confirmButtonColor: '#dc3545' // Use a red color for errors
        });
        <?php unset($_SESSION['error']); // Clear the message after displaying
              ?>
    <?php endif; ?>
});
</script>
</body>
</html>