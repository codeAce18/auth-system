<?php
// video-processor.php - Process uploaded videos with FFmpeg
// This would typically be run as a background job or cron task

// Include your existing database configuration
require_once 'config.php';

// Define upload directories
define('UPLOAD_DIR', '../uploads/videos/');
define('THUMBNAIL_DIR', '../uploads/thumbnails/');

class VideoProcessor {
    private $ffmpegPath = '/usr/bin/ffmpeg'; // Update with your FFmpeg path
    private $ffprobePath = '/usr/bin/ffprobe'; // Update with your FFprobe path
    private $db;
    
    public function __construct($connection) {
        $this->db = $connection;
    }
    
    public function processUnprocessedVideos() {
        try {
            // Get videos that need processing (published_at is NULL)
            $stmt = $this->db->prepare("
                SELECT id, file_path, file_name 
                FROM videos 
                WHERE published_at IS NULL
                LIMIT 10
            ");
            
            $stmt->execute();
            
            $videos = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $videos[] = $row;
            }
            
            foreach ($videos as $video) {
                try {
                    $this->processVideo($video['id'], $video['file_path'], $video['file_name']);
                    echo "Processed video ID: " . $video['id'] . "\n";
                } catch (Exception $e) {
                    echo "Error processing video ID " . $video['id'] . ": " . $e->getMessage() . "\n";
                }
            }
        } catch (PDOException $e) {
            echo "Database error: " . $e->getMessage() . "\n";
        }
    }
    
    private function processVideo($videoId, $videoPath, $fileName) {
        // 1. Generate thumbnail
        $thumbnailPath = $this->generateThumbnail($videoPath, $fileName);
        
        // 2. Get video duration
        $duration = $this->getVideoDuration($videoPath);
        
        // 3. Generate different quality versions (optional)
        $this->generateVideoVariants($videoPath, $fileName);
        
        // 4. Update database
        $this->updateVideoRecord($videoId, $thumbnailPath, $duration);
    }
    
    private function generateThumbnail($videoPath, $fileName) {
        $baseFileName = pathinfo($fileName, PATHINFO_FILENAME);
        $thumbnailPath = THUMBNAIL_DIR . $baseFileName . '.jpg';
        
        // Take screenshot at 3 seconds into the video
        $command = sprintf(
            '%s -i %s -ss 00:00:03 -frames:v 1 %s',
            escapeshellarg($this->ffmpegPath),
            escapeshellarg($videoPath),
            escapeshellarg($thumbnailPath)
        );
        
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            throw new Exception("Failed to generate thumbnail");
        }
        
        return $thumbnailPath;
    }
    
    private function getVideoDuration($videoPath) {
        $command = sprintf(
            '%s -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s',
            escapeshellarg($this->ffprobePath),
            escapeshellarg($videoPath)
        );
        
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0 || empty($output)) {
            throw new Exception("Failed to get video duration");
        }
        
        // Return duration in seconds
        return (int)floatval($output[0]);
    }
    
    private function generateVideoVariants($videoPath, $fileName) {
        $baseFileName = pathinfo($fileName, PATHINFO_FILENAME);
        
        // Generate 720p version (if original is higher quality)
        $output720p = UPLOAD_DIR . $baseFileName . '_720p.mp4';
        $command720p = sprintf(
            '%s -i %s -vf scale=-1:720 -c:v libx264 -crf 23 -preset medium -c:a aac -b:a 128k %s',
            escapeshellarg($this->ffmpegPath),
            escapeshellarg($videoPath),
            escapeshellarg($output720p)
        );
        
        exec($command720p);
        
        // Generate 480p version
        $output480p = UPLOAD_DIR . $baseFileName . '_480p.mp4';
        $command480p = sprintf(
            '%s -i %s -vf scale=-1:480 -c:v libx264 -crf 23 -preset medium -c:a aac -b:a 128k %s',
            escapeshellarg($this->ffmpegPath),
            escapeshellarg($videoPath),
            escapeshellarg($output480p)
        );
        
        exec($command480p);
    }
    
    private function updateVideoRecord($videoId, $thumbnailPath, $duration) {
        try {
            // Update the video record with thumbnail, duration, and set it as published
            $stmt = $this->db->prepare("
                UPDATE videos
                SET 
                    thumbnail_path = :thumbnail_path,
                    duration = :duration,
                    published_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            
            $stmt->bindParam(':thumbnail_path', $thumbnailPath);
            $stmt->bindParam(':duration', $duration);
            $stmt->bindParam(':id', $videoId);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Failed to update video record");
            }
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
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

// Close the connection
$conn = null;
?>