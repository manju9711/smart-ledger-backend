<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$id = intval($data['id'] ?? 0);
$name = trim($data['name'] ?? '');

if (!$id || !$name) {
    echo json_encode(["status"=>false,"message"=>"ID & Name required"]);
    exit;
}

$sql = "UPDATE categories SET name='$name' WHERE id='$id'";

if ($conn->query($sql)) {
    echo json_encode(["status"=>true,"message"=>"Updated"]);
} else {
    echo json_encode(["status"=>false,"message"=>$conn->error]);
}
?>