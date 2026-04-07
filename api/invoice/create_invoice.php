<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$company_id = intval($data['company_id'] ?? 0);

// 🔥 COMPANY VALIDATION
$checkCompany = mysqli_query($conn, "SELECT id FROM companies WHERE id='$company_id'");
if (mysqli_num_rows($checkCompany) == 0) {
    echo json_encode(["status"=>false,"message"=>"Invalid company"]);
    exit;
}

$customer_name = $data['customer_name'];
$customer_phone = $data['customer_phone'];
$products = $data['products'];

$sub_total = $data['sub_total'];
$gst_total = $data['gst_total'];
$total_amount = $data['total_amount'];

$paid_amount = $data['paid_amount'];
$balance_amount = $data['balance_amount'];
$payment_method = $data['payment_method'];

$invoice_no = "INV-" . time();

if (!$customer_name || !preg_match('/^[0-9]{10}$/', $customer_phone)) {
    echo json_encode(["status"=>false,"message"=>"Invalid customer"]);
    exit;
}

if (count($products) == 0) {
    echo json_encode(["status"=>false,"message"=>"No products"]);
    exit;
}

// 🔥 VALIDATE PRODUCTS + STOCK
foreach ($products as $item) {

    $product_id = intval($item['product_id']);
    $qty = floatval($item['qty']);

    $check = mysqli_query($conn, "
        SELECT stock FROM products 
        WHERE id='$product_id' AND company_id='$company_id' AND is_deleted=0
    ");

    if (mysqli_num_rows($check) == 0) {
        echo json_encode(["status"=>false,"message"=>"Invalid product"]);
        exit;
    }

    $row = mysqli_fetch_assoc($check);

    if ($row['stock'] < $qty) {
        echo json_encode(["status"=>false,"message"=>"Stock not enough"]);
        exit;
    }
}

// INSERT
$product_json = json_encode($products);

$sql = "INSERT INTO invoices (
invoice_no, customer_name, customer_phone, products,
sub_total, gst_total, total_amount,
paid_amount, balance_amount, payment_method, company_id
) VALUES (
'$invoice_no','$customer_name','$customer_phone','$product_json',
'$sub_total','$gst_total','$total_amount',
'$paid_amount','$balance_amount','$payment_method','$company_id'
)";

if ($conn->query($sql)) {

    // 🔥 STOCK REDUCE
    foreach ($products as $item) {
        $pid = $item['product_id'];
        $qty = $item['qty'];

        $conn->query("
            UPDATE products 
            SET stock = stock - $qty 
            WHERE id='$pid'
        ");
    }

    echo json_encode([
        "status"=>true,
        "message"=>"Invoice Created",
        "invoice_no"=>$invoice_no
    ]);

} else {
    echo json_encode(["status"=>false,"message"=>$conn->error]);
}
?>