<?php
// check_session.php - Verify if user is logged in
session_start();

$response = array('loggedin' => false);

if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    $response['loggedin'] = true;
    $response['username'] = $_SESSION["username"];
}

header('Content-Type: application/json');
echo json_encode($response);
?>