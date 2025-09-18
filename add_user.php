<?php
session_start();
require_once 'utils/auto_logger.php';

// Include the database connection file
include 'db_connect.php';

// Include PHPMailer library files
require 'C:/xampp/htdocs/FMMS/PHPMailer-master/src/Exception.php';
require 'C:/xampp/htdocs/FMMS/PHPMailer-master/src/PHPMailer.php';
require 'C:/xampp/htdocs/FMMS/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$autoLogger = new AutoLogger();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $dob = $_POST['dob'] ?? ''; 

    if (empty($name) || empty($username) || empty($email) || empty($role) || empty($phone) || empty($dob)) {
        $_SESSION['message'] = "All fields are required.";
        header("Location: index.php");
        exit();
    }

    // Check if the email already exists in the database
    $checkEmailQuery = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($checkEmailQuery);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Email already exists
        $_SESSION['message2'] = "The email address is already registered.";
        header("Location: admin_dashboard.php");
        exit();
    }

    // Check if the username already exists in the database
    $checkUsernameQuery = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($checkUsernameQuery);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $_SESSION['message2'] = "The username is already taken.";
        header("Location: admin_dashboard.php");
        exit();
    }

    // Check if the phone number already exists in the database
    $checkPhoneQuery = "SELECT * FROM users WHERE phone = ?";
    $stmt = $conn->prepare($checkPhoneQuery);
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $_SESSION['message2'] = "The phone number is already registered.";
        header("Location: admin_dashboard.php");
        exit();
    }

    // Generate a random password
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

    function sendEmail($to, $name, $username, $password) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'test@gmail.com';
            $mail->Password   = '**** **** **** ****'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->setFrom('test@gmail.com', 'Mutai Enterprises Ltd');
            $mail->addAddress($to); 
            $mail->isHTML(true);  
            $mail->Subject = 'Your New Account Password';
            $mail->Body    = "<p>Hello, $name,</p>
                              <p>Your account has been created successfully. Here are your login details:</p>
                              <p>Username: <strong>$username</strong></p>
                              <p>Email: $to</p>
                              <p>Password: <strong>$password</strong></p>
                              <p>Please change your password after your first login.</p>";
            $mail->send();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }

    $password = generateRandomPassword();
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert new user if email and username do not exist
    $sql = "INSERT INTO users (name, username, email, password, role, phone, dob, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sssssss", $name, $username, $email, $hashed_password, $role, $phone, $dob);

        if ($stmt->execute()) {
            sendEmail($email, $name, $username, $password);
            $_SESSION['message'] = "User added successfully. An email has been sent to the user.";
            $autoLogger->logUserCreation($_SESSION['user_id'], $conn->insert_id, $name);
        } else {
            $_SESSION['message'] = "Error: " . $stmt->error;
            $autoLogger->logError($_SESSION['user_id'], "Failed to create user: " . $_SESSION['message']);
        }

        $stmt->close();
    }

    $conn->close();
    header("Location: admin_dashboard.php");
    exit();
}

