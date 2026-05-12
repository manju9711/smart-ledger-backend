<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$invoice_no = $data['invoice_no'] ?? '';

if (!$invoice_no) {
    echo json_encode([
        "status"=>false,
        "message"=>"Invoice required"
    ]);
    exit;
}

// ✅ CHECK invoice exists
$check = $conn->query("SELECT * FROM invoices WHERE invoice_no='$invoice_no'");

if ($check->num_rows == 0) {
    echo json_encode([
        "status"=>false,
        "message"=>"Invoice not found"
    ]);
    exit;
}

// ✅ UPDATE PAYMENT
$sql = "
UPDATE invoices 
SET 
    payment_status='paid',
    paid_amount = total_amount,
    balance_amount = 0
WHERE invoice_no='$invoice_no'
";

if ($conn->query($sql)) {

 $conn->query("
        UPDATE payments
        SET
            payment_status='paid',
            paid_amount = total_amount,
            balance_amount = 0
        WHERE invoice_no='$invoice_no'
    ");

    echo json_encode([
        "status"=>true,
        "message"=>"Payment updated successfully"
    ]);

} else {

    echo json_encode([
        "status"=>false,
        "message"=>$conn->error
    ]);
}
?>