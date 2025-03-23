<?php
// reset_password.php - With CORS support for React frontend

// Enable CORS
header("Access-Control-Allow-Origin: https://h4-porn.vercel.app"); // Replace * with your frontend URL in production
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Database connection
require_once 'config.php';
header('Content-Type: application/json');

// Initialize response array
$response = array('success' => false, 'message' => '');

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data - handle both form data and JSON
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? $_SERVER["CONTENT_TYPE"] : '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // Handle JSON request body
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        $token = isset($data['token']) ? trim($data['token']) : '';
        $password = isset($data['password']) ? trim($data['password']) : '';
        $confirmPassword = isset($data['confirmPassword']) ? trim($data['confirmPassword']) : '';
    } else {
        // Handle form-data request body
        $token = isset($_POST['token']) ? trim($_POST['token']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        $confirmPassword = isset($_POST['confirmPassword']) ? trim($_POST['confirmPassword']) : '';
    }
    
    // Basic validation
    if (empty($token) || empty($password) || empty($confirmPassword)) {
        $response['message'] = "All fields are required";
    } elseif ($password !== $confirmPassword) {
        $response['message'] = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $response['message'] = "Password must be at least 6 characters long";
    } else {
        // Check if token exists and is not expired
        $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 0) {
            $response['message'] = "Invalid or expired token. Please request a new password reset.";
        } else {
            $stmt->bind_result($userId);
            $stmt->fetch();
            $stmt->close();
            
            // Hash the new password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password and clear reset token
            $updateStmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
            $updateStmt->bind_param("si", $hashedPassword, $userId);
            
            if ($updateStmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Password has been reset successfully. You can now log in with your new password.";
            } else {
                $response['message'] = "Error: " . $updateStmt->error;
            }
            $updateStmt->close();
        }
    }
    
    // Close connection
    $conn->close();
}

// Verify token - for GET requests when user accesses the reset page
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['token'])) {
    $token = trim($_GET['token']);
    
    if (empty($token)) {
        $response['message'] = "Invalid token";
    } else {
        // Check if token exists and is not expired
        $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 0) {
            $response['message'] = "Invalid or expired token. Please request a new password reset.";
        } else {
            $response['success'] = true;
            $response['message'] = "Token is valid";
        }
        $stmt->close();
    }
    
    // Close connection
    $conn->close();
}

// Return JSON response
echo json_encode($response);
?>