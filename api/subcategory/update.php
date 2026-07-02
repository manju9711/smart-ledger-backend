<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$id          = intval($data['id'] ?? 0);
$name        = trim($data['name'] ?? '');
$category_id = intval($data['category_id'] ?? 0);

if (!$id || !$name || !$category_id) {
    echo json_encode(["status" => false, "message" => "id, name & category_id are required"]);
    exit;
}

$stmt = $conn->prepare(
    "UPDATE subcategories SET name = ?, category_id = ? WHERE id = ?"
);
$stmt->bind_param("sii", $name, $category_id, $id);

if ($stmt->execute()) {
    echo json_encode(["status" => true, "message" => "Subcategory updated"]);
} else {
    echo json_encode(["status" => false, "message" => $conn->error]);
}

$stmt->close();
$conn->close();