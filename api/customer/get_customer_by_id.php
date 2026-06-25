<?php
ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }

include "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);
$id   = intval($data['id'] ?? 0);

if (!$id) {
    echo json_encode(["status" => false, "message" => "ID required"]);
    exit;
}

$res = $conn->query("
    SELECT c.*
    FROM customers c
    WHERE c.id = '$id'
    LIMIT 1
");

if ($res->num_rows === 0) {
    echo json_encode(["status" => false, "message" => "Not found"]);
    exit;
}

echo json_encode(["status" => true, "data" => $res->fetch_assoc()]);
?>