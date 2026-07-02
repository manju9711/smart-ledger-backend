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

$company_id  = intval($_GET['company_id'] ?? 0);
$category_id = intval($_GET['category_id'] ?? 0);

if (!$company_id) {
    echo json_encode([
        "status" => false,
        "message" => "company_id required"
    ]);
    exit;
}

$sql = "
SELECT
    id,
    name,
    category_id,
    status
FROM subcategories
WHERE company_id = ?
AND is_deleted = 0
AND status = 'active'
";

if ($category_id > 0) {
    $sql .= " AND category_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $company_id, $category_id);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $company_id);
}

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