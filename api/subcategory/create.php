<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
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

$name        = trim($data['name'] ?? '');
$category_id = intval($data['category_id'] ?? 0);
$company_id  = intval($data['company_id'] ?? 0);

if (!$name || !$category_id || !$company_id) {
    echo json_encode(["status" => false, "message" => "Name, Category & Company are required"]);
    exit;
}

// Duplicate check (prepared statement - safe from SQL injection)
$stmt = $conn->prepare(
    "SELECT id FROM subcategories 
     WHERE name = ? AND category_id = ? AND company_id = ? AND is_deleted = 0"
);
$stmt->bind_param("sii", $name, $category_id, $company_id);
$stmt->execute();
$dup = $stmt->get_result();

if ($dup->num_rows > 0) {
    echo json_encode(["status" => false, "message" => "Subcategory already exists under this category"]);
    exit;
}
$stmt->close();

$stmt = $conn->prepare(
    "INSERT INTO subcategories (name, category_id, company_id, status, is_deleted)
     VALUES (?, ?, ?, 'active', 0)"
);
$stmt->bind_param("sii", $name, $category_id, $company_id);

if ($stmt->execute()) {
    echo json_encode(["status" => true, "message" => "Subcategory added"]);
} else {
    echo json_encode(["status" => false, "message" => $conn->error]);
}

$stmt->close();
$conn->close();