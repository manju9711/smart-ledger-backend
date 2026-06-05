<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data['id']);

$sql = "
SELECT
    c.*,
    co.gstin as company_gstin
FROM customers c
LEFT JOIN companies co
    ON c.company_id = co.id
WHERE c.id = '$id'
LIMIT 1
";

$res = $conn->query($sql);

echo json_encode([
    "status" => true,
    "data" => $res->fetch_assoc()
]);