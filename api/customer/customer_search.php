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
$q        = $conn->real_escape_string($_GET['q'] ?? '');

if (!$admin_id) {
    echo json_encode(["status" => false, "message" => "Admin ID required"]);
    exit;
}

$res = $conn->query("
    SELECT id, name, phone, gst_no, credit_enabled, credit_limit,
           loyalty_points, advance_balance, pending_amount
    FROM customers
    WHERE admin_id = '$admin_id'
      AND is_deleted = 0
      AND (name LIKE '%$q%' OR phone LIKE '%$q%')
    ORDER BY name ASC
    LIMIT 10
");

$data = [];
while ($row = $res->fetch_assoc()) $data[] = $row;

echo json_encode(["status" => true, "data" => $data]);
?>