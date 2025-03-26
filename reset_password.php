<?php
// reset_password.php - With CORS support for React frontend

// Enable CORS
header("Access-Control-Allow-Origin: https://h4-p.vercel.app"); // Replace with frontend URL in production
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
$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Get form data (handle both JSON and form-data)
        $contentType = $_SERVER["CONTENT_TYPE"] ?? '';

        if (strpos($contentType, 'application/json') !== false) {
            // Handle JSON request body
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
        } else {
            // Handle form-data request body
            $data = $_POST;
        }

        // Extract and sanitize inputs
        $token = trim($data['token'] ?? '');
        $password = trim($data['password'] ?? '');
        $confirmPassword = trim($data['confirmPassword'] ?? '');

        // Basic validation
        if (empty($token) || empty($password) || empty($confirmPassword)) {
            throw new Exception("All fields are required.");
        }
        if ($password !== $confirmPassword) {
            throw new Exception("Passwords do not match.");
        }
        if (strlen($password) < 6) {
            throw new Exception("Password must be at least 6 characters long.");
        }

        // Check if token exists and is still valid
        $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = :token AND reset_token_expires > NOW()");
        $stmt->bindParam(":token", $token);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            throw new Exception("Invalid or expired token. Please request a new password reset.");
        }

        // Fetch user ID
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $userId = $user['id'];

        // Hash the new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Update the user's password and clear reset token
        $updateStmt = $conn->prepare("UPDATE users SET password = :password, reset_token = NULL, reset_token_expires = NULL WHERE id = :id");
        $updateStmt->bindParam(":password", $hashedPassword);
        $updateStmt->bindParam(":id", $userId);

        if ($updateStmt->execute()) {
            // Destroy any existing session to force logout after password reset
            session_start();
            session_destroy();

            $response['success'] = true;
            $response['message'] = "Password has been reset successfully. You can now log in with your new password.";
        } else {
            throw new Exception("Error updating password.");
        }
    }

    // Verify token for GET requests (when user accesses the reset page)
    if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['token'])) {
        $token = trim($_GET['token']);

        if (empty($token)) {
            throw new Exception("Invalid token.");
        }

        $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = :token AND reset_token_expires > NOW()");
        $stmt->bindParam(":token", $token);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            throw new Exception("Invalid or expired token. Please request a new password reset.");
        } else {
            $response['success'] = true;
            $response['message'] = "Token is valid.";
        }
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?>
