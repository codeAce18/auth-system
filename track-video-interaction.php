<?php
// Include database configuration
require_once 'config.php';

// CORS Headers
header("Access-Control-Allow-Origin: https://h4-p.vercel.app");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Ensure only POST requests are processed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Receive POST data
$input = json_decode(file_get_contents('php://input'), true);

$username = $input['username'] ?? null;
$videoId = $input['videoId'] ?? null;
$duration = $input['duration'] ?? 0;

if (!$username || !$videoId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing username or video ID']);
    exit;
}

try {
    // Use $conn from config.php instead of creating a new connection
    
    // First, get the user ID
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    $userId = $user['id'];

    // Check if video interaction already logged for XP
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM video_watch_logs 
        WHERE user_id = :user_id 
        AND video_id = :video_id 
        AND duration >= 300 
        AND start_time >= NOW() - INTERVAL '24 hours'
    ");
    $stmt->execute([
        'user_id' => $userId, 
        'video_id' => $videoId
    ]);
    $existingLog = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingLog['count'] > 0) {
        echo json_encode(['xpAwarded' => false, 'message' => 'XP already awarded for this video today']);
        exit;
    }

    // Log video watch
    $stmt = $conn->prepare("
        INSERT INTO video_watch_logs 
        (user_id, video_id, start_time, end_time, duration) 
        VALUES (:user_id, :video_id, NOW(), NOW() + INTERVAL '5 minutes', :duration)
    ");
    $stmt->execute([
        'user_id' => $userId,
        'video_id' => $videoId,
        'duration' => $duration
    ]);

    // Update user XP
    $stmt = $conn->prepare("
        UPDATE users 
        SET xp_points = xp_points + 5 
        WHERE id = :user_id
    ");
    $stmt->execute(['user_id' => $userId]);

    echo json_encode([
        'xpAwarded' => true, 
        'xpPoints' => 5, 
        'message' => 'XP awarded successfully'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>