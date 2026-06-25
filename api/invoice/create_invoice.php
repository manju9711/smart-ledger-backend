<?php
ini_set('display_errors', 0);
error_reporting(0);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . '/../../config/db.php';
$data = json_decode(file_get_contents("php://input"), true);

$company_id     = intval($data['company_id'] ?? 0);
$customer_id    = intval($data['customer_id'] ?? 0);
$customer_name  = $conn->real_escape_string($data['customer_name'] ?? '');
if (trim($customer_name) == "") $customer_name = "Customer";
$customer_phone = $conn->real_escape_string($data['customer_phone'] ?? '');
$cashier_id     = intval($data['cashier_id'] ?? 0);
$products       = $data['products'] ?? [];
$sub_total      = floatval($data['sub_total'] ?? 0);
$gst_total      = floatval($data['gst_total'] ?? 0);
$total_amount   = floatval($data['total_amount'] ?? 0);
$paid_amount    = floatval($data['paid_amount'] ?? 0);
$payment_method = $conn->real_escape_string($data['payment_method'] ?? 'cash');
$payment_type   = $conn->real_escape_string($data['payment_type'] ?? 'cash');
$gst_type       = $conn->real_escape_string($data['gst_type'] ?? 'without_gst');
$gst_no         = $conn->real_escape_string($data['gst_no'] ?? '');
$invoice_no     = "INV-" . time();

/* ── VALIDATION ── */
if (empty($customer_name) && empty($customer_phone)) {
    echo json_encode(["status" => false, "message" => "Customer name or phone required"]);
    exit;
}
if (!empty($customer_phone) && !preg_match('/^[0-9]{10}$/', $customer_phone)) {
    echo json_encode(["status" => false, "message" => "Invalid phone number"]);
    exit;
}
if (count($products) == 0) {
    echo json_encode(["status" => false, "message" => "No products"]);
    exit;
}

/* ── GST CONTROL ── */
if ($gst_type == "without_gst") {
    $gst_total    = 0;
    $total_amount = $sub_total;
}

/* ── CREDIT / CASH LOGIC ── */
if ($payment_type == "credit") {
    $advance_used    = 0;
    $effective_total = $total_amount;
    $final_paid      = 0;
    $balance_amount  = $total_amount;
    $payment_status  = "not_paid";
    $advance_delta   = 0;
} else {
    $advance_balance = 0.0;
    if ($customer_id > 0) {
        $adv_res = $conn->query("
            SELECT advance_balance FROM customers
            WHERE id='$customer_id'
            LIMIT 1
        ");
        if ($adv_res && $adv_res->num_rows > 0) {
            $advance_balance = floatval($adv_res->fetch_assoc()['advance_balance']);
        }
    }
    $advance_used    = min($advance_balance, $total_amount);
    $effective_total = $total_amount - $advance_used;
    $total_received  = $paid_amount + $advance_used;

    if ($total_received >= $total_amount) {
        $final_paid     = $total_amount;
        $balance_amount = 0;
        $payment_status = "paid";
        $extra          = $total_received - $total_amount;
        $advance_delta  = $extra - $advance_used;
    } else {
        $final_paid     = $total_received;
        $balance_amount = $total_amount - $total_received;
        $payment_status = "partial";
        $advance_delta  = -$advance_used;
    }
}

/* ── DUE DATE ── */
$due_date = NULL;
if ($payment_type == "credit") {
    $credit_days = 0;
    $creditQry = $conn->query("
        SELECT credit_days FROM customers
        WHERE id='$customer_id'
        LIMIT 1
    ");
    if ($creditQry && $creditQry->num_rows > 0) {
        $credit_days = intval($creditQry->fetch_assoc()['credit_days']);
    }
    $due_date = $credit_days > 0
        ? date('Y-m-d', strtotime("+$credit_days days"))
        : date('Y-m-d');
}

/* ── STOCK CHECK ── */
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
    if (floatval($check->fetch_assoc()['stock']) < $qty) {
        echo json_encode(["status" => false, "message" => "Stock not enough"]);
        exit;
    }
}

/* ── PREVIOUS BALANCE ── */
$previous_balance = 0;
if ($customer_id > 0) {
    $balQry = $conn->query("
        SELECT COALESCE(SUM(balance_amount),0) AS total_pending
        FROM invoices
        WHERE customer_id='$customer_id'
        AND balance_amount > 0
    ");
    if ($balQry && $balQry->num_rows > 0) {
        $previous_balance = floatval($balQry->fetch_assoc()['total_pending']);
    }
}
$current_balance = $previous_balance + $balance_amount;

/* ── INSERT INVOICE ── */
$product_json    = $conn->real_escape_string(json_encode($products));
$customer_id_sql = $customer_id > 0 ? $customer_id : "NULL";
$due_date_sql    = $due_date ? "'$due_date'" : "NULL";
$gst_no_sql      = $gst_no   ? "'$gst_no'"  : "NULL";

$sql = "
    INSERT INTO invoices (
        invoice_no, customer_id, customer_name, customer_phone, cashier_id,
        products, sub_total, gst_total, total_amount,
        paid_amount, balance_amount, previous_balance, current_balance,
        payment_method, payment_type, gst_type, gst_no,
        payment_status, company_id, due_date
    ) VALUES (
        '$invoice_no', $customer_id_sql, '$customer_name', '$customer_phone', '$cashier_id',
        '$product_json', '$sub_total', '$gst_total', '$total_amount',
        '$final_paid', '$balance_amount', '$previous_balance', '$current_balance',
        '$payment_method', '$payment_type', '$gst_type', $gst_no_sql,
        '$payment_status', '$company_id', $due_date_sql
    )
";
if (!$conn->query($sql)) {
    echo json_encode(["status" => false, "message" => $conn->error]);
    exit;
}
$invoice_id = $conn->insert_id;

/* ── INSERT PAYMENT ── */
$pay_sql = "
    INSERT INTO payments (
        company_id, invoice_id, invoice_no, customer_id,
        total_amount, paid_amount, balance_amount,
        payment_method, payment_status,
        notes, created_at, updated_at
    ) VALUES (
        '$company_id', '$invoice_id', '$invoice_no', $customer_id_sql,
        '$total_amount', '$final_paid', '$balance_amount',
        '$payment_method', '$payment_status',
        '', NOW(), NOW()
    )
";
if (!$conn->query($pay_sql)) {
    echo json_encode(["status" => false, "message" => "Payment insert failed: " . $conn->error]);
    exit;
}

/* ── DEDUCT STOCK ── */
foreach ($products as $item) {
    $pid = intval($item['product_id']);
    $qty = floatval($item['qty']);
    $conn->query("UPDATE products SET stock = stock - $qty WHERE id='$pid'");
}

/* ── UPDATE CUSTOMER ── */
if ($customer_id > 0) {
    if ($payment_type != "credit") {
        $conn->query("
            UPDATE customers
            SET advance_balance = GREATEST(0, advance_balance + ($advance_delta))
            WHERE id='$customer_id'
        ");
        $points = floor($total_amount / 100);
        if ($points > 0) {
            $conn->query("
                UPDATE customers SET loyalty_points = loyalty_points + $points
                WHERE id='$customer_id'
            ");
        }
    }

    $total_pending = $current_balance;
    $conn->query("
        UPDATE customers SET pending_amount = '$total_pending'
        WHERE id = '$customer_id'
    ");
}  // ← இந்த } முன்னாடி இல்லாம போச்சு — அதுதான் CORS error!

/* ── LAST INVOICE ── */
$last_invoice = null;
if ($customer_id > 0) {
    $last_res = $conn->query("
        SELECT invoice_no, total_amount, paid_amount, balance_amount,
               payment_status, payment_method, created_at
        FROM invoices
        WHERE customer_id = '$customer_id'
          AND company_id  = '$company_id'
          AND id          != '$invoice_id'
        ORDER BY id DESC
        LIMIT 1
    ");
    if ($last_res && $last_res->num_rows > 0) {
        $last_invoice = $last_res->fetch_assoc();
    }
}

/* ── RESPONSE ── */
echo json_encode([
    "status"         => true,
    "invoice_no"     => $invoice_no,
    "invoice_id"     => $invoice_id,
    "advance_used"   => $advance_used  ?? 0,
    "advance_delta"  => $advance_delta ?? 0,
    "balance_amount" => $balance_amount,
    "payment_status" => $payment_status,
    "pending_amount" => $total_pending ?? 0,
    "last_invoice"   => $last_invoice,
]);
?>