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

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['id'])) {
    echo json_encode(["status"=>false,"message"=>"Invalid data"]);
    exit;
}

$id = $data['id'];
$name = $data['product_name'];
$price = $data['price'];
$stock = $data['stock'];
$gst = $data['gst_percentage'];
$barcode = $data['barcode'] ?? "";

$sql = "UPDATE products SET 
product_name='$name',
price='$price',
stock='$stock',
gst_percentage='$gst',
barcode='$barcode'
WHERE id='$id'";

if($conn->query($sql)){
    echo json_encode(["status"=>true,"message"=>"Updated"]);
}else{
    echo json_encode(["status"=>false,"message"=>"Update Failed"]);
}
?>