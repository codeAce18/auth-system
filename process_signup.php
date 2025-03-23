<?php
// process_signup.php - With CORS support for React frontend

// Enable CORS
header("Access-Control-Allow-Origin: https://h4-porn.vercel.app"); // Replace * with your frontend URL in production
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Database connection
require_once 'config.php';  // Ensure config.php uses PDO
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

        $username = isset($data['username']) ? trim($data['username']) : '';
        $email = isset($data['email']) ? trim($data['email']) : '';
        $password = isset($data['password']) ? trim($data['password']) : '';
    } else {
        // Handle form-data request body
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    }

    // Basic validation
    if (empty($username) || empty($email) || empty($password)) {
        $response['message'] = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Invalid email format";
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->bindParam(":email", $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {  
                $response['message'] = "Email already exists. Please use a different email.";
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
                $stmt->bindParam(":username", $username);
                $stmt->bindParam(":email", $email);
                $stmt->bindParam(":password", $hashed_password);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = "Registration successful! You can now login.";
                } else {
                    $response['message'] = "Error: " . implode(" ", $stmt->errorInfo());
                }
            }
        } catch (PDOException $e) {
            $response['message'] = "Database error: " . $e->getMessage();
        }
    }
}

// Return JSON response
echo json_encode($response);
?>
