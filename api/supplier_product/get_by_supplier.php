<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }

include __DIR__ . '/../../config/db.php';

$supplier_id = intval($_GET['supplier_id'] ?? 0);

if (!$supplier_id) {
    echo json_encode(["status"=>true,"data"=>[]]);
    exit;
}

$result = mysqli_query($conn, "
SELECT
    sp.*,
    c.name AS category_name,
    sc.name AS subcategory_name,
    b.name AS brand_name,
    comp.company_name
FROM supplier_products sp
LEFT JOIN categories c ON sp.category_id = c.id
LEFT JOIN subcategories sc ON sp.subcategory_id = sc.id
LEFT JOIN brands b ON sp.brand_id = b.id
LEFT JOIN companies comp ON sp.company_id = comp.id
WHERE sp.supplier_id = '$supplier_id'
ORDER BY sp.id DESC
");

if (!$result) {
    echo json_encode(["status"=>false,"message"=>mysqli_error($conn)]);
    exit;
}

$data = [];
while ($row = mysqli_fetch_assoc($result)) { $data[] = $row; }

echo json_encode(["status"=>true,"data"=>$data]);