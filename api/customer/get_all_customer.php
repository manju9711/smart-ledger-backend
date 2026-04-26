<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
include "../../config/db.php";

$company_id = intval($_GET['company_id']);

$res = $conn->query("
SELECT * FROM customers
WHERE company_id='$company_id' AND is_deleted=0
ORDER BY id DESC
");

$data = [];
while($row = $res->fetch_assoc()){
    $data[] = $row;
}

echo json_encode([
    "status"=>true,
    "data"=>$data
]);