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

if (!$company_id) {

    echo json_encode([
        "status" => true,
        "data" => []
    ]);
    exit;
}

$result = mysqli_query($conn, "

SELECT
    p.*,
    c.name AS category_name,
    comp.company_name,
    comp.gstin AS company_gstin,
    comp.gst_type

FROM products p

LEFT JOIN categories c
ON p.category_id = c.id

LEFT JOIN companies comp
ON p.company_id = comp.id

WHERE p.company_id = '$company_id'
AND p.is_deleted = 0

ORDER BY p.id DESC

");

$data = [];

while ($row = mysqli_fetch_assoc($result)) {

    $data[] = $row;

}

echo json_encode([
    "status" => true,
    "data" => $data
]);