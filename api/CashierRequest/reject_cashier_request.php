<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include "../../config/db.php";

$data = json_decode(
    file_get_contents("php://input"),
    true
);

$id = intval($data['id'] ?? 0);

mysqli_query($conn,"

UPDATE company_requests

SET status='rejected'

WHERE id='$id'

");

echo json_encode([
    "status"=>true,
    "message"=>"Company request rejected"
]);