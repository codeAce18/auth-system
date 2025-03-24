<?php
// api/upload-status.php - Check upload status
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Include your existing database configuration
require_once '../config.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Video ID is required'
    ]);
    exit;
}

$videoId = $_GET['id'];

try {
    $stmt = $conn->prepare("
        SELECT title, file_path, file_size, published_at, duration 
        FROM videos 
        WHERE id = :id
    ");
    $stmt->bindParam(':id', $videoId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Video not found'
        ]);
        exit;
    }
    
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If the video is published, it's fully processed
    $isProcessed = $video['published_at'] !== null;
    $progress = $isProcessed ? 100 : 75; // Example: 75% if still processing
    
    echo json_encode([
        'success' => true,
        'data' => [
            'title' => $video['title'],
            'file_size' => $video['file_size'],
            'duration' => $video['duration'],
            'is_processed' => $isProcessed,
            'progress' => $progress,
            'remaining_time' => $isProcessed ? 0 : '2:13 MIN' // Example
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

// Close the connection
$conn = null;
exit;
?>