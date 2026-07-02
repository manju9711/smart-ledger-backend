<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . "/../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$id             = intval($data["id"] ?? 0);
$name           = trim($data["name"] ?? "");
$category_id    = intval($data["category_id"] ?? 0);
$subcategory_id = intval($data["subcategory_id"] ?? 0);

if (!$id || !$name || !$category_id || !$subcategory_id) {

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
AND is_deleted = 0
AND id != ?
");

$stmt->bind_param(
    "siii",
    $name,
    $category_id,
    $subcategory_id,
    $id
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

/* Update */

$stmt = $conn->prepare("
UPDATE brands
SET
name = ?,
category_id = ?,
subcategory_id = ?
WHERE id = ?
");

$stmt->bind_param(
    "siii",
    $name,
    $category_id,
    $subcategory_id,
    $id
);

if ($stmt->execute()) {

    echo json_encode([
        "status" => true,
        "message" => "Brand updated successfully"
    ]);

} else {

    echo json_encode([
        "status" => false,
        "message" => $conn->error
    ]);

}

$stmt->close();
$conn->close();

?>