<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$admin_id = intval($data['admin_id'] ?? 0);

$sql = "

SELECT *

FROM companies

WHERE is_deleted = 0
AND admin_id='$admin_id'

ORDER BY id DESC

";

$res = mysqli_query($conn, $sql);

$list = [];

while($row = mysqli_fetch_assoc($res)){
    $list[] = $row;
}

echo json_encode([
    "status"=>true,
    "data"=>$list
]);