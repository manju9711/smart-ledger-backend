<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$id = intval($data['id'] ?? 0);

if (!$id) {
    echo json_encode(["status"=>false,"message"=>"ID required"]);
    exit;
}

$sql = "UPDATE categories SET is_deleted=1 WHERE id='$id'";

if ($conn->query($sql)) {
    echo json_encode(["status"=>true,"message"=>"Deleted"]);
} else {
    echo json_encode(["status"=>false,"message"=>$conn->error]);
}
?>