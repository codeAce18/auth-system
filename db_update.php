<?php
// db_update.php
require_once 'config.php'; // Include your database connection

$sql = "ALTER TABLE users 
        ADD COLUMN reset_token VARCHAR(255) DEFAULT NULL,
        ADD COLUMN reset_token_expires DATETIME DEFAULT NULL";

if ($conn->query($sql) === TRUE) {
    echo "Database updated successfully";
} else {
    echo "Error updating database: " . $conn->error;
}

$conn->close();
?>