<?php
header("Content-Type: application/json");

// Allow requests from http://localhost:3001
header("Access-Control-Allow-Origin: http://localhost:3000");

// Allow credentials (cookies, authorization headers)
header("Access-Control-Allow-Credentials: true");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Allow these HTTP methods
    header("Access-Control-Allow-Methods: POST, OPTIONS");

    // Allow these headers
    header("Access-Control-Allow-Headers: Content-Type");

    // Cache the preflight response for 1 hour
    header("Access-Control-Max-Age: 3600");

    // Exit early for preflight requests
    exit(0);
}

session_start();

// Destroy the session
if (session_destroy()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to destroy session"]);
}
?>