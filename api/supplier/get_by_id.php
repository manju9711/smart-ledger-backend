<?php
// 🔥 CORS HEADERS
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, OPTIONS");

// 🔥 PREFLIGHT
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . '/../../config/db.php';

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    echo json_encode(["status" => false, "message" => "ID required"]);
    exit;
}

$stmt = $conn->prepare(
    "SELECT * FROM suppliers WHERE id = ? AND is_deleted = 0"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    echo json_encode(["status" => false, "message" => "Supplier not found"]);
    exit;
}

echo json_encode(["status" => true, "data" => $data]);