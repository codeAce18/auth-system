<?php
// create_db.php - Script to create the database and users table
// Run this script once to set up your database

$host = "dpg-cvfi2jdsvqrc73d1smig-a.oregon-postgres.render.com"; // Render PostgreSQL hostname
$db_user = "auth_database_7zod_user"; // Render PostgreSQL username
$db_pass = "bce9EugCZQutsSveRgHybEMKZCvvv8HA"; // Render PostgreSQL password
$db_name = "auth_database_7zod"; // Render PostgreSQL database name
$port = 5432; // PostgreSQL default port

try {
    // DSN (Data Source Name)
    $dsn = "pgsql:host=$host;port=$port;dbname=$db_name";

    // Create a new PDO instance
    $conn = new PDO($dsn, $db_user, $db_pass);

    // Set error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to PostgreSQL successfully<br>";

    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY, -- Automatically handles auto-increment
        username VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $conn->exec($sql);
    echo "Table 'users' created successfully";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Close the connection
$conn = null;
?>
