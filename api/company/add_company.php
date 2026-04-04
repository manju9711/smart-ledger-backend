<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . '/../../config/db.php';

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Get values safely
$company_name    = $data['company_name'] ?? '';
$company_address = $data['company_address'] ?? '';
$company_code    = $data['company_code'] ?? '';
$gstin           = $data['gstin'] ?? '';
$phone           = $data['phone'] ?? '';
$logo            = $data['logo'] ?? '';

$upload_dir = __DIR__ . "/../uploads/";

// Create folder if not exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$logo_path = "";

// 🔥 Handle base64 image
if (!empty($logo)) {

    $image = base64_decode($logo);

    if ($image === false) {
        echo json_encode([
            "status" => false,
            "message" => "Invalid base64 image"
        ]);
        exit();
    }

    $file_name = time() . ".png";
    $full_path = $upload_dir . $file_name;

    if (file_put_contents($full_path, $image) === false) {
        echo json_encode([
            "status" => false,
            "message" => "Failed to save image"
        ]);
        exit();
    }

    // Save relative path to DB
    $logo_path = "uploads/" . $file_name;
}

// 🔥 Insert query
$sql = "INSERT INTO companies 
(company_name, company_address, company_code, gstin, phone, logo) 
VALUES 
('$company_name', '$company_address', '$company_code', '$gstin', '$phone', '$logo_path')";

// Execute
if ($conn->query($sql)) {
    echo json_encode([
        "status" => true,
        "message" => "Company Added",
        "logo" => $logo_path
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => $conn->error
    ]);
}
?>