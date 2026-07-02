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

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    echo json_encode([
        "status" => false,
        "message" => "Product ID required"
    ]);
    exit;
}

$sql = "
SELECT
    p.*,
    c.name AS category_name,
    sc.name AS subcategory_name,
    b.name AS brand_name

FROM products p

LEFT JOIN categories c
ON p.category_id = c.id

LEFT JOIN subcategories sc
ON p.subcategory_id = sc.id

LEFT JOIN brands b
ON p.brand_id = b.id

WHERE
p.id = ?
AND p.is_deleted = 0
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {

    echo json_encode([
        "status" => false,
        "message" => "Product not found"
    ]);

    exit;
}

echo json_encode([
    "status" => true,
    "data" => $product
]);

$stmt->close();
$conn->close();