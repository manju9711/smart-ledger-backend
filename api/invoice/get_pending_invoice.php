<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$company_id = intval($data['company_id'] ?? 0);

if (!$company_id) {
    echo json_encode(["status"=>false,"message"=>"company_id required"]);
    exit;
}

$result = $conn->query("
SELECT * FROM invoices 
WHERE company_id='$company_id' 
AND payment_status='not_paid'
ORDER BY id DESC
");

$rows = [];

while($row = $result->fetch_assoc()){
    $row['products'] = json_decode($row['products']);
    $rows[] = $row;
}

echo json_encode([
    "status"=>true,
    "data"=>$rows
]);
?>