<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$admin_id     = intval($data['admin_id'] ?? 0);
$old_password = trim($data['old_password'] ?? '');
$new_password = trim($data['new_password'] ?? '');

if (!$admin_id || !$old_password || !$new_password) {
    echo json_encode([
        "status" => false,
        "message" => "All fields are required"
    ]);
    exit;
}

/* Get Admin */

$res = mysqli_query(
    $conn,
    "SELECT password
     FROM users
     WHERE id='$admin_id'
     AND role IN ('admin','cashier')"
);

if (mysqli_num_rows($res) == 0) {

    echo json_encode([
        "status" => false,
        "message" => "Admin not found"
    ]);
    exit;

}

$row = mysqli_fetch_assoc($res);

/* Verify Old Password */

if (!password_verify($old_password, $row['password'])) {

    echo json_encode([
        "status" => false,
        "message" => "Old password is incorrect"
    ]);
    exit;

}

/* Update Password */

$newHash = password_hash($new_password, PASSWORD_DEFAULT);

mysqli_query(
    $conn,
    "UPDATE users
     SET password='$newHash'
     WHERE id='$admin_id'"
);

echo json_encode([
    "status" => true,
    "message" => "Password updated successfully"
]);

?>