<?php
require_once 'config.php'; // Your existing database configuration

// Secure session settings
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => 'h4-porn.vercel.app',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax' // Changed from 'None' for better security
]);
session_start();

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: https://h4-porn.vercel.app");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Input validation
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        exit(json_encode(['status' => 'error', 'message' => 'Invalid email format']));
    }

    try {
        // 1. Query your real users table
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = :email");
        $stmt->bindParam(':email', $data['email']);
        $stmt->execute();
        
        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // 2. Verify password against stored hash
            if (password_verify($data['password'], $user['password'])) {
                session_regenerate_id(true); // Security measure
                $_SESSION['user_id'] = $user['id'];
                
                echo json_encode([
                    'status' => 'success',
                    'user' => ['id' => $user['id']]
                ]);
            } else {
                http_response_code(401);
                echo json_encode(['status' => 'error', 'message' => 'Invalid credentials']);
            }
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
}
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Session check endpoint
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'status' => 'success',
            'user' => ['id' => $_SESSION['user_id']]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    }
}
?>