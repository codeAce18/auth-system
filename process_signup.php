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
require_once 'config.php';
header('Content-Type: application/json');

// Initialize response array
$response = array('success' => false, 'message' => '');

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? $_SERVER["CONTENT_TYPE"] : '';

    if (strpos($contentType, 'application/json') !== false) {
        // Handle JSON request body
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $email = isset($data['email']) ? trim($data['email']) : '';
        $username = isset($data['username']) ? trim($data['username']) : '';
        $password = isset($data['password']) ? trim($data['password']) : '';
    } else {
        // Handle form-data request body
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    }

    // Validate input
    if (empty($email) || empty($username) || empty($password)) {
        $response['message'] = "All fields are required.";
    } else {
        try {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email"); 
            $stmt->bindParam(":email", $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $response['message'] = "Email already exists.";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (email, username, password) VALUES (:email, :username, :password)"); // ✅ Changed $pdo to $conn
                $stmt->bindParam(":email", $email);
                $stmt->bindParam(":username", $username);
                $stmt->bindParam(":password", $hashed_password);
                $stmt->execute();

                $response['success'] = true;
                $response['message'] = "Account created successfully!";
            }
        } catch (PDOException $e) {
            $response['message'] = "Database error: " . $e->getMessage();
        }
    }
}

// Return JSON response
echo json_encode($response);
?>
