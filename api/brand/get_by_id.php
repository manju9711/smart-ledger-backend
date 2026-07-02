<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . "/../../config/db.php";

$id = intval($_GET["id"] ?? 0);

if (!$id) {

    echo json_encode([
        "status" => false,
        "message" => "id required"
    ]);

    exit;
}

$stmt = $conn->prepare("
SELECT
    b.id,
    b.name,
    b.company_id,
    b.category_id,
    b.subcategory_id,
    b.status,
    c.name AS category_name,
    s.name AS subcategory_name

FROM brands b

LEFT JOIN categories c
ON b.category_id = c.id

LEFT JOIN subcategories s
ON b.subcategory_id = s.id

WHERE
b.id = ?
AND b.is_deleted = 0
");

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {

    echo json_encode([
        "status" => true,
        "data" => $row
    ]);

} else {

    echo json_encode([
        "status" => false,
        "message" => "Brand not found"
    ]);

}

$stmt->close();
$conn->close();

?>