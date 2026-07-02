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
    echo json_encode(["status" => false, "message" => "company_id required", "data" => []]);
    exit;
}

$sql = "SELECT sc.id, sc.name, sc.status, sc.category_id, c.name AS category_name
        FROM subcategories sc
        JOIN categories c ON c.id = sc.category_id
        WHERE sc.company_id = ? AND sc.is_deleted = 0
        ORDER BY sc.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(["status" => true, "data" => $data]);

$stmt->close();
$conn->close();