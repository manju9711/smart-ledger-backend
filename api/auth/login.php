<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

include "../../config/db.php";

// ✅ PREFLIGHT
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ✅ GET JSON DATA
$data = json_decode(file_get_contents("php://input"), true);

$email    = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');

// ✅ VALIDATION
if (!$email || !$password) {

    echo json_encode([
        "status" => false,
        "message" => "Email & Password required"
    ]);

    exit;
}

//
// 🔥 1. CHECK USERS TABLE
// (superadmin / admin / cashier)
//

$user_q = mysqli_query($conn, "
    SELECT * FROM users
    WHERE email='$email'
");

$user = mysqli_fetch_assoc($user_q);

// ✅ USER FOUND
if ($user) {

    // ❌ INACTIVE USER
    if ($user['status'] != 'active') {

        echo json_encode([
            "status" => false,
            "message" => "Your account is inactive. Contact admin."
        ]);

        exit;
    }

    // ✅ PASSWORD MATCH
    if (password_verify($password, $user['password'])) {

       echo json_encode([
    "status" => true,
    "role"   => $user['role'],
    "data"   => [
        "id"         => $user['id'],
        "name"       => $user['name'],
        "email"      => $user['email'],
        "company_id" => $user['company_id'],
        "admin_id"   => $user['admin_id']
    ]
]);

        exit;
    }
}

//
// 🔥 2. CHECK COMPANIES TABLE (ADMIN LOGIN)
//

$comp_q = mysqli_query($conn, "
    SELECT * FROM companies
    WHERE owner_email='$email'
");

$company = mysqli_fetch_assoc($comp_q);

// ✅ COMPANY FOUND
if ($company) {

    // OPTIONAL STATUS CHECK
    // if you have status column in companies table

    /*
    if ($company['status'] != 'active') {

        echo json_encode([
            "status" => false,
            "message" => "Company account inactive"
        ]);

        exit;
    }
    */

    // ✅ PASSWORD MATCH
    if (password_verify($password, $company['owner_password'])) {

        echo json_encode([
            "status" => true,
            "role"   => "admin",
            "data"   => [
                "id"         => $company['id'],
                "name"       => $company['company_name'],
                "email"      => $company['owner_email'],
                "company_id" => $company['id']
            ]
        ]);

        exit;
    }
}

//
// ❌ INVALID LOGIN
//

echo json_encode([
    "status" => false,
    "message" => "Invalid credentials"
]);

?>