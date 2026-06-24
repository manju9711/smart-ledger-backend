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


$data = json_decode(
    file_get_contents("php://input"),
    true
);

$id = intval($data['request_id'] ?? 0);

$res = mysqli_query($conn,"
SELECT *
FROM company_requests
WHERE id='$id'
");

$request = mysqli_fetch_assoc($res);

if(!$request){

    echo json_encode([
        "status"=>false,
        "message"=>"Request not found"
    ]);
    exit;
}

$logoPath = '';

if(!empty($request['logo'])){

    $image = base64_decode($request['logo']);

    $upload_dir =
    __DIR__ . "/../uploads/";

    if(!is_dir($upload_dir)){
        mkdir($upload_dir,0777,true);
    }

    $file_name = time().".png";

    file_put_contents(
        $upload_dir.$file_name,
        $image
    );

    $logoPath = "uploads/".$file_name;
}

mysqli_query($conn,"

INSERT INTO companies
(
 admin_id,
 company_name,
 company_code,
 company_address,
 gstin,
 gst_type,
 phone,
 logo
)

VALUES
(
 '".$request['admin_id']."',
 '".$request['company_name']."',
 '".$request['company_code']."',
 '".$request['company_address']."',
 '".$request['gstin']."',
 '".$request['gst_type']."',
 '".$request['phone']."',
 '$logoPath'
)

");

mysqli_query($conn,"

UPDATE company_requests

SET status='approved'

WHERE id='$id'

");

echo json_encode([
    "status"=>true,
    "message"=>"Company approved successfully"
]);