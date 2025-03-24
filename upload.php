<?php
// upload.php - Handles the video upload process
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Include your existing database configuration
require_once 'config.php';

// Define upload directories
define('UPLOAD_DIR', '../uploads/videos/');
define('THUMBNAIL_DIR', '../uploads/thumbnails/');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests are allowed'
    ]);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERROR_OK) {
    $error = isset($_FILES['video']) ? $_FILES['video']['error'] : 'No file uploaded';
    echo json_encode([
        'success' => false,
        'message' => 'Upload failed: ' . $error
    ]);
    exit;
}

// Create directories if they don't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
if (!file_exists(THUMBNAIL_DIR)) {
    mkdir(THUMBNAIL_DIR, 0755, true);
}

// Process the uploaded video
$videoFile = $_FILES['video'];
$videoName = $videoFile['name'];
$videoTmpPath = $videoFile['tmp_name'];
$videoSize = $videoFile['size'];
$videoType = $videoFile['type'];

// Generate a unique filename to prevent overwrites
$uniqueId = uniqid();
$extension = pathinfo($videoName, PATHINFO_EXTENSION);
$newFileName = $uniqueId . '.' . $extension;
$uploadPath = UPLOAD_DIR . $newFileName;

// Validate file type
$allowedTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
if (!in_array($videoType, $allowedTypes)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid file type. Allowed types: MP4, WebM, Ogg, QuickTime'
    ]);
    exit;
}

// Move the uploaded file to the destination directory
if (!move_uploaded_file($videoTmpPath, $uploadPath)) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to move uploaded file'
    ]);
    exit;
}

// Get form data
$title = isset($_POST['title']) ? $_POST['title'] : '';
$description = isset($_POST['description']) ? $_POST['description'] : '';
$orientation = isset($_POST['orientation']) ? $_POST['orientation'] : 'Straight';
$privacy = isset($_POST['privacy']) ? $_POST['privacy'] : 'Public';
$monetize = isset($_POST['monetize']) ? $_POST['monetize'] : 'No';
$license = isset($_POST['license']) ? $_POST['license'] : 'Standard';
$language = isset($_POST['language']) ? $_POST['language'] : 'English';
$tags = isset($_POST['tags']) ? $_POST['tags'] : '';
$cast = isset($_POST['cast']) ? $_POST['cast'] : '';
$categories = isset($_POST['categories']) ? $_POST['categories'] : '';

// Parse JSON for categories if it's a JSON string
if (is_string($categories)) {
    $categories = json_decode($categories, true);
}
if (!is_array($categories)) {
    $categories = [];
}

try {
    // Begin transaction
    $conn->beginTransaction();

    // Store video information in the database
    $sql = "INSERT INTO videos (
        file_name, original_name, file_path, file_size, file_type,
        title, description, orientation, privacy, monetize, 
        license, language, tags, cast, upload_date, user_id
    ) VALUES (
        :file_name, :original_name, :file_path, :file_size, :file_type,
        :title, :description, :orientation, :privacy, :monetize,
        :license, :language, :tags, :cast, CURRENT_TIMESTAMP, :user_id
    ) RETURNING id";

    $stmt = $conn->prepare($sql);

    // Get user ID from session or authentication (using 1 as default for testing)
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

    $stmt->bindParam(':file_name', $newFileName);
    $stmt->bindParam(':original_name', $videoName);
    $stmt->bindParam(':file_path', $uploadPath);
    $stmt->bindParam(':file_size', $videoSize);
    $stmt->bindParam(':file_type', $videoType);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':orientation', $orientation);
    $stmt->bindParam(':privacy', $privacy);
    $stmt->bindParam(':monetize', $monetize);
    $stmt->bindParam(':license', $license);
    $stmt->bindParam(':language', $language);
    $stmt->bindParam(':tags', $tags);
    $stmt->bindParam(':cast', $cast);
    $stmt->bindParam(':user_id', $userId);

    $stmt->execute();
    $videoId = $conn->lastInsertId();

    // Insert categories if provided
    if (!empty($categories)) {
        foreach ($categories as $category) {
            // Check if category exists, if not create it
            $checkSql = "SELECT id FROM categories WHERE name = :name";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bindParam(':name', $category);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                $categoryRow = $checkStmt->fetch(PDO::FETCH_ASSOC);
                $categoryId = $categoryRow['id'];
            } else {
                // Create new category
                $createSql = "INSERT INTO categories (name, slug) VALUES (:name, :slug) RETURNING id";
                $createStmt = $conn->prepare($createSql);
                $slug = strtolower(str_replace(' ', '-', $category));
                $createStmt->bindParam(':name', $category);
                $createStmt->bindParam(':slug', $slug);
                $createStmt->execute();
                $categoryId = $conn->lastInsertId();
            }
            
            // Insert the video-category relationship
            $relSql = "INSERT INTO video_categories (video_id, category_id) VALUES (:video_id, :category_id)";
            $relStmt = $conn->prepare($relSql);
            $relStmt->bindParam(':video_id', $videoId);
            $relStmt->bindParam(':category_id', $categoryId);
            $relStmt->execute();
        }
    }

    // Commit transaction
    $conn->commit();

    // Return success response
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
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Delete the uploaded file if database insert fails
    if (file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

// Close the connection
$conn = null;
exit;
?>