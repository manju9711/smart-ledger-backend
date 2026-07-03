<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . '/../../config/db.php';

$company_id = intval($_GET['company_id'] ?? 0);

if ($company_id <= 0) {
    echo json_encode(["status" => true, "data" => []]);
    exit;
}

$stmt = $conn->prepare(
    "SELECT
        id, company_id, supplier_name, mobile_number, alt_mobile,
        email, gst_number, address, city, district, state, pincode, country, status
     FROM suppliers
     WHERE company_id = ? AND is_deleted = 0
     ORDER BY id DESC"
);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "status" => true,
    "data" => $data
]);