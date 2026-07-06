<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }

include "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$id             = intval($data['id']);
$name           = $conn->real_escape_string($data['name']);
$phone          = $conn->real_escape_string($data['phone']);
$gst_no         = strtoupper($conn->real_escape_string($data['gst_no'] ?? ''));
$address        = $conn->real_escape_string($data['address']);
$type           = $conn->real_escape_string($data['type']);
$credit_enabled = intval($data['credit_enabled']);
$credit_limit   = floatval($data['credit_limit']);
$credit_days    = intval($data['credit_days']);

// GST validation based on type
if ($type === 'B2B') {
    if (!$gst_no) {
        echo json_encode(["status" => false, "message" => "GST number is required for B2B customers"]);
        exit;
    }
    if (!preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gst_no)) {
        echo json_encode(["status" => false, "message" => "Invalid GST number format"]);
        exit;
    }
} else {
    if ($gst_no && !preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gst_no)) {
        echo json_encode(["status" => false, "message" => "Invalid GST number format"]);
        exit;
    }
}

$sql = "
UPDATE customers SET
name='$name',
phone='$phone',
gst_no='$gst_no',
address='$address',
type='$type',
credit_enabled='$credit_enabled',
credit_limit='$credit_limit',
credit_days='$credit_days'
WHERE id='$id'
";

echo json_encode(["status" => $conn->query($sql)]);
?>