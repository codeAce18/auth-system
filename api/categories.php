<?php
// api/categories.php - Get all categories
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Include your existing database configuration
require_once '../config.php';

try {
    $stmt = $conn->prepare("SELECT name FROM categories ORDER BY name");
    $stmt->execute();
    
    $categories = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categories[] = $row['name'];
    }
    
    echo json_encode($categories);
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