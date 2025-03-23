<?php
// process_login.php - With CORS support for React frontend

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
        
        $email = isset($data['email']) ? trim($data['email']) : '';
        $password = isset($data['password']) ? trim($data['password']) : '';
    } else {
        // Handle form-data request body
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    }
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $response['message'] = "Both email and password are required";
    } else {
        // Prepare SQL statement
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Password is correct, start a new session
                session_start();
                
                // Store data in session variables
                $_SESSION["loggedin"] = true;
                $_SESSION["id"] = $user["id"];
                $_SESSION["username"] = $user["username"];
                
                $response['success'] = true;
                $response['message'] = "Login successful";
                $response['username'] = $user["username"];
                $response['redirect'] = "/"; // For React Router
            } else {
                $response['message'] = "Invalid password";
            }
        } else {
            $response['message'] = "No account found with that email";
        }
        
        $stmt->close();
    }
    
    // Close connection
    $conn->close();
}

// Return JSON response
echo json_encode($response);
?>