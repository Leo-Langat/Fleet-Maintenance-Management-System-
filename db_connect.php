<?php
$servername = "localhost"; 
$username = "root";        
$password = "";            
$dbname = "fleet_management"; 

// Set timezone
date_default_timezone_set('Africa/Nairobi');

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set MySQL timezone
$conn->query("SET time_zone = '+03:00'");

