<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
require_once("../../config/db.php");

// Get RAW JSON
$data = json_decode(file_get_contents("php://input"), true);

$username = $data['username'];
$email    = $data['email'];
$password = $data['password'];

// Validation
if (!$username || !$email || !$password) {
    echo json_encode(["status"=>"error", "message"=>"All fields required"]);
    exit;
}

// Check email exists
$check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
if (mysqli_num_rows($check) > 0) {
    echo json_encode(["status"=>"error", "message"=>"Email already exists"]);
    exit;
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert
$query = "INSERT INTO users (username, email, password) 
          VALUES ('$username', '$email', '$hashed_password')";

if (mysqli_query($conn, $query)) {
    echo json_encode(["status"=>"success", "message"=>"Registered successfully"]);
} else {
    echo json_encode(["status"=>"error", "message"=>"Something went wrong"]);
}
?>