<?php
session_start();
require_once 'db_connect.php';
require_once 'utils/auto_logger.php';

// Log the logout action if user is logged in
if (isset($_SESSION['user_id'])) {
    $logger = new AutoLogger();
    $logger->logLogout($_SESSION['user_id']);
}

// Unset all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>
