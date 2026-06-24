<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include "../../config/db.php";

$sql = "

SELECT

    cr.*,
    u.name AS admin_name,
    u.email AS admin_email

FROM company_requests cr

LEFT JOIN users u
ON u.id = cr.admin_id

WHERE cr.status='pending'

ORDER BY cr.id DESC

";

$res = mysqli_query($conn,$sql);

$data = [];

while($row = mysqli_fetch_assoc($res)){
    $data[] = $row;
}

echo json_encode([
    "status"=>true,
    "data"=>$data
]);