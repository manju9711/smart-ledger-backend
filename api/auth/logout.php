<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

include "../../config/db.php";

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$id   = intval($data['id'] ?? 0);
$role = trim($data['role'] ?? '');

if (!$id || !$role) {
    echo json_encode(["status" => false, "message" => "ID and role required"]);
    exit;
}

if ($role === "admin") {
    // admin logging in via companies table OR via users table
    // Try companies table first (owner-level admin)
    mysqli_query($conn, "UPDATE companies SET active_token=NULL WHERE id='$id'");
}

// Also clear from users table (covers admin-in-users-table, cashier, superadmin)
mysqli_query($conn, "UPDATE users SET active_token=NULL WHERE id='$id'");

echo json_encode(["status" => true, "message" => "Logged out"]);
?>