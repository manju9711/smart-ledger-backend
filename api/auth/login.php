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

$email    = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');

if (!$email || !$password) {
    echo json_encode([
        "status" => false,
        "message" => "Email & Password required"
    ]);
    exit;
}

//
// 🔥 1. CHECK USERS TABLE (superadmin / admin / cashier)
//
$email_esc = mysqli_real_escape_string($conn, $email);

$user_q = mysqli_query($conn, "
    SELECT * FROM users
    WHERE email='$email_esc'
");

$user = mysqli_fetch_assoc($user_q);

if ($user) {

    if ($user['status'] != 'active') {
        echo json_encode([
            "status" => false,
            "message" => "Your account is inactive. Contact super admin."
        ]);
        exit;
    }

    if (password_verify($password, $user['password'])) {

        // 🔒 ALREADY LOGGED IN ELSEWHERE?
        if (!empty($user['active_token'])) {
            echo json_encode([
                "status" => false,
                "message" => "This account is already logged in on another device. Please logout there first."
            ]);
            exit;
        }

        // ✅ Generate new session token
        $token = bin2hex(random_bytes(32));
        $user_id = intval($user['id']);

        mysqli_query($conn, "
            UPDATE users SET active_token='$token'
            WHERE id='$user_id'
        ");

        echo json_encode([
            "status" => true,
            "role"   => $user['role'],
            "token"  => $token,
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
    WHERE owner_email='$email_esc'
");

$company = mysqli_fetch_assoc($comp_q);

if ($company) {

    if (password_verify($password, $company['owner_password'])) {

        // 🔒 ALREADY LOGGED IN ELSEWHERE?
        if (!empty($company['active_token'])) {
            echo json_encode([
                "status" => false,
                "message" => "This account is already logged in on another device. Please logout there first."
            ]);
            exit;
        }

        $token = bin2hex(random_bytes(32));
        $company_id = intval($company['id']);

        mysqli_query($conn, "
            UPDATE companies SET active_token='$token'
            WHERE id='$company_id'
        ");

        echo json_encode([
            "status" => true,
            "role"   => "admin",
            "token"  => $token,
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

echo json_encode([
    "status" => false,
    "message" => "Invalid credentials"
]);

?>