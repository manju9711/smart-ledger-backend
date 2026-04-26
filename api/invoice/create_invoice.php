<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }

include "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$company_id     = intval($data['company_id']     ?? 0);
$customer_id    = intval($data['customer_id']    ?? 0);  // NEW
$customer_name  = $conn->real_escape_string($data['customer_name']  ?? '');
$customer_phone = $conn->real_escape_string($data['customer_phone'] ?? '');
$products       = $data['products']       ?? [];
$sub_total      = floatval($data['sub_total']     ?? 0);
$gst_total      = floatval($data['gst_total']     ?? 0);
$total_amount   = floatval($data['total_amount']  ?? 0);
$paid_amount    = floatval($data['paid_amount']   ?? 0);
$balance_amount = floatval($data['balance_amount']?? 0);
$payment_method = $conn->real_escape_string($data['payment_method'] ?? 'cash'); // cash/online/upi/credit
$payment_type   = $conn->real_escape_string($data['payment_type']   ?? 'cash'); // cash / credit
$gst_type       = $conn->real_escape_string($data['gst_type']       ?? 'without_gst'); // with_gst / without_gst
$gst_no         = $conn->real_escape_string($data['gst_no']         ?? '');     // NEW

$invoice_no = "INV-" . time();

/* ── Validation ── */
if (!$customer_name || !preg_match('/^[0-9]{10}$/', $customer_phone)) {
    echo json_encode(["status" => false, "message" => "Invalid customer"]);
    exit;
}
if (count($products) == 0) {
    echo json_encode(["status" => false, "message" => "No products"]);
    exit;
}

/* ── GST control ── */
if ($gst_type == "without_gst") {
    $gst_total    = 0;
    $total_amount = $sub_total;
}

/* ── Payment logic ── */
if ($payment_type == "credit") {
    $paid_amount    = 0;
    $balance_amount = $total_amount;
    $payment_status = "not_paid";
} else {
    $payment_status = "paid";
}

/* ── Due date ── */
if ($payment_type == "credit") {
    $due_date = date('Y-m-d', strtotime('+30 days'));
} else {
    $due_date = NULL;
}

/* ── Stock check ── */
foreach ($products as $item) {
    $product_id = intval($item['product_id']);
    $qty        = floatval($item['qty']);
    $check = $conn->query("
        SELECT stock FROM products
        WHERE id='$product_id' AND company_id='$company_id' AND is_deleted=0
    ");
    if ($check->num_rows == 0) {
        echo json_encode(["status" => false, "message" => "Invalid product"]);
        exit;
    }
    $row = $check->fetch_assoc();
    if ($row['stock'] < $qty) {
        echo json_encode(["status" => false, "message" => "Stock not enough for one or more products"]);
        exit;
    }
}

/* ── Insert invoice ── */
$product_json      = $conn->real_escape_string(json_encode($products));
$customer_id_sql   = $customer_id > 0 ? "'$customer_id'" : "NULL";
$due_date_sql      = $due_date ? "'$due_date'" : "NULL";
$gst_no_sql        = $gst_no   ? "'$gst_no'"  : "NULL";

$sql = "
    INSERT INTO invoices (
        invoice_no, customer_id, customer_name, customer_phone,
        products, sub_total, gst_total, total_amount,
        paid_amount, balance_amount,
        payment_method, payment_type, gst_type, gst_no,
        payment_status, company_id, due_date
    ) VALUES (
        '$invoice_no', $customer_id_sql, '$customer_name', '$customer_phone',
        '$product_json', '$sub_total', '$gst_total', '$total_amount',
        '$paid_amount', '$balance_amount',
        '$payment_method', '$payment_type', '$gst_type', $gst_no_sql,
        '$payment_status', '$company_id', $due_date_sql
    )
";

if ($conn->query($sql)) {
    $invoice_id = $conn->insert_id;

    /* ── Deduct stock ── */
    foreach ($products as $item) {
        $pid = intval($item['product_id']);
        $qty = floatval($item['qty']);
        $conn->query("UPDATE products SET stock = stock - $qty WHERE id='$pid'");
    }

    echo json_encode([
        "status"     => true,
        "invoice_no" => $invoice_no,
        "invoice_id" => $invoice_id,
    ]);
} else {
    echo json_encode(["status" => false, "message" => $conn->error]);
}
?>