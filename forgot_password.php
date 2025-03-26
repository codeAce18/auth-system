<?php
// forgot_password.php - With CORS support for React frontend

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; 

// Enable CORS
header("Access-Control-Allow-Origin: https://h4-p.vercel.app");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Database connection
require_once 'config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Get request body (JSON format)
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $email = trim($data['email'] ?? '');

        // Validate email
        if (empty($email)) {
            throw new Exception("Email is required.");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        // Check if the email exists in the database
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            // Mask email existence for security reasons
            throw new Exception("If your email exists, a reset link has been sent.");
        }

        // Generate reset token & expiry
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Update reset token in the database
        $updateStmt = $conn->prepare("UPDATE users SET reset_token = :token, reset_token_expires = :expires WHERE email = :email");
        $updateStmt->bindParam(":token", $token);
        $updateStmt->bindParam(":expires", $expires);
        $updateStmt->bindParam(":email", $email);
        $updateStmt->execute();

        // Generate password reset link
        $baseUrl = "https://h4-porn.vercel.app";
        $resetLink = $baseUrl . "/auth/reset-password?token=" . $token;

        // **PHPMailer Configuration**
        $mail = new PHPMailer(true);

        try {
            // SMTP Settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; 
            $mail->SMTPAuth = true;
            $mail->Username = 'oluwatofunmijoel765@gmail.com'; 
            $mail->Password = 'vezf uias gdto kjpv';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            // Email Headers
            $mail->setFrom('oluwatofunmijoel765@gmail.com', 'Porn4Hub'); 
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = "Password Reset Request";
            $mail->Body = "
                <html>
                <head><title>Password Reset</title></head>
                <body>
                <h2>Password Reset Request</h2>
                <p>Click the link below to reset your password:</p>
                <p><a href='$resetLink'>Reset Password here</a></p>
                <p>This link expires in 1 hour.</p>
                </body>
                </html>
            ";

            // Send Email
            if ($mail->send()) {
                $response['success'] = true;
                $response['message'] = "A reset link has been sent to your email successfully.";
            } else {
                throw new Exception("Mailer Error: " . $mail->ErrorInfo);
            }
        } catch (Exception $e) {
            throw new Exception("Mailer Error: " . $mail->ErrorInfo);
        }
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?>
