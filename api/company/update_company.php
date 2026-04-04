<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
include __DIR__ . '/../../config/db.php';

// JSON header
header("Content-Type: application/json");

// get raw JSON
$raw = file_get_contents("php://input");

// convert JSON → PHP array
$data = json_decode($raw, true);

// check data
if (!$data) {
    echo json_encode([
        "status" => false,
        "message" => "Invalid JSON input"
    ]);
    exit;
}

// assign values
$id = $data['id'] ?? '';
$company_name = $data['company_name'] ?? '';
$company_address = $data['company_address'] ?? '';
$company_code = $data['company_code'] ?? '';
$gstin = $data['gstin'] ?? '';
$phone = $data['phone'] ?? '';
$logo = $data['logo'] ?? '';

// validation
if (empty($id)) {
    echo json_encode([
        "status" => false,
        "message" => "ID is required"
    ]);
    exit;
}

$logo_query = "";

// handle base64 logo
if (!empty($logo)) {

    $image = base64_decode($logo);

    $file_name = time() . ".png";

    $upload_dir = __DIR__ . "/../uploads/";
    $full_path = $upload_dir . $file_name;

    file_put_contents($full_path, $image);

    $db_path = "uploads/" . $file_name;

    $logo_query = ", logo='$db_path'";
}

// update query
$sql = "UPDATE companies SET 
company_name='$company_name',
company_address='$company_address',
company_code='$company_code',
gstin='$gstin',
phone='$phone'
$logo_query
WHERE id='$id'";

// execute
if ($conn->query($sql)) {
    echo json_encode([
        "status" => true,
        "message" => "Updated Successfully"
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => $conn->error
    ]);
}
?>