<?php

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

$name       = $data['name'] ?? '';
$email      = $data['email'] ?? '';
$password   = $data['password'] ?? '';
$role       = $data['role'] ?? '';
$admin_id = intval($data['admin_id'] ?? 0);
$requested_by = intval($data['requested_by'] ?? 0);

if (!$name || !$email || !$password || !$role) {

    echo json_encode([
        "status"=>false,
        "message"=>"All fields required"
    ]);

    exit;
}

if (!in_array($role, ['superadmin','cashier','admin'])) {

    echo json_encode([
        "status"=>false,
        "message"=>"Invalid role"
    ]);

    exit;
}

if ($role == 'cashier' && !$admin_id) {

    echo json_encode([
        "status"=>false,
        "message"=>"Admin ID required"
    ]);

    exit;
}

$check = mysqli_query($conn,
"SELECT id FROM users WHERE email='$email'");

if (mysqli_num_rows($check) > 0) {

    echo json_encode([
        "status"=>false,
        "message"=>"Email already exists"
    ]);

    exit;
}

$hashed = password_hash($password, PASSWORD_DEFAULT);


// ✅ CASHIER LIMIT CHECK

if ($role == 'cashier') {

    $countRes = mysqli_query($conn, "

        SELECT COUNT(*) as total
        FROM users
        WHERE admin_id='$admin_id'
        AND role='cashier'

    ");

    $countRow = mysqli_fetch_assoc($countRes);

    // ✅ LIMIT = 3

    if ($countRow['total'] >= 3) {

        // SAVE REQUEST

        mysqli_query($conn, "

           INSERT INTO cashier_requests
(
    admin_id,
    requested_by,
    name,
    email,
    password
)

            VALUES
            (
               '$admin_id',
                '$requested_by',
                '$name',
                '$email',
                '$hashed'
            )

        ");

        echo json_encode([
            "status"=>false,
            "request_sent"=>true,
            "message"=>"Cashier limit reached. Request sent to Super Admin."
        ]);

        exit;
    }
}


// ✅ NORMAL REGISTER

mysqli_begin_transaction($conn);

try {

    $sql = "

       INSERT INTO users
(
    name,
    email,
    password,
    role,
    admin_id
)
VALUES
(
    '$name',
    '$email',
    '$hashed',
    '$role',
    '$admin_id'
)
    ";

    if (!mysqli_query($conn, $sql)) {
        throw new Exception("User insert failed");
    }

    if ($role == 'admin') {

        $update = "

            UPDATE companies SET

            owner_name='$name',
            owner_email='$email',
            owner_password='$hashed'

            WHERE id='$company_id'

        ";

        if (!mysqli_query($conn, $update)) {
            throw new Exception("Company update failed");
        }
    }

    mysqli_commit($conn);

    echo json_encode([
        "status"=>true,
        "message"=>"User registered successfully"
    ]);

} catch (Exception $e) {

    mysqli_rollback($conn);

    echo json_encode([
        "status"=>false,
        "message"=>$e->getMessage()
    ]);
}
?>