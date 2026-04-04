<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");

include __DIR__ . '/../../config/db.php';

$invoice_no = $_GET['id'];

$result = $conn->query("SELECT * FROM invoices WHERE invoice_no='$invoice_no'");

if($result->num_rows > 0){
    $row = $result->fetch_assoc();
    $row['products'] = json_decode($row['products']);

    echo json_encode([
        "status"=>true,
        "data"=>$row
    ]);
}else{
    echo json_encode([
        "status"=>false,
        "message"=>"Invoice not found"
    ]);
}
?>