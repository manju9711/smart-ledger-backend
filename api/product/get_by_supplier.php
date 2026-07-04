<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . '/../../config/db.php';

$supplier_id = intval($_GET['supplier_id'] ?? 0);

if (!$supplier_id) {
    echo json_encode(["status" => false, "message" => "supplier_id required"]);
    exit;
}

$sql = "SELECT p.*, c.company_name, cat.name AS category_name
        FROM products p
        LEFT JOIN companies c ON p.company_id = c.id
        LEFT JOIN categories cat ON p.category_id = cat.id
        WHERE p.supplier_id = '$supplier_id'
        AND p.is_deleted = 0
        ORDER BY p.id DESC";

$result = mysqli_query($conn, $sql);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode(["status" => true, "data" => $data]);
?>