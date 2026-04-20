<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$invoice_no = $data['invoice_no'];

if (!$invoice_no) {
    echo json_encode(["status"=>false,"message"=>"Invoice required"]);
    exit;
}

$conn->query("
UPDATE invoices 
SET payment_status='paid',
paid_amount = total_amount,
balance_amount = 0
WHERE invoice_no='$invoice_no'
");

echo json_encode([
    "status"=>true,
    "message"=>"Payment updated"
]);
?>