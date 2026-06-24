<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

/* ── INPUTS ── */
$company_id     = intval($data['company_id'] ?? 0);
$from_date      = $data['from_date']      ?? '';   // YYYY-MM-DD
$to_date        = $data['to_date']        ?? '';   // YYYY-MM-DD
$payment_method = $data['payment_method'] ?? 'all';
$payment_status = $data['payment_status'] ?? 'all';
$customer_name  = trim($data['customer_name'] ?? '');

/* ── VALIDATION ── */
if (!$company_id) {
    echo json_encode(["status" => false, "message" => "company_id required"]);
    exit;
}

/* ── BUILD WHERE CLAUSE ── */
$where = "i.company_id = '$company_id'";

// Date range filter
if ($from_date && $to_date) {
    $from = $conn->real_escape_string($from_date);
    $to   = $conn->real_escape_string($to_date);
    $where .= " AND DATE(i.created_at) BETWEEN '$from' AND '$to'";
} elseif ($from_date) {
    $from  = $conn->real_escape_string($from_date);
    $where .= " AND DATE(i.created_at) >= '$from'";
} elseif ($to_date) {
    $to    = $conn->real_escape_string($to_date);
    $where .= " AND DATE(i.created_at) <= '$to'";
}

// Payment method filter
if ($payment_method && $payment_method !== 'all') {
    $pm    = $conn->real_escape_string($payment_method);
    $where .= " AND i.payment_method = '$pm'";
}

// Payment status filter
if ($payment_status && $payment_status !== 'all') {
    $ps    = $conn->real_escape_string($payment_status);
    $where .= " AND i.payment_status = '$ps'";
}

// Customer name filter
if ($customer_name) {
    $cn    = $conn->real_escape_string($customer_name);
    $where .= " AND i.customer_name LIKE '%$cn%'";
}

/* ── QUERY ── */
$sql = "
    SELECT
        i.*,
        u.name AS cashier_name
    FROM invoices i
    LEFT JOIN users u ON i.cashier_id = u.id
    WHERE $where
    ORDER BY i.id DESC
";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode([
        "status"  => false,
        "message" => $conn->error
    ]);
    exit;
}

$rows = [];

/* ── SUMMARY TOTALS ── */
$total_invoices   = 0;
$total_amount_sum = 0.0;
$total_paid_sum   = 0.0;
$total_pending_sum = 0.0;

while ($row = $result->fetch_assoc()) {
    $row['products']   = json_decode($row['products'] ?? '[]');
    $rows[]            = $row;
    $total_invoices++;
    $total_amount_sum  += floatval($row['total_amount']);
    $total_paid_sum    += floatval($row['paid_amount']);
    $total_pending_sum += floatval($row['balance_amount']);
}

echo json_encode([
    "status"        => true,
    "data"          => $rows,
    "summary"       => [
        "total_invoices"   => $total_invoices,
        "total_amount"     => $total_amount_sum,
        "total_paid"       => $total_paid_sum,
        "total_pending"    => $total_pending_sum,
    ]
]);
?>