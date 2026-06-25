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
$phone    = $conn->real_escape_string($_GET['phone'] ?? '');

if (!$admin_id || !$phone) {
    echo json_encode(["status" => false, "message" => "Admin ID and phone required"]);
    exit;
}

$res = $conn->query("
    SELECT id, name, phone, gst_no, credit_enabled, credit_limit,
           loyalty_points, advance_balance, pending_amount
    FROM customers
    WHERE admin_id = '$admin_id'
      AND phone = '$phone'
      AND is_deleted = 0
    LIMIT 1
");

if ($res->num_rows === 0) {
    echo json_encode(["status" => false]);
    exit;
}

echo json_encode(["status" => true, "data" => $res->fetch_assoc()]);
?>