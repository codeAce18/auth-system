<?php
// process_login.php - With CORS support for React frontend

// Enable CORS
header("Access-Control-Allow-Origin: https://h4-porn.vercel.app"); // Change to frontend URL in production
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

// Process form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get form data - handle both form-data and JSON
    $contentType = $_SERVER["CONTENT_TYPE"] ?? '';

    if (stripos($contentType, 'application/json') !== false) {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
    }

    // Validate input
    if (!$email || !$password) {
        $response['message'] = "Both email and password are required";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = :email");
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->execute();

            if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (password_verify($password, $user['password'])) {
                    session_start();
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $user["id"];
                    $_SESSION["username"] = $user["username"];

                    $response = [
                        'success' => true,
                        'message' => "Login successful",
                        'username' => $user["username"],
                        'redirect' => "/"
                    ];
                } else {
                    $response['message'] = "Invalid password";
                }
            } else {
                $response['message'] = "No account found with that email";
            }
        } catch (PDOException $e) {
            $response['message'] = "Database error: " . $e->getMessage();
        }
    }
}

// Return JSON response
echo json_encode($response);
?>
