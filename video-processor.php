<?php
// video-processor.php - Process uploaded videos with FFmpeg
require_once 'config.php';

// Define upload directories
define('UPLOAD_DIR', 'uploads/videos/');
define('THUMBNAIL_DIR', 'uploads/thumbnails/');

class VideoProcessor {
    private $ffmpegPath = '/usr/bin/ffmpeg'; // Update this path
    private $ffprobePath = '/usr/bin/ffprobe'; // Update this path
    private $db;
    
    public function __construct($connection) {
        $this->db = $connection;
    }
    
    public function processUnprocessedVideos() {
        try {
            $stmt = $this->db->prepare("
                SELECT id, file_path, file_name 
                FROM videos 
                WHERE published_at IS NULL
                LIMIT 5
            ");
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                try {
                    $this->processVideo($row['id'], $row['file_path'], $row['file_name']);
                    echo "Processed video ID: " . $row['id'] . "\n";
                } catch (Exception $e) {
                    echo "Error processing video ID " . $row['id'] . ": " . $e->getMessage() . "\n";
                }
            }
        } catch (PDOException $e) {
            echo "Database error: " . $e->getMessage() . "\n";
        }
    }
    
    private function processVideo($videoId, $videoPath, $fileName) {
        $thumbnailPath = $this->generateThumbnail($videoPath, $fileName);
        $duration = $this->getVideoDuration($videoPath);
        $this->updateVideoRecord($videoId, $thumbnailPath, $duration);
    }
    
    private function generateThumbnail($videoPath, $fileName) {
        $baseFileName = pathinfo($fileName, PATHINFO_FILENAME);
        $thumbnailPath = THUMBNAIL_DIR . $baseFileName . '.jpg';
        
        $command = sprintf(
            '%s -i %s -ss 00:00:03 -vframes 1 %s 2>&1',
            escapeshellarg($this->ffmpegPath),
            escapeshellarg($videoPath),
            escapeshellarg($thumbnailPath)
        );
        
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0 || !file_exists($thumbnailPath)) {
            throw new Exception("Failed to generate thumbnail: " . implode("\n", $output));
        }
        
        return $thumbnailPath;
    }
    
    private function getVideoDuration($videoPath) {
        $command = sprintf(
            '%s -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>&1',
            escapeshellarg($this->ffprobePath),
            escapeshellarg($videoPath)
        );
        
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0 || empty($output)) {
            throw new Exception("Failed to get video duration: " . implode("\n", $output));
        }
        
        return (int)round(floatval($output[0]));
    }
    
    private function updateVideoRecord($videoId, $thumbnailPath, $duration) {
        $stmt = $this->db->prepare("
            UPDATE videos
            SET thumbnail_path = :thumbnail_path,
                duration = :duration,
                published_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':thumbnail_path' => $thumbnailPath,
            ':duration' => $duration,
            ':id' => $videoId
        ]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Failed to update video record");
        }
    }
}

// Run the processor
try {
    $processor = new VideoProcessor($conn);
    $processor->processUnprocessedVideos();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$conn = null;
?>