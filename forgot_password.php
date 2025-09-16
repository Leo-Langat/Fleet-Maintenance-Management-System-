<?php 
session_start();

// Include the database connection file
require 'db_connect.php';

// Include PHPMailer library files
require 'C:/xampp/htdocs/FMMS/PHPMailer-master/src/Exception.php';
require 'C:/xampp/htdocs/FMMS/PHPMailer-master/src/PHPMailer.php';
require 'C:/xampp/htdocs/FMMS/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';

    if (empty($email)) {
        $_SESSION['message'] = "Email address is required.";
        header("Location: forgot_password.php");
        exit();
    }

    // Check if the email exists in the database
    $checkEmailQuery = "SELECT * FROM Users WHERE email = ?";
    $stmt = $conn->prepare($checkEmailQuery);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Email exists, proceed to reset password
        function generateRandomPassword($length = 6) {
            $lowercase = 'abcdefghijklmnopqrstuvwxyz';
            $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $numbers = '0123456789';
            $specialCharacters = '!@#$%^&*()-_=+[]{};:,.<>?';

            $characters = $lowercase . $uppercase . $numbers . $specialCharacters;
            $password = '';
            $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
            $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
            $password .= $numbers[random_int(0, strlen($numbers) - 1)];
            $password .= $specialCharacters[random_int(0, strlen($specialCharacters) - 1)];

            for ($i = 4; $i < $length; $i++) {
                $password .= $characters[random_int(0, strlen($characters) - 1)];
            }

            return str_shuffle($password);
        }

        function sendEmail($to, $password) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'lionellangat2000@gmail.com';
                $mail->Password   = 'srsl vbwk afvc wwbs'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->setFrom('lionellangat2000@gmail.com', 'Mutai Enterprises Ltd');
                $mail->addAddress($to); 
                $mail->isHTML(true);  
                $mail->Subject = 'Password Reset';
                $mail->Body    = "<p>Hello,</p>
                                  <p>Your password has been reset successfully. Here are your new login details:</p>
                                  <p>Email: $to</p>
                                  <p>Password: <strong>$password</strong></p>
                                  <p>Please change your password after your first login.</p>";
                $mail->send();
            } catch (Exception $e) {
                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        }

        // Generate a new password
        $new_password = generateRandomPassword();
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        // Update the password and reset the first_login field
        $updatePasswordQuery = "UPDATE Users SET password = ?, first_login = 0 WHERE email = ?";
        $stmt = $conn->prepare($updatePasswordQuery);
        $stmt->bind_param("ss", $hashed_password, $email);

        if ($stmt->execute()) {
            // Send email with the new password
            sendEmail($email, $new_password);
            $_SESSION['message'] = "A new password has been sent to your email address.";
        } else {
            $_SESSION['message'] = "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        // Email does not exist in the database
        $_SESSION['message2'] = "Email address not found.";
    }

    $conn->close();
    header("Location: forgot_password.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Mutai Enterprises</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #2C3E50, #3498DB);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .forgot-password-container {
            width: 100%;
            max-width: 450px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .forgot-password-box {
            padding: 40px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-container img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3498DB;
            padding: 3px;
        }

        h2 {
            color: #2C3E50;
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
        }

        .input-group {
            margin-bottom: 25px;
            position: relative;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #34495E;
            font-size: 14px;
            font-weight: 500;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #E0E0E0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #F8F9FA;
        }

        .input-group input:focus {
            border-color: #3498DB;
            background: #FFFFFF;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .input-group i {
            position: absolute;
            right: 15px;
            top: 40px;
            color: #95A5A6;
        }

        button[type="submit"] {
            width: 100%;
            padding: 14px;
            background: #3498DB;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        button[type="submit"]:hover {
            background: #2980B9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        button[type="submit"]:active {
            transform: translateY(0);
        }

        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }

        .back-to-login a {
            color: #3498DB;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: color 0.3s ease;
        }

        .back-to-login a:hover {
            color: #2980B9;
        }

        .back-to-login i {
            font-size: 12px;
        }

        /* Loading animation for button */
        .loading {
            position: relative;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Responsive adjustments */
        @media (max-width: 480px) {
            .forgot-password-box {
                padding: 30px 20px;
            }

            h2 {
                font-size: 24px;
            }

            .input-group input {
                padding: 10px 12px;
            }
        }
    </style>
</head>
<body>
    <div class="forgot-password-container">
        <div class="forgot-password-box">
            <div class="logo-container">
                <img src="logo.png" alt="Mutai Enterprises Logo">
            </div>
            <h2>Forgot Password</h2>
            <form action="forgot_password.php" method="POST" id="forgotPasswordForm">
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your registered email" required>
                    <i class="fas fa-envelope"></i>
                </div>
                <button type="submit" id="submitBtn">
                    <i class="fas fa-paper-plane"></i>
                    Send Reset Link
                </button>
            </form>
            <div class="back-to-login">
                <a href="login.php">
                    <i class="fas fa-arrow-left"></i>
                    Back to Login
                </a>
            </div>
        </div>
    </div>

    <script>
        // Form submission handling
        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = 'Processing...';
        });

        // SweetAlert messages
        <?php if (isset($_SESSION['message'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: "<?php echo $_SESSION['message']; ?>",
                confirmButtonText: 'OK',
                confirmButtonColor: '#3498DB'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php';
                }
            });
            <?php unset($_SESSION['message']); ?>
        <?php elseif (isset($_SESSION['message2'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: "<?php echo $_SESSION['message2']; ?>",
                confirmButtonText: 'OK',
                confirmButtonColor: '#3498DB'
            });
            <?php unset($_SESSION['message2']); ?>
        <?php endif; ?>

        // Input validation
        const emailInput = document.getElementById('email');
        emailInput.addEventListener('input', function() {
            if (this.validity.valid) {
                this.style.borderColor = '#2ECC71';
            } else {
                this.style.borderColor = '#E74C3C';
            }
        });
    </script>
</body>
</html>
