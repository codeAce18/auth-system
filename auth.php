<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: https://h4-porn.vercel.app");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

// Mock database
$users = [
    'user@example.com' => [
        'id' => 1,
        'password' => '$2y$10$hashedpassword' 
    ]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['email']) && isset($data['password'])) {
        if (isset($users[$data['email']]) && password_verify($data['password'], $users[$data['email']]['password'])) {
            $_SESSION['user_id'] = $users[$data['email']]['id'];
            echo json_encode([
                'status' => 'success',
                'user' => [
                    'id' => $users[$data['email']]['id'],
                    'email' => $data['email']
                ]
            ]);
            exit;
        }
    }
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid credentials']);
} 
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
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