<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include __DIR__ . '/../../config/db.php';

// raw JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status"=>false,"message"=>"Invalid JSON"]);
    exit;
}

// values
$customer_name = $data['customer_name'];
$customer_phone = $data['customer_phone'];
$products = json_encode($data['products']);

$sub_total = $data['sub_total'];
$gst_total = $data['gst_total'];
$total_amount = $data['total_amount'];

$paid_amount = $data['paid_amount'];
$balance_amount = $data['balance_amount'];
$payment_method = $data['payment_method'];

// invoice number generate
$invoice_no = "INV-" . time();

if (empty($customer_name) || !preg_match('/^[0-9]{10}$/', $customer_phone)) {
    echo json_encode(["status"=>false,"message"=>"Invalid customer details"]);
    exit;
}

if (empty($data['products']) || count($data['products']) == 0) {
    echo json_encode(["status"=>false,"message"=>"No products"]);
    exit;
}

// insert query
$sql = "INSERT INTO invoices (
invoice_no, customer_name, customer_phone, products,
sub_total, gst_total, total_amount,
paid_amount, balance_amount, payment_method
) VALUES (
'$invoice_no','$customer_name','$customer_phone','$products',
'$sub_total','$gst_total','$total_amount',
'$paid_amount','$balance_amount','$payment_method'
)";

if ($conn->query($sql)) {

    // 🔥🔥🔥 STOCK REDUCE HERE 🔥🔥🔥
    $productList = $data['products'];

    foreach ($productList as $item) {
        $name = $item['name'];
        $qty = $item['qty'];

        $conn->query("
            UPDATE products 
            SET stock = GREATEST(stock - $qty, 0)
            WHERE product_name = '$name'
        ");
    }

    // response
    echo json_encode([
        "status"=>true,
        "message"=>"Invoice Created",
        "invoice_no"=>$invoice_no
    ]);

} else {
    echo json_encode([
        "status"=>false,
        "message"=>$conn->error
    ]);
}
?>