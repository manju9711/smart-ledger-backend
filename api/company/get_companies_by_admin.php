<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include "../../config/db.php";

$admin_id = intval($_GET['admin_id'] ?? 0);

if (!$admin_id) {
    echo json_encode(["status" => false, "message" => "Admin ID required"]);
    exit;
}

$result = mysqli_query($conn, "
    SELECT id, company_name
    FROM companies
    WHERE admin_id = '$admin_id'
    ORDER BY company_name ASC
");

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode(["status" => true, "data" => $data]);
?>