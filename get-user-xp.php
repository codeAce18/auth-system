<?php
// get-user-xp.php

// Include database configuration
require_once 'config.php';

// CORS Headers
header("Access-Control-Allow-Origin: https://h4-p.vercel.app");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Ensure only GET requests are processed
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Get username from query parameters
$username = $_GET['username'] ?? null;

if (!$username) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing username']);
    exit;
}

try {
    // Get user XP points
    $stmt = $conn->prepare("SELECT xp_points FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    echo json_encode([
        'xpPoints' => intval($user['xp_points'])
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>