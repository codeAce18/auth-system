<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: https://h4-porn.vercel.app");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

// 1. Verify session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

// 2. Get input data
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['user_id'], $data['video_id'], $data['duration'])) {
    http_response_code(400);
    die(json_encode(['status' => 'error', 'message' => 'Invalid data']));
}

// 3. Verify user matches session
if ($_SESSION['user_id'] != $data['user_id']) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Forbidden']));
}

// Include database configuration
require_once 'config.php';


try {
    // Get and validate JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data || !isset($data['user_id'], $data['video_id'], $data['duration'])) {
        throw new Exception("Invalid input data");
    }

    $userId = (int)$data['user_id'];
    $videoId = (int)$data['video_id'];
    $duration = (int)$data['duration'];
    $awardedAt = $data['awarded_at'] ?? date('Y-m-d H:i:s');

    // Calculate XP points (5 XP per 5 minutes)
    $xpPoints = floor($duration / 300) * 5;

    if ($xpPoints > 0) {
        // Log the XP award
        $stmt = $conn->prepare("INSERT INTO video_watch_logs (user_id, video_id, duration, xp_awarded, awarded_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $videoId, $duration, $xpPoints, $awardedAt]);

        // Update the user's XP points
        $stmt = $conn->prepare("UPDATE users SET xp_points = xp_points + ? WHERE id = ?");
        $stmt->execute([$xpPoints, $userId]);

        echo json_encode([
            "status" => "success", 
            "message" => "User awarded {$xpPoints} XP points!",
            "xp_awarded" => $xpPoints
        ]);
    } else {
        // Log watch time without XP award
        $stmt = $conn->prepare("INSERT INTO video_watch_logs (user_id, video_id, duration) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $videoId, $duration]);
        
        echo json_encode([
            "status" => "info", 
            "message" => "User did not watch long enough to earn XP.",
            "current_watch_time" => $duration
        ]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error", 
        "message" => $e->getMessage()
    ]);
}

// Note: Don't close the connection here since it was created in config.php
// $conn = null; // Remove this line
?>