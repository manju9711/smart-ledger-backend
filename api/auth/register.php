<?php
// 🔥 CORS HEADERS (VERY IMPORTANT)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// 🔥 HANDLE PREFLIGHT (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include "../../config/db.php";

// 🔥 GET JSON INPUT
$data = json_decode(file_get_contents("php://input"), true);

$name       = $data['name'] ?? '';
$email      = $data['email'] ?? '';
$password   = $data['password'] ?? '';
$role       = $data['role'] ?? '';
$company_id = $data['company_id'] ?? null;

// 🔥 VALIDATION
if (!$name || !$email || !$password || !$role) {
    echo json_encode(["status"=>false,"message"=>"All fields required"]);
    exit;
}

// 🔥 ROLE CHECK
if (!in_array($role, ['superadmin','cashier','admin'])) {
    echo json_encode(["status"=>false,"message"=>"Invalid role"]);
    exit;
}

// 🔥 COMPANY CHECK
if (($role == 'cashier' || $role == 'admin') && !$company_id) {
    echo json_encode(["status"=>false,"message"=>"Company ID required"]);
    exit;
}

// 🔥 EMAIL CHECK
$check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
if (mysqli_num_rows($check) > 0) {
    echo json_encode(["status"=>false,"message"=>"Email already exists"]);
    exit;
}

// 🔥 HASH PASSWORD
$hashed = password_hash($password, PASSWORD_DEFAULT);

// 🔥 START TRANSACTION
mysqli_begin_transaction($conn);

try {

    // ✅ INSERT USER
    $sql = "INSERT INTO users (name,email,password,role,company_id)
            VALUES ('$name','$email','$hashed','$role','$company_id')";

    if (!mysqli_query($conn, $sql)) {
        throw new Exception("User insert failed");
    }

    // ✅ ADMIN → UPDATE COMPANY
    if ($role == 'admin') {

        $update = "UPDATE companies SET
                   owner_name='$name',
                   owner_email='$email',
                   owner_password='$hashed'
                   WHERE id='$company_id'";

        if (!mysqli_query($conn, $update)) {
            throw new Exception("Company update failed");
        }
    }

    // ✅ COMMIT
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