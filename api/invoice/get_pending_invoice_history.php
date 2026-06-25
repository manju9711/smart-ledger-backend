<?php
ini_set('display_errors', 0);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

include "../../config/db.php";

$data     = json_decode(file_get_contents("php://input"), true);
$admin_id = intval($data['admin_id'] ?? 0);

if (!$admin_id) {
    echo json_encode(["status" => false, "message" => "admin_id required"]);
    exit;
}

// ALL invoices — paid + pending + partial
// Join customers to filter by admin_id
$result = $conn->query("
    SELECT
        i.*,
        IFNULL(c.credit_limit, 0)            AS credit_limit,
        (i.total_amount - i.balance_amount)  AS paid_amount_total

    FROM invoices i

    INNER JOIN customers c ON c.id = i.customer_id

    WHERE c.admin_id  = '$admin_id'
      AND c.is_deleted = 0

    ORDER BY i.id DESC
");

if (!$result) {
    echo json_encode(["status" => false, "message" => $conn->error]);
    exit;
}

$rows = [];

while ($row = $result->fetch_assoc()) {

    // Decode products JSON
    $row['products'] = json_decode($row['products'] ?? '[]');

    // Dynamic payment status
    $balance = floatval($row['balance_amount']);
    $paid    = floatval($row['paid_amount_total']);

    if ($balance <= 0) {
        $row['payment_history_status'] = "paid";
    } elseif ($paid > 0) {
        $row['payment_history_status'] = "partial";
    } else {
        $row['payment_history_status'] = "pending";
    }

    $rows[] = $row;
}

echo json_encode(["status" => true, "data" => $rows]);
?>