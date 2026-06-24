<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$id       = intval($data['id'] ?? 0);
$name     = trim($data['name'] ?? '');
$email    = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');

if (!$id || !$name || !$email) {

    echo json_encode([
        "status" => false,
        "message" => "Required fields missing"
    ]);

    exit;
}

$check = mysqli_query($conn, "

    SELECT id

    FROM users

    WHERE email='$email'
    AND id != '$id'

");

if (mysqli_num_rows($check) > 0) {

    echo json_encode([
        "status" => false,
        "message" => "Email already exists"
    ]);

    exit;
}

$password_query = "";

if (!empty($password)) {

    $hashed = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    $password_query = "
        , password='$hashed'
    ";
}

$sql = "

    UPDATE users

    SET
        name='$name',
        email='$email'
        $password_query

    WHERE id='$id'
    AND role='admin'

";

if ($conn->query($sql)) {

    echo json_encode([
        "status" => true,
        "message" => "Admin updated successfully"
    ]);

} else {

    echo json_encode([
        "status" => false,
        "message" => $conn->error
    ]);

}