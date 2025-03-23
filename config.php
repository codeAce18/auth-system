<?php
// config.php - Database configuration
$host = "localhost";
$db_user = "root"; // default XAMPP username
$db_pass = ""; // default XAMPP password is empty
$db_name = "user_auth";

// Create connection
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>