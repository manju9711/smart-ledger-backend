<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$request_id = intval($data['request_id'] ?? 0);

if (!$request_id) {

    echo json_encode([
        "status" => false,
        "message" => "Request ID required"
    ]);

    exit;
}

$update = mysqli_query($conn, "

    UPDATE company_requests

    SET status='rejected'

    WHERE id='$request_id'
    AND status='pending'

");

if ($update) {

    echo json_encode([
        "status" => true,
        "message" => "Company request rejected"
    ]);

} else {

    echo json_encode([
        "status" => false,
        "message" => mysqli_error($conn)
    ]);
}