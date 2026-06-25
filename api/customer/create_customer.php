<?php
ini_set('display_errors', 0);  // 👈 hide HTML errors
error_reporting(0);             // 👈 no error output

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }

include "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$admin_id       = intval($data['admin_id'] ?? 0);
$name           = $conn->real_escape_string(trim($data['name'] ?? ''));
$phone          = $conn->real_escape_string(trim($data['phone'] ?? ''));
$gst_no         = strtoupper($conn->real_escape_string(trim($data['gst_no'] ?? '')));
$address        = $conn->real_escape_string(trim($data['address'] ?? ''));
$type           = $conn->real_escape_string($data['type'] ?? 'regular');
$credit_enabled = intval($data['credit_enabled'] ?? 0);
$credit_limit   = floatval($data['credit_limit'] ?? 0);
$credit_days    = intval($data['credit_days'] ?? 0);

// ── VALIDATION ──
if (!$admin_id) {
    echo json_encode(["status" => false, "message" => "Admin ID required"]);
    exit;
}

if (!$name) {
    echo json_encode(["status" => false, "message" => "Customer name is required"]);
    exit;
}

if (!preg_match('/^[0-9]{10}$/', $phone)) {
    echo json_encode(["status" => false, "message" => "Enter valid 10 digit mobile number"]);
    exit;
}

// GST mandatory + format validation (15 alphanumeric chars)
if (!$gst_no) {
    echo json_encode(["status" => false, "message" => "GST number is required"]);
    exit;
}

if (!preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gst_no)) {
    echo json_encode(["status" => false, "message" => "Invalid GST number format"]);
    exit;
}

// ── INSERT ──
$sql = "
    INSERT INTO customers
    (admin_id, name, phone, address, gst_no, type, credit_enabled, credit_limit, credit_days, created_at)
    VALUES
    ('$admin_id','$name','$phone','$address','$gst_no','$type','$credit_enabled','$credit_limit','$credit_days', NOW())
";

if ($conn->query($sql)) {
    echo json_encode([
        "status"  => true,
        "message" => "Customer created successfully"
    ]);
} else {
    // 👇 this shows exact DB error
    echo json_encode([
        "status"  => false,
        "message" => "DB error: " . $conn->error
    ]);
}
?>