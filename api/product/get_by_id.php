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

$id = $data['id'] ?? $_GET['id'];

if(!$id){
    echo json_encode(["status"=>false,"message"=>"ID required"]);
    exit;
}

$sql = "SELECT * FROM products WHERE id='$id' AND is_deleted=0";
$result = $conn->query($sql);

if($result->num_rows > 0){
    echo json_encode(["status"=>true,"data"=>$result->fetch_assoc()]);
}else{
    echo json_encode(["status"=>false,"message"=>"Not found"]);
}
?>