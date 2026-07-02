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

$company_id = intval($_GET['company_id'] ?? 0);

if (!$company_id) {
    echo json_encode([
        "status" => false,
        "message" => "company_id required",
        "data" => []
    ]);
    exit;
}

$sql = "
SELECT
    b.id,
    b.name,
    b.status,
    b.category_id,
    b.subcategory_id,
    c.name AS category_name,
    s.name AS subcategory_name

FROM brands b

LEFT JOIN categories c
ON b.category_id = c.id

LEFT JOIN subcategories s
ON b.subcategory_id = s.id

WHERE
b.company_id = ?
AND b.is_deleted = 0

ORDER BY b.id DESC
";

$stmt = $conn->prepare($sql);
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

$stmt->close();
$conn->close();

?>