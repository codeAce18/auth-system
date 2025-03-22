<?php
// config.php - Database configuration using environment variables
$host = getenv('DB_HOST'); 
$db_user = getenv('DB_USERNAME'); 
$db_pass = getenv('DB_PASSWORD'); 
$db_name = getenv('DB_NAME'); 

// Create connection
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>