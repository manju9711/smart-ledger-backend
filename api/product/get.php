<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include "../../config/db.php";

$company_id = intval($_GET['company_id'] ?? 0);

// NEW: optional brand filter. When 0 (not passed / "All Brands"),
// every product for the company is returned — unchanged behavior.
$brand_id = intval($_GET['brand_id'] ?? 0);

if (!$company_id) {

    echo json_encode([
        "status" => true,
        "data" => []
    ]);
    exit;
}

$where = "p.company_id = '$company_id' AND p.is_deleted = 0";

// NEW: narrow to a single brand when requested
if ($brand_id > 0) {
    $where .= " AND p.brand_id = '$brand_id'";
}

$result = mysqli_query($conn, "

SELECT
    p.*,
    c.name AS category_name,
    sc.name AS subcategory_name,
    b.name AS brand_name,
    comp.company_name,
    comp.gstin AS company_gstin,
    comp.gst_type,
    sup.supplier_name

FROM products p

LEFT JOIN categories c
ON p.category_id = c.id

LEFT JOIN subcategories sc
ON p.subcategory_id = sc.id

LEFT JOIN brands b
ON p.brand_id = b.id

LEFT JOIN companies comp
ON p.company_id = comp.id

LEFT JOIN suppliers sup
ON p.supplier_id = sup.id

WHERE $where

ORDER BY p.id DESC

");

if (!$result) {
    echo json_encode([
        "status" => false,
        "message" => mysqli_error($conn)
    ]);
    exit;
}

$data = [];

while ($row = mysqli_fetch_assoc($result)) {

    $data[] = $row;

}

echo json_encode([
    "status" => true,
    "data" => $data
]);