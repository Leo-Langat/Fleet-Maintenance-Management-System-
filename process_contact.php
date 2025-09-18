<?php
require_once 'utils/auto_logger.php';
require_once 'db_connect.php';

// Include PHPMailer library files
require 'C:/xampp/htdocs/FMMS/PHPMailer-master/src/Exception.php';
require 'C:/xampp/htdocs/FMMS/PHPMailer-master/src/PHPMailer.php';
require 'C:/xampp/htdocs/FMMS/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize logger
$logger = new AutoLogger();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);
    
    // Validate input
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required";
    }
    
    // If no errors, process the form
    if (empty($errors)) {
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'test@gmail.com';
            $mail->Password   = '**** **** **** ****';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('test@gmail.com', 'Mutai Enterprises Ltd');
            $mail->addAddress('test@gmail.com'); // Company email
            $mail->addReplyTo($email, $name);

            // Content
            $mail->isHTML(true);
            $mail->Subject = "New Contact Form Message from $name";
            $mail->Body    = "<h2>New Contact Form Submission</h2>
                             <p><strong>Name:</strong> $name</p>
                             <p><strong>Email:</strong> $email</p>
                             <p><strong>Message:</strong></p>
                             <p>" . nl2br(htmlspecialchars($message)) . "</p>";

            $mail->send();
            
            // Log successful contact form submission
            $logger->logSystemAction("Contact form submitted successfully", "Contact form submission from $name ($email)");
            
            // Redirect with success message
            header("Location: contact.php?status=success");
            exit();
        } catch (Exception $e) {
            // Log failed email sending
            $logger->logSystemAction("Failed to send contact form email", "Failed to send email from $name ($email): " . $mail->ErrorInfo);
            
            // Redirect with error message
            header("Location: contact.php?status=error");
            exit();
        }
    } else {
        // Log validation errors
        $logger->logSystemAction("Contact form validation failed", "Validation errors: " . implode(", ", $errors));
        
        // Redirect with error message
        header("Location: contact.php?status=error&errors=" . urlencode(implode(", ", $errors)));
        exit();
    }
} else {
    // If not POST request, redirect to contact page
    header("Location: contact.php");
    exit();
}
?> 