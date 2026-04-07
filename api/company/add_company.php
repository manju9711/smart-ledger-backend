<?php
// 🔥 CORS HEADERS
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// 🔥 PREFLIGHT
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

// Company fields
$company_name    = $data['company_name'] ?? '';
$company_address = $data['company_address'] ?? '';
$company_code    = $data['company_code'] ?? '';
$gstin           = $data['gstin'] ?? '';
$phone           = $data['phone'] ?? '';
$logo            = $data['logo'] ?? '';

// 🔥 Owner fields
$owner_name     = $data['owner_name'] ?? '';
$owner_email    = $data['owner_email'] ?? '';
$owner_password = $data['owner_password'] ?? '';

// Validation
if (!$company_name || !$owner_name || !$owner_email || !$owner_password) {
    echo json_encode([
        "status" => false,
        "message" => "Company & Owner details required"
    ]);
    exit();
}

// Check email exists (users table)
$check = mysqli_query($conn, "SELECT id FROM users WHERE email='$owner_email'");
if (mysqli_num_rows($check) > 0) {
    echo json_encode([
        "status" => false,
        "message" => "Owner email already exists"
    ]);
    exit();
}

// 🔥 IMAGE UPLOAD
$upload_dir = __DIR__ . "/../uploads/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$logo_path = "";

if (!empty($logo)) {
    $image = base64_decode($logo);

    if ($image === false) {
        echo json_encode(["status"=>false,"message"=>"Invalid image"]);
        exit();
    }

    $file_name = time() . ".png";
    $full_path = $upload_dir . $file_name;

    if (file_put_contents($full_path, $image) === false) {
        echo json_encode(["status"=>false,"message"=>"Image save failed"]);
        exit();
    }

    $logo_path = "uploads/" . $file_name;
}

// 🔥 HASH PASSWORD (COMMON)
$hashed_password = password_hash($owner_password, PASSWORD_DEFAULT);

// 🔥 TRANSACTION
mysqli_begin_transaction($conn);

try {

    // ✅ 1. INSERT COMPANY (🔥 OWNER DETAILS ADD)
    $company_sql = "INSERT INTO companies 
    (company_name, company_address, company_code, gstin, phone, logo,
     owner_name, owner_email, owner_password) 
    VALUES 
    ('$company_name','$company_address','$company_code','$gstin','$phone','$logo_path',
     '$owner_name','$owner_email','$hashed_password')";

    if (!mysqli_query($conn, $company_sql)) {
        throw new Exception("Company insert failed");
    }

    $company_id = mysqli_insert_id($conn);

    // ✅ 2. INSERT ADMIN USER (users table)
    $user_sql = "INSERT INTO users 
    (name, email, password, role, company_id) 
    VALUES 
    ('$owner_name','$owner_email','$hashed_password','admin','$company_id')";

    if (!mysqli_query($conn, $user_sql)) {
        throw new Exception("Admin creation failed");
    }

    // ✅ COMMIT
    mysqli_commit($conn);

    echo json_encode([
        "status" => true,
        "message" => "Company + Admin Created Successfully",
        "company_id" => $company_id
    ]);

} catch (Exception $e) {

    mysqli_rollback($conn);

    echo json_encode([
        "status" => false,
        "message" => $e->getMessage()
    ]);
}
?>