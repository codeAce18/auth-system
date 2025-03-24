<?php
// Database connection
$host = "dpg-cvfi2jdsvqrc73d1smig-a.oregon-postgres.render.com";
$db_user = "auth_database_7zod_user";
$db_pass = "bce9EugCZQutsSveRgHybEMKZCvvv8HA";
$db_name = "auth_database_7zod";
$port = 5432;

try {
    // DSN (Data Source Name)
    $dsn = "pgsql:host=$host;port=$port;dbname=$db_name";

    // Create a new PDO instance
    $conn = new PDO($dsn, $db_user, $db_pass);

    // Set error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get the JSON data from the frontend
    $data = json_decode(file_get_contents('php://input'), true);

    $userId = $data['user_id'];
    $videoId = $data['video_id'];
    $startTime = $data['start_time'];
    $endTime = $data['end_time'];
    $duration = $data['duration'];

    // Log the video watch session
    $sql = "INSERT INTO video_watch_logs (user_id, video_id, start_time, end_time, duration) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId, $videoId, $startTime, $endTime, $duration]);

    // Award XP points if the user watched for at least 5 minutes (300 seconds)
    if ($duration >= 300) {
        $xpPoints = 5;

        // Update the user's XP points
        $sql = "UPDATE users SET xp_points = xp_points + ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$xpPoints, $userId]);

        echo json_encode(["status" => "success", "message" => "User awarded 5 XP points!"]);
    } else {
        echo json_encode(["status" => "info", "message" => "User did not watch long enough to earn XP."]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}

// Close the connection
$conn = null;
?>