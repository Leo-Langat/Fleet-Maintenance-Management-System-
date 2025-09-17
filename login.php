<?php
session_start();
require_once 'utils/auto_logger.php';

$autoLogger = new AutoLogger();
//Leo-Langat the great
// Include the database connection file
require 'db_connect.php';

// Initialize error message
$error_message = '';

// Check for timeout message
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error_message = 'Your session has expired due to inactivity. Please log in again.';
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Prepare and bind
    $stmt = $conn->prepare("SELECT user_id, password, first_login, role FROM Users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $hashed_password, $first_login, $role);
        $stmt->fetch();
    
        if (password_verify($password, $hashed_password)) {
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            $_SESSION['user_id'] = $user_id; // Store user_id in session
    
            // Log successful login
            $autoLogger->logLogin($user_id, true);

            if ($first_login == 0) { // Check if first_login is 0
                $_SESSION['first_login'] = true;
                // Redirect to show the modal
                header("Location: login.php?show_modal=true");
                exit();
            } else {
                $_SESSION['first_login'] = false;
                // Update first_login to 1 after successful login if not already done
                $update_stmt = $conn->prepare("UPDATE Users SET first_login = 1 WHERE username = ?");
                $update_stmt->bind_param("s", $username);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Redirect to the appropriate dashboard based on role
                switch ($role) {
                    case 'admin':
                        header("Location: admin_dashboard.php");
                        break;
                    case 'driver':
                        header("Location: driver_dashboard.php");
                        break;
                    case 'mechanic':
                        header("Location: mechanic_dashboard.php");
                        break;
                    default:
                        // Fallback if role is not recognized
                        header("Location: login.php");
                        break;
                }
                exit();
            }
        } else {
            // Log failed login attempt
            $autoLogger->logLogin($user_id, false);
            $error_message = 'Invalid username or password.';
        }
    } else {
        $error_message = 'Invalid username or password.';
    }

    $stmt->close();
    $conn->close();
}

$show_modal = isset($_GET['show_modal']) && $_GET['show_modal'] === 'true';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fleet Maintenance Login</title>
    <!-- Font Awesome for the eye icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>


    body {
        background-image: url('truck.jpg'); /* Replace 'background.jpg' with your image file */
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    

        .input-group {
            position: relative;
        }

        .input-group input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .input-group .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }

        /* Modal styling */
        .modal {
            display: <?php echo $show_modal ? 'flex' : 'none'; ?>;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 200;
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.2);
        }

        .modal-header h3 {
            margin-bottom: 20px;
        }

        .modal-content input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .modal-content button {
            padding: 10px 20px;
            background-color:#2980B9;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .modal-content button:hover {
            background-color: #2471A3;
        }

        .modal-content .cancel-btn {
            background-color: #E74C3C;
        }

        .modal-content .cancel-btn:hover {
            background-color: #C0392B;
        }

        .login-container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    position: relative;
    left: -98px; /* Adjust this value to move it further left */
}

.back-home {
    text-align: center;
    margin-top: 10px;
}

.back-home-btn {
    display: inline-block;
    padding: 8px 16px;
    background-color: #34495E;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background-color 0.3s;
}

.back-home-btn:hover {
    background-color: #2C3E50;
}

.logo-container {
    text-align: center;
    margin-bottom: 20px;
}

.logo-container img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #2980B9;
    padding: 3px;
}

.login-box {
    background: rgba(255, 255, 255, 0.95);
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    width: 100%;
    max-width: 400px;
}

.login-box h2 {
    color: #2C3E50;
    text-align: center;
    margin-bottom: 25px;
    font-size: 24px;
    font-weight: 600;
}

    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="logo-container">
                <img src="logo.png" alt="Mutai Enterprises Logo">
            </div>
            <h2>Mutai Enterprises Limited</h2>
            <form action="login.php" method="POST" id="loginForm">
                <div class="input-group">
                    <label for="username"><strong>Username</strong></label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required autocomplete="username">
                </div>
                <div class="input-group">
                    <label for="password"><strong>Password</strong></label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                    <i class="fas fa-eye toggle-password"></i>
                </div>
                <div class="input-group">
                    <button type="submit">Login</button>
                </div>
                <div class="forgot-password">
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
                <div class="back-home">
                    <a href="index.php" class="back-home-btn">Back to Home</a>
                </div>
                <?php if (isset($error_message)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Password Change Modal -->
    <div class="modal" id="changePasswordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Change Your Password</h3>
            </div>
            <form id="changePasswordForm">
                <div class="input-group">
                    <input type="password" id="new_password" name="new_password" placeholder="New Password" required>
                    <i class="fas fa-eye toggle-password"></i>
                </div>
                <div class="input-group">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                    <i class="fas fa-eye toggle-password"></i>
                </div>
                <button type="submit">Change Password</button><br><br>
                <button type="button" class="cancel-btn" id="closeModalBtn">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        // Show the change password modal if required
        <?php if ($show_modal): ?>
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('changePasswordModal').style.display = 'flex';
        });

        // Close modal when "Cancel" button is clicked
        document.getElementById('closeModalBtn').addEventListener('click', () => {
            document.getElementById('changePasswordModal').style.display = 'none';
        });

        // Close modal when clicking outside the modal content
        window.addEventListener('click', (e) => {
            if (e.target === document.getElementById('changePasswordModal')) {
                document.getElementById('changePasswordModal').style.display = 'none';
            }
        });
        <?php endif; ?>

        // Toggle password visibility for both login and modal
        document.querySelectorAll('.toggle-password').forEach(eyeIcon => {
            eyeIcon.addEventListener('click', function () {
                const input = this.previousElementSibling;
                if (input.type === 'password') {
                    input.type = 'text';
                    this.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    input.type = 'password';
                    this.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        });

        // Handle password change form submission
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            // Strong password requirements
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;

            if (newPassword !== confirmPassword) {
                alert('Passwords do not match.');
                return;
            }

            if (!passwordRegex.test(newPassword)) {
                alert('Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.');
                return;
            }

            fetch('change_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `new_password=${encodeURIComponent(newPassword)}`
            })
            .then(response => response.text())
            .then(result => {
                if (result === 'success') {
                    alert('Password changed successfully. Please log in again.');
                    window.location.href = 'login.php'; // Redirect to login page after successful change
                } else {
                    alert('Error changing password.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred.');
            });
        });
    </script>
</body>
</html>
