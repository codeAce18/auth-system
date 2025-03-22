<?php
// config.php - PostgreSQL database configuration
$host = getenv('DB_HOST'); // Render will provide this
$db_user = getenv('DB_USERNAME'); // Render will provide this
$db_pass = getenv('DB_PASSWORD'); // Render will provide this
$db_name = getenv('DB_NAME'); // Render will provide this

// Create connection
$conn = new PDO("pgsql:host=$host;dbname=$db_name", $db_user, $db_pass);

// Check connection
if (!$conn) {
    die("Connection failed");
}
?>