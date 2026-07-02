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
$role = $_GET['role'] ?? '';

if ($role !== "superadmin" && !$admin_id) {
    echo json_encode([
        "status" => false,
        "message" => "Admin ID required"
    ]);
    exit;
}

if ($role === "superadmin") {

    $sql = "
        SELECT id, company_name
        FROM companies
        WHERE status='active'
        ORDER BY company_name ASC
    ";

} else {

    $sql = "
        SELECT id, company_name
        FROM companies
        WHERE admin_id='$admin_id'
        AND status='active'
        ORDER BY company_name ASC
    ";

}

$result = mysqli_query($conn, $sql);

$data = [];

while ($row = mysqli_fetch_assoc($result)) {

    $data[] = $row;

}

echo json_encode([
    "status" => true,
    "data" => $data
]);