<?php
ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }

include "../../config/db.php";

$admin_id = intval($_GET['admin_id'] ?? 0);

if (!$admin_id) {
    echo json_encode(["status" => false, "message" => "Admin ID required"]);
    exit;
}

$res = $conn->query("
    SELECT
        c.*,
        IFNULL(
            SUM(
                CASE
                    WHEN i.balance_amount < 0
                    THEN ABS(i.balance_amount)
                    ELSE 0
                END
            ), 0
        ) AS advance_amount
    FROM customers c
    LEFT JOIN invoices i ON i.customer_id = c.id
    WHERE c.admin_id = '$admin_id'
      AND c.is_deleted = 0
    GROUP BY c.id
    ORDER BY c.id DESC
");

$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(["status" => true, "data" => $data]);
?>