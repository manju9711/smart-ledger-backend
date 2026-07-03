<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$id     = intval($data['id'] ?? 0);
$status = $data['status'] ?? '';

if (!$id || !in_array($status, ['active', 'inactive'], true)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid data"
    ]);
    exit;
}

$stmt = $conn->prepare("UPDATE suppliers SET status = ? WHERE id = ? AND is_deleted = 0");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => $conn->error
    ]);
    exit;
}

$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Supplier status updated"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => $stmt->error
    ]);
}