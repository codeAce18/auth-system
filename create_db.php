<?php
// create_db.php - Script to create the database and tables
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        reset_token VARCHAR(255),
        reset_token_expires TIMESTAMP,
        xp_points INT DEFAULT 0
    )";

    $conn->exec($sql);
    echo "Table 'users' created successfully<br>";

    // Create video_watch_logs table
    $sql = "CREATE TABLE IF NOT EXISTS video_watch_logs (
        id SERIAL PRIMARY KEY,
        user_id INT REFERENCES users(id) ON DELETE CASCADE,
        video_id INT,
        start_time TIMESTAMP,
        end_time TIMESTAMP,
        duration INT -- Duration in seconds
    )";

    $conn->exec($sql);
    echo "Table 'video_watch_logs' created successfully";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Close the connection
$conn = null;
?>