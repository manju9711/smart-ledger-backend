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
$from_date      = $data['from_date'] ?? '';
$to_date        = $data['to_date'] ?? '';
$payment_method = $data['payment_method'] ?? 'all';
$payment_status = $data['payment_status'] ?? 'all';
$customer_name  = trim($data['customer_name'] ?? '');

/* ── VALIDATION ── */
if (!$company_id) {
    echo json_encode([
        "status" => false,
        "message" => "company_id required"
    ]);
    exit;
}

/* ── WHERE ── */
$where = "i.company_id = '$company_id'";

/* Date Filter */
if ($from_date && $to_date) {

    $from = $conn->real_escape_string($from_date);
    $to   = $conn->real_escape_string($to_date);

    $where .= " AND DATE(i.created_at) BETWEEN '$from' AND '$to'";

} elseif ($from_date) {

    $from = $conn->real_escape_string($from_date);

    $where .= " AND DATE(i.created_at) >= '$from'";

} elseif ($to_date) {

    $to = $conn->real_escape_string($to_date);

    $where .= " AND DATE(i.created_at) <= '$to'";
}

/* Payment Method */
if ($payment_method && $payment_method !== 'all') {

    $pm = $conn->real_escape_string($payment_method);

    $where .= " AND i.payment_method = '$pm'";
}

/* Payment Status */
if ($payment_status == 'paid') {
    $where .= " AND i.balance_amount = 0";

} elseif ($payment_status == 'not_paid') {
    $where .= " AND i.paid_amount = 0 AND i.balance_amount > 0
                AND (i.due_date IS NULL OR i.due_date >= CURDATE())";

} elseif ($payment_status == 'pending') {
    // partial payment, not yet overdue
    $where .= " AND i.paid_amount > 0 AND i.balance_amount > 0
                AND (i.due_date IS NULL OR i.due_date >= CURDATE())";

} elseif ($payment_status == 'overdue') {
    // due_date passed, still has balance
    $where .= " AND i.balance_amount > 0 AND i.due_date < CURDATE()";
}

/* Customer Name */
if ($customer_name) {

    $cn = $conn->real_escape_string($customer_name);

    $where .= " AND i.customer_name LIKE '%$cn%'";
}

/* ── QUERY ── */
$sql = "
SELECT
    i.*,
    u.name AS cashier_name,
    c.gstin
FROM invoices i
LEFT JOIN users u
    ON i.cashier_id = u.id
LEFT JOIN companies c
    ON i.company_id = c.id
WHERE $where
ORDER BY i.id DESC
";

$result = $conn->query($sql);

if (!$result) {

    echo json_encode([
        "status" => false,
        "message" => $conn->error
    ]);
    exit;
}

$rows = [];

$total_invoices    = 0;
$total_amount_sum  = 0;
$total_paid_sum    = 0;
$total_pending_sum = 0;

while ($row = $result->fetch_assoc()) {

    /* Auto Status Logic */

/* Auto Status Logic */
if (floatval($row['balance_amount']) == 0) {
    $row['payment_status'] = 'paid';

} elseif (floatval($row['paid_amount']) == 0) {
    // Nothing paid at all
    // Check if due_date passed → overdue, else not_paid
    if (!empty($row['due_date']) && $row['due_date'] < date('Y-m-d')) {
        $row['payment_status'] = 'overdue';
    } else {
        $row['payment_status'] = 'not_paid';
    }

} else {
    // Half paid (partial) → check due_date
    if (!empty($row['due_date']) && $row['due_date'] < date('Y-m-d')) {
        $row['payment_status'] = 'overdue';
    } else {
        $row['payment_status'] = 'pending'; // ← partial payment, not yet overdue
    }
}

    $row['products'] = json_decode(
        $row['products'] ?? '[]',
        true
    );

    $rows[] = $row;

    $total_invoices++;

    $total_amount_sum += floatval($row['total_amount']);

    $total_paid_sum += floatval($row['paid_amount']);

    $total_pending_sum += floatval($row['balance_amount']);
}

echo json_encode([
    "status" => true,
    "data" => $rows,
    "summary" => [
        "total_invoices" => $total_invoices,
        "total_amount" => $total_amount_sum,
        "total_paid" => $total_paid_sum,
        "total_pending" => $total_pending_sum
    ]
]);
?>