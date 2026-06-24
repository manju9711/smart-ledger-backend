<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// 🔥 PREFLIGHT
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

// 🔥 GET DATA
$id              = $data['id'] ?? '';
$company_name    = trim($data['company_name'] ?? '');
$company_address = trim($data['company_address'] ?? '');
$company_code    = trim($data['company_code'] ?? '');
$gstin           = strtoupper(trim($data['gstin'] ?? ''));
$gst_type        = $data['gst_type'] ?? 'with_gst';
$phone           = trim($data['phone'] ?? '');
$logo            = $data['logo'] ?? '';



// ============================
// 🔥 VALIDATION
// ============================

$emailRegex = "/^[^\s@]+@[^\s@]+\.[^\s@]+$/";
$gstRegex   = "/^[0-9A-Z]{15}$/";
$phoneRegex = "/^[0-9]{10}$/";

if (!$id) {
    echo json_encode(["status"=>false,"message"=>"Company ID missing"]);
    exit();
}

if (!$company_name || !$company_code || !$company_address) {
    echo json_encode(["status"=>false,"message"=>"All company fields required"]);
    exit();
}

// GST validation
if ($gst_type == 'with_gst') {
    if (!$gstin || !preg_match($gstRegex, $gstin)) {
        echo json_encode(["status"=>false,"message"=>"Invalid GSTIN"]);
        exit();
    }
}

// Phone validation
if (!preg_match($phoneRegex, $phone)) {
    echo json_encode(["status"=>false,"message"=>"Phone must be 10 digits"]);
    exit();
}





// ============================
// 🔥 IMAGE UPLOAD
// ============================

$logo_query = "";

if (!empty($logo)) {
    $image = base64_decode($logo);

    if ($image === false) {
        echo json_encode(["status"=>false,"message"=>"Invalid image"]);
        exit();
    }

    $upload_dir = __DIR__ . "/../uploads/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_name = time() . ".png";
    $full_path = $upload_dir . $file_name;

    if (file_put_contents($full_path, $image) === false) {
        echo json_encode(["status"=>false,"message"=>"Image upload failed"]);
        exit();
    }

    $db_path = "uploads/" . $file_name;
    $logo_query = ", logo='$db_path'";
}

// ============================
// 🔥 TRANSACTION
// ============================

mysqli_begin_transaction($conn);

try {

    // ✅ UPDATE COMPANY
    $sql = "UPDATE companies SET 
        company_name='$company_name',
        company_address='$company_address',
        company_code='$company_code',
        gstin='$gstin',
        gst_type='$gst_type',
        phone='$phone'
        $logo_query
        WHERE id='$id'";

    if (!mysqli_query($conn, $sql)) {
        throw new Exception("Company update failed");
    }

    

   

    mysqli_commit($conn);

    echo json_encode([
        "status" => true,
        "message" => "Company updated successfully"
    ]);

} catch (Exception $e) {

    mysqli_rollback($conn);

    echo json_encode([
        "status" => false,
        "message" => $e->getMessage()
    ]);
}
?>