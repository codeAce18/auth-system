<?php
// forgot_password.php - With CORS support for React frontend

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure this path is correct

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Database connection
require_once 'config.php';
header('Content-Type: application/json');

$response = array('success' => false, 'message' => '');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    $email = isset($data['email']) ? trim($data['email']) : '';

    if (empty($email)) {
        $response['message'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Invalid email format";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 0) {
            // Do not reveal whether email exists for security reasons
            $response['success'] = true;
            $response['message'] = "If your email exists, a reset link has been sent.";
        } else {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
            $updateStmt->bind_param("sss", $token, $expires, $email);

            if ($updateStmt->execute()) {
                $baseUrl = "https://h4-porn.vercel.app";
                $resetLink = $baseUrl . "/auth/reset-password?token=" . $token;

                // **PHPMailer Configuration**
                $mail = new PHPMailer(true);

                try {
                    // SMTP Settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com'; // Change to your SMTP server
                    $mail->SMTPAuth = true;
                    $mail->Username = 'oluwatofunmijoel765@gmail.com'; // Your Gmail
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
                        $response['message'] = "Mailer Error: " . $mail->ErrorInfo;
                    }
                } catch (Exception $e) {
                    $response['message'] = "Mailer Error: " . $mail->ErrorInfo;
                }
            } else {
                $response['message'] = "Error: " . $updateStmt->error;
            }
            $updateStmt->close();
        }
        $stmt->close();
    }
    $conn->close();
}

echo json_encode($response);
?>
