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
$from_date      = $data['from_date']      ?? '';
$to_date        = $data['to_date']        ?? '';
$payment_method = $data['payment_method'] ?? 'all';
$payment_status = $data['payment_status'] ?? 'all'; // pending | not_paid | partial | all
$customer_name  = trim($data['customer_name'] ?? '');
$due_status     = $data['due_status']     ?? 'all'; // overdue | upcoming | all

/* ── VALIDATION ── */
if (!$company_id) {
    echo json_encode(["status" => false, "message" => "company_id required"]);
    exit;
}

/* ── BUILD WHERE CLAUSE ── */
// Only show invoices that still have balance
$where = "i.company_id = '$company_id'
          AND i.balance_amount > 0
          AND i.payment_status IN ('not_paid','partial')";

// Date range (invoice creation date)
if ($from_date && $to_date) {
    $from  = $conn->real_escape_string($from_date);
    $to    = $conn->real_escape_string($to_date);
    $where .= " AND DATE(i.created_at) BETWEEN '$from' AND '$to'";
} elseif ($from_date) {
    $from  = $conn->real_escape_string($from_date);
    $where .= " AND DATE(i.created_at) >= '$from'";
} elseif ($to_date) {
    $to    = $conn->real_escape_string($to_date);
    $where .= " AND DATE(i.created_at) <= '$to'";
}

// Payment method
if ($payment_method && $payment_method !== 'all') {
    $pm    = $conn->real_escape_string($payment_method);
    $where .= " AND i.payment_method = '$pm'";
}

// Payment status (not_paid / partial)
if ($payment_status && $payment_status !== 'all') {
    $ps    = $conn->real_escape_string($payment_status);
    $where .= " AND i.payment_status = '$ps'";
}

// Customer name
if ($customer_name) {
    $cn    = $conn->real_escape_string($customer_name);
    $where .= " AND i.customer_name LIKE '%$cn%'";
}

// Due status (overdue = due_date < TODAY, upcoming = due_date >= TODAY)
$today = date('Y-m-d');
if ($due_status === 'overdue') {
    $where .= " AND i.due_date IS NOT NULL AND i.due_date < '$today'";
} elseif ($due_status === 'upcoming') {
    $where .= " AND (i.due_date IS NULL OR i.due_date >= '$today')";
}

/* ── QUERY ── */
$sql = "
    SELECT
        i.*,
        u.name  AS cashier_name,
        c.credit_limit,
        c.credit_days,
        COALESCE(
            (SELECT SUM(p.paid_amount)
             FROM payments p
             WHERE p.invoice_id = i.id
             AND p.payment_status = 'paid'),
            i.paid_amount
        ) AS paid_amount_total
    FROM invoices i
    LEFT JOIN users     u ON i.cashier_id   = u.id
    LEFT JOIN customers c ON i.customer_id  = c.id
    WHERE $where
    ORDER BY
        CASE
            WHEN i.due_date IS NOT NULL AND i.due_date < '$today' THEN 0
            ELSE 1
        END,
        i.due_date ASC,
        i.id DESC
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
$total_pending_sum  = 0.0;
$total_overdue_sum  = 0.0;
$overdue_count      = 0;

while ($row = $result->fetch_assoc()) {
    $row['products'] = json_decode($row['products'] ?? '[]');

    // Flag overdue server-side too
    $isOverdue = false;
    if ($row['due_date'] && $row['due_date'] < $today) {
        $isOverdue = true;
    }
    $row['is_overdue'] = $isOverdue;

    $rows[]             = $row;
    $total_pending_sum  += floatval($row['balance_amount']);
    if ($isOverdue) {
        $total_overdue_sum += floatval($row['balance_amount']);
        $overdue_count++;
    }
}

echo json_encode([
    "status"  => true,
    "data"    => $rows,
    "summary" => [
        "total_pending"  => $total_pending_sum,
        "total_overdue"  => $total_overdue_sum,
        "overdue_count"  => $overdue_count,
        "total_records"  => count($rows),
    ]
]);
?>