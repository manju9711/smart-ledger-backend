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

$name           = trim($data['name'] ?? '');
$category_id    = intval($data['category_id'] ?? 0);
$subcategory_id = intval($data['subcategory_id'] ?? 0);
$company_id     = intval($data['company_id'] ?? 0);

if (!$name || !$category_id || !$subcategory_id || !$company_id) {
    echo json_encode([
        "status" => false,
        "message" => "All fields are required"
    ]);
    exit;
}

/* Duplicate Check */

$stmt = $conn->prepare("
SELECT id
FROM brands
WHERE
name = ?
AND category_id = ?
AND subcategory_id = ?
AND company_id = ?
AND is_deleted = 0
");

$stmt->bind_param(
    "siii",
    $name,
    $category_id,
    $subcategory_id,
    $company_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    echo json_encode([
        "status" => false,
        "message" => "Brand already exists"
    ]);

    exit;
}

$stmt->close();

/* Insert */

$stmt = $conn->prepare("
INSERT INTO brands
(
name,
category_id,
subcategory_id,
company_id,
status,
is_deleted
)
VALUES
(
?,
?,
?,
?,
'active',
0
)
");

$stmt->bind_param(
    "siii",
    $name,
    $category_id,
    $subcategory_id,
    $company_id
);

if ($stmt->execute()) {

    echo json_encode([
        "status" => true,
        "message" => "Brand added successfully"
    ]);

} else {

    echo json_encode([
        "status" => false,
        "message" => $conn->error
    ]);

}

$stmt->close();
$conn->close();