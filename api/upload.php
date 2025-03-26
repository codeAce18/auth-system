<?php
// upload.php - Handles the video upload process
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://h4-p.vercel.app'); 
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

// Define upload directories (relative to this file)
define('UPLOAD_DIR', '../uploads/videos/');
define('THUMBNAIL_DIR', '../uploads/thumbnails/');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
    $error = $_FILES['video']['error'] ?? 'No file uploaded';
    echo json_encode(['success' => false, 'message' => 'Upload failed: ' . $error]);
    exit;
}

// Create directories if they don't exist
if (!file_exists(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
if (!file_exists(THUMBNAIL_DIR)) mkdir(THUMBNAIL_DIR, 0755, true);

// Process the uploaded video
$videoFile = $_FILES['video'];
$videoName = basename($videoFile['name']);
$videoTmpPath = $videoFile['tmp_name'];
$videoSize = $videoFile['size'];
$videoType = $videoFile['type'];

// Generate a unique filename
$uniqueId = uniqid();
$extension = pathinfo($videoName, PATHINFO_EXTENSION);
$newFileName = $uniqueId . '.' . $extension;
$uploadPath = UPLOAD_DIR . $newFileName;

// Validate file type
$allowedTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
if (!in_array($videoType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed types: MP4, WebM, Ogg, QuickTime']);
    exit;
}

// Move the uploaded file
if (!move_uploaded_file($videoTmpPath, $uploadPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
    exit;
}

// Get form data
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$orientation = $_POST['orientation'] ?? 'Straight';
$privacy = $_POST['privacy'] ?? 'Public';
$monetize = $_POST['monetize'] ?? 'No';
$license = $_POST['license'] ?? 'Standard';
$language = $_POST['language'] ?? 'English';
$tags = $_POST['tags'] ?? '';
$performers = $_POST['cast'] ?? '';
$categories = $_POST['categories'] ?? '';

try {
    $conn->beginTransaction();

    // Store video information
    $sql = "INSERT INTO videos (
        file_name, original_name, file_path, file_size, file_type,
        title, description, orientation, privacy, monetize, 
        license, language, tags, performers, user_id
    ) VALUES (
        :file_name, :original_name, :file_path, :file_size, :file_type,
        :title, :description, :orientation, :privacy, :monetize,
        :license, :language, :tags, :performers, :user_id
    ) RETURNING id";

    $stmt = $conn->prepare($sql);
    
    // For testing, use user_id = 1. In production, use your auth system
    $userId = 1; // Replace with actual user ID from session
    
    $stmt->execute([
        ':file_name' => $newFileName,
        ':original_name' => $videoName,
        ':file_path' => $uploadPath,
        ':file_size' => $videoSize,
        ':file_type' => $videoType,
        ':title' => $title,
        ':description' => $description,
        ':orientation' => $orientation,
        ':privacy' => $privacy,
        ':monetize' => $monetize,
        ':license' => $license,
        ':language' => $language,
        ':tags' => $tags,
        ':performers' => $cast,
        ':user_id' => $userId
    ]);
    
    $videoId = $conn->lastInsertId();

    // Insert categories if provided
    if (!empty($categories)) {
        $categoriesArray = json_decode($categories, true) ?: [];
        
        foreach ($categoriesArray as $category) {
            // Check if category exists
            $checkStmt = $conn->prepare("SELECT id FROM categories WHERE name = :name");
            $checkStmt->execute([':name' => $category]);
            
            if ($checkStmt->rowCount() > 0) {
                $categoryId = $checkStmt->fetch(PDO::FETCH_ASSOC)['id'];
            } else {
                // Create new category
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $category));
                $createStmt = $conn->prepare("INSERT INTO categories (name, slug) VALUES (:name, :slug) RETURNING id");
                $createStmt->execute([':name' => $category, ':slug' => $slug]);
                $categoryId = $conn->lastInsertId();
            }
            
            // Insert relationship
            $relStmt = $conn->prepare("INSERT INTO video_categories (video_id, category_id) VALUES (:video_id, :category_id)");
            $relStmt->execute([':video_id' => $videoId, ':category_id' => $categoryId]);
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Video uploaded successfully',
        'data' => [
            'video_id' => $videoId,
            'file_name' => $newFileName,
            'file_path' => $uploadPath,
            'title' => $title
        ]
    ]);

} catch (PDOException $e) {
    $conn->rollBack();
    if (file_exists($uploadPath)) unlink($uploadPath);
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn = null;
exit;
?>