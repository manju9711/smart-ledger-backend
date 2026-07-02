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
    echo json_encode(["status" => false, "message" => "id required"]);
    exit;
}

$stmt = $conn->prepare(
    "SELECT sc.id, sc.name, sc.category_id, sc.company_id, sc.status, c.name AS category_name
     FROM subcategories sc
     JOIN categories c ON c.id = sc.category_id
     WHERE sc.id = ? AND sc.is_deleted = 0"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(["status" => true, "data" => $row]);
} else {
    echo json_encode(["status" => false, "message" => "Subcategory not found"]);
}

$stmt->close();
$conn->close();