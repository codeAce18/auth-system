<?php
// api/upload-status.php - Check upload status
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://h4-p.vercel.app');
header('Access-Control-Allow-Methods: GET');

require_once '../config.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Video ID is required']);
    exit;
}

$videoId = $_GET['id'];

try {
    $stmt = $conn->prepare("
        SELECT title, file_path, file_size, published_at, duration 
        FROM videos 
        WHERE id = :id
    ");
    $stmt->execute([':id' => $videoId]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Video not found']);
        exit;
    }
    
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
    $isProcessed = $video['published_at'] !== null;
    
    echo json_encode([
        'title' => $video['title'],
        'file_size' => $video['file_size'],
        'duration' => $video['duration'],
        'is_processed' => $isProcessed,
        'progress' => $isProcessed ? 100 : 75,
        'remaining_time' => $isProcessed ? 0 : '2:13 MIN'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$conn = null;
exit;
?>