<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$invoice_id = intval($data['invoice_id'] ?? 0);

$amount = floatval($data['amount'] ?? 0);

$payment_method = mysqli_real_escape_string(
    $conn,
    $data['payment_method'] ?? 'cash'
);

if ($invoice_id <= 0) {

    echo json_encode([
        "status" => false,
        "message" => "Invalid Invoice ID"
    ]);

    exit;
}

if ($amount <= 0) {

    echo json_encode([
        "status" => false,
        "message" => "Enter Valid Amount"
    ]);

    exit;
}

$invoiceQuery = mysqli_query(
    $conn,
    "SELECT * FROM invoices WHERE id='$invoice_id'"
);

$invoice = mysqli_fetch_assoc($invoiceQuery);

if (!$invoice) {

    echo json_encode([
        "status" => false,
        "message" => "Invoice Not Found"
    ]);

    exit;
}

$current_paid =
    floatval($invoice['paid_amount']);

$current_balance =
    floatval($invoice['balance_amount']);

$new_paid =
    $current_paid + $amount;

$new_balance =
    $current_balance - $amount;

if ($new_balance < 0) {
    $new_balance = 0;
}

$payment_status =
    $new_balance <= 0
    ? 'paid'
    : 'pending';

$update = mysqli_query(
    $conn,
    "UPDATE invoices SET
        paid_amount='$new_paid',
        balance_amount='$new_balance',
        payment_method='$payment_method',
        payment_status='$payment_status'
     WHERE id='$invoice_id'"
);

if (!$update) {

    echo json_encode([
        "status" => false,
        "message" => mysqli_error($conn)
    ]);

    exit;
}

echo json_encode([
    "status" => true,
    "message" => "Payment Updated Successfully"
]);

?>