<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$name         = trim($data['name'] ?? '');
$email        = trim($data['email'] ?? '');
$password     = $data['password'] ?? '';
$role         = $data['role'] ?? '';
$company_id   = intval($data['company_id'] ?? 0);
$requested_by = intval($data['requested_by'] ?? 0);

/* ==========================
   VALIDATION
========================== */

if (!$name || !$email || !$password || !$role) {
    echo json_encode([
        "status" => false,
        "message" => "All fields required"
    ]);
    exit;
}

if (!in_array($role, ['superadmin', 'admin', 'cashier'])) {
    echo json_encode([
        "status" => false,
        "message" => "Invalid role"
    ]);
    exit;
}

/* Cashier-ku mattum company required */
if ($role === 'cashier' && !$company_id) {
    echo json_encode([
        "status" => false,
        "message" => "Company ID required"
    ]);
    exit;
}

/* ==========================
   EMAIL CHECK
========================== */

$check = mysqli_query(
    $conn,
    "SELECT id FROM users WHERE email='$email'"
);

if (mysqli_num_rows($check) > 0) {
    echo json_encode([
        "status" => false,
        "message" => "Email already exists"
    ]);
    exit;
}

$hashed = password_hash($password, PASSWORD_DEFAULT);

/* ==========================
   CASHIER LIMIT CHECK
========================== */

if ($role === 'cashier') {

    $countRes = mysqli_query(
        $conn,
        "
        SELECT COUNT(*) as total
        FROM users
        WHERE company_id='$company_id'
        AND role='cashier'
        "
    );

    $countRow = mysqli_fetch_assoc($countRes);

    if ($countRow['total'] >= 3) {

        mysqli_query(
            $conn,
            "
            INSERT INTO cashier_requests
            (
                company_id,
                requested_by,
                name,
                email,
                password
            )
            VALUES
            (
                '$company_id',
                '$requested_by',
                '$name',
                '$email',
                '$hashed'
            )
            "
        );

        echo json_encode([
            "status" => false,
            "request_sent" => true,
            "message" => "Cashier limit reached. Request sent to Super Admin."
        ]);

        exit;
    }
}

/* ==========================
   INSERT USER
========================== */

mysqli_begin_transaction($conn);

try {

    if ($role === 'admin' || $role === 'superadmin') {

        $sql = "
            INSERT INTO users
            (
                name,
                email,
                password,
                role,
                company_id
            )
            VALUES
            (
                '$name',
                '$email',
                '$hashed',
                '$role',
                NULL
            )
        ";

    } else {

        $sql = "
            INSERT INTO users
            (
                name,
                email,
                password,
                role,
                company_id
            )
            VALUES
            (
                '$name',
                '$email',
                '$hashed',
                '$role',
                '$company_id'
            )
        ";
    }

    if (!mysqli_query($conn, $sql)) {
        throw new Exception(mysqli_error($conn));
    }

    mysqli_commit($conn);

    echo json_encode([
        "status" => true,
        "message" => ucfirst($role) . " created successfully"
    ]);

} catch (Exception $e) {

    mysqli_rollback($conn);

    echo json_encode([
        "status" => false,
        "message" => $e->getMessage()
    ]);
}

?>