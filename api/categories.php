<?php
// api/categories.php - Get all categories
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://h4-p.vercel.app');
header('Access-Control-Allow-Methods: GET');

require_once '../config.php';

try {
    $stmt = $conn->query("SELECT name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    echo json_encode($categories);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$conn = null;
exit;
?>