<?php
// 🔥 CORS HEADERS
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// 🔥 PREFLIGHT
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$company_id     = intval($data['company_id'] ?? 0);
$supplier_name  = trim($data['supplier_name'] ?? '');
$mobile_number  = trim($data['mobile_number'] ?? '');
$alt_mobile     = trim($data['alt_mobile'] ?? '');
$email          = trim($data['email'] ?? '');
$gst_number     = trim($data['gst_number'] ?? '');
$address        = trim($data['address'] ?? '');
$city           = trim($data['city'] ?? '');
$district       = trim($data['district'] ?? '');
$state          = trim($data['state'] ?? '');
$pincode        = trim($data['pincode'] ?? '');
$country        = trim($data['country'] ?? '');

if (!$company_id || !$supplier_name || !$mobile_number) {
    echo json_encode(["status" => false, "message" => "Company, Supplier Name & Mobile Number are required"]);
    exit;
}

if (!preg_match('/^\d{10}$/', $mobile_number)) {
    echo json_encode(["status" => false, "message" => "Enter a valid 10-digit mobile number"]);
    exit;
}

if ($alt_mobile && !preg_match('/^\d{10}$/', $alt_mobile)) {
    echo json_encode(["status" => false, "message" => "Alternative mobile must be a 10-digit number"]);
    exit;
}

if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => false, "message" => "Enter a valid email address"]);
    exit;
}

if ($pincode && !preg_match('/^\d{6}$/', $pincode)) {
    echo json_encode(["status" => false, "message" => "Pincode must be exactly 6 digits"]);
    exit;
}

if ($gst_number && strlen($gst_number) !== 15) {
    echo json_encode(["status" => false, "message" => "GST number must be exactly 15 characters"]);
    exit;
}

// Duplicate check (same mobile number under same company, not deleted)
$dupStmt = $conn->prepare(
    "SELECT id FROM suppliers WHERE mobile_number = ? AND company_id = ? AND is_deleted = 0"
);
$dupStmt->bind_param("si", $mobile_number, $company_id);
$dupStmt->execute();
$dupResult = $dupStmt->get_result();

if ($dupResult->num_rows > 0) {
    echo json_encode(["status" => false, "message" => "Supplier with this mobile number already exists"]);
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO suppliers
        (company_id, supplier_name, mobile_number, alt_mobile, email, gst_number,
         address, city, district, state, pincode, country, status, is_deleted)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 0)"
);

if (!$stmt) {
    echo json_encode(["status" => false, "message" => $conn->error]);
    exit;
}

$stmt->bind_param(
    "isssssssssss",
    $company_id,
    $supplier_name,
    $mobile_number,
    $alt_mobile,
    $email,
    $gst_number,
    $address,
    $city,
    $district,
    $state,
    $pincode,
    $country
);

if ($stmt->execute()) {
    echo json_encode(["status" => true, "message" => "Supplier added"]);
} else {
    echo json_encode(["status" => false, "message" => $stmt->error]);
}