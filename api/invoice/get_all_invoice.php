<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
include __DIR__ . '/../../config/db.php';

$result = $conn->query("SELECT * FROM invoices ORDER BY id DESC");

$data = [];

while($row = $result->fetch_assoc()){
    $row['products'] = json_decode($row['products']);
    $data[] = $row;
}

echo json_encode([
    "status"=>true,
    "data"=>$data
]);
?>