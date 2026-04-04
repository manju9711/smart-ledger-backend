<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . "/../../config/db.php";

// Get RAW JSON
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Check JSON valid ah?
if (!$data) {
    echo json_encode(["status" => false, "message" => "Invalid JSON"]);
    exit;
}

// Validate fields
if (
    !isset($data['product_name']) ||
    !isset($data['price']) ||
    !isset($data['stock']) ||
    !isset($data['gst_percentage'])
) {
    echo json_encode(["status" => false, "message" => "Missing fields"]);
    exit;
}

// Assign values
$name = $data['product_name'];
$price = $data['price'];
$stock = $data['stock'];
$gst = $data['gst_percentage'];
$barcode = isset($data['barcode']) ? $data['barcode'] : "";

// Insert query
$sql = "INSERT INTO products 
(product_name, price, stock, gst_percentage, barcode) 
VALUES ('$name', '$price', '$stock', '$gst', '$barcode')";

if ($conn->query($sql)) {
    echo json_encode(["status" => true, "message" => "Product Added"]);
} else {
    echo json_encode(["status" => false, "message" => "Insert Failed"]);
}
?>