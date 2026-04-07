<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? '';
$company_name = $data['company_name'] ?? '';
$company_address = $data['company_address'] ?? '';
$company_code = $data['company_code'] ?? '';
$gstin = $data['gstin'] ?? '';
$phone = $data['phone'] ?? '';
$logo = $data['logo'] ?? '';

if (!$id) {
    echo json_encode(["status"=>false,"message"=>"ID required"]);
    exit;
}

$logo_query = "";

// 🔥 IMAGE UPDATE
if (!empty($logo)) {
    $image = base64_decode($logo);
    $file_name = time() . ".png";
    $upload_dir = __DIR__ . "/../uploads/";
    $full_path = $upload_dir . $file_name;

    file_put_contents($full_path, $image);

    $db_path = "uploads/" . $file_name;
    $logo_query = ", logo='$db_path'";
}

// 🔥 TRANSACTION
mysqli_begin_transaction($conn);

try {

    // ✅ COMPANY UPDATE
    $sql = "UPDATE companies SET 
        company_name='$company_name',
        company_address='$company_address',
        company_code='$company_code',
        gstin='$gstin',
        phone='$phone'
        $logo_query
        WHERE id='$id'";

    if (!mysqli_query($conn, $sql)) {
        throw new Exception("Company update failed");
    }

    // ✅ ADMIN USER UPDATE
    $admin_sql = "UPDATE users SET 
        name='$company_name Admin',
        email='$company_code@admin.com'
        WHERE company_id='$id' AND role='admin'";

    if (!mysqli_query($conn, $admin_sql)) {
        throw new Exception("Admin update failed");
    }

    mysqli_commit($conn);

    echo json_encode([
        "status"=>true,
        "message"=>"Updated Successfully"
    ]);

} catch (Exception $e) {

    mysqli_rollback($conn);

    echo json_encode([
        "status"=>false,
        "message"=>$e->getMessage()
    ]);
}
?>