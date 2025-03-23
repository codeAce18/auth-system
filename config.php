<?php
// config.php - Database configuration for PostgreSQL
$host = "dpg-cvfi2jdsvqrc73d1smig-a.oregon-postgres.render.com"; // Render database hostname
$db_user = "auth_database_7zod_user"; // Render database username
$db_pass = "bce9EugCZQutsSveRgHybEMKZCvvv8HA"; // Render database password
$db_name = "auth_database_7zod"; // Render database name
$port = 5432; // Default PostgreSQL port

try {
    // DSN (Data Source Name)
    $dsn = "pgsql:host=$host;port=$port;dbname=$db_name";

    // Create a new PDO instance
    $conn = new PDO($dsn, $db_user, $db_pass);

    // Set error mode to exception for better error handling
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to PostgreSQL database successfully!";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
