<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$admin_id = intval($data['admin_id'] ?? 0);

// COMPANY DATA
$company_name    = trim($data['company_name'] ?? '');
$company_address = trim($data['company_address'] ?? '');
$company_code    = trim($data['company_code'] ?? '');
$gstin           = strtoupper(trim($data['gstin'] ?? ''));
$gst_type        = $data['gst_type'] ?? 'with_gst';
$phone           = trim($data['phone'] ?? '');
$logo            = $data['logo'] ?? '';

$db_path = '';

// UPLOAD LOGO
if (!empty($logo)) {

    $image = base64_decode($logo);

    if ($image !== false) {

        $upload_dir = __DIR__ . "/../uploads/";

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . ".png";

        $full_path = $upload_dir . $file_name;

        if (file_put_contents($full_path, $image)) {
            $db_path = "uploads/" . $file_name;
        }
    }
}

// VALIDATION

$gstRegex   = "/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/";
$phoneRegex = "/^[0-9]{10}$/";

if (!$company_name || !$company_code || !$company_address) {

    echo json_encode([
        "status" => false,
        "message" => "All company fields required"
    ]);
    exit;
}

if ($gst_type == 'with_gst') {

    if (!$gstin || !preg_match($gstRegex, $gstin)) {

        echo json_encode([
            "status" => false,
            "message" => "Invalid GSTIN"
        ]);
        exit;
    }
}

if (!preg_match($phoneRegex, $phone)) {

    echo json_encode([
        "status" => false,
        "message" => "Phone must be 10 digits"
    ]);
    exit;
}


// CHECK COMPANY LIMIT

if ($admin_id > 0) {

    $countQ = mysqli_query($conn, "
        SELECT COUNT(*) total
        FROM companies
        WHERE admin_id='$admin_id'
        AND is_deleted='0'
    ");

    $countRow = mysqli_fetch_assoc($countQ);

    if ($countRow['total'] >= 3) {

        // CHECK DUPLICATE REQUEST

        $checkReq = mysqli_query($conn, "
            SELECT id
            FROM company_requests
            WHERE admin_id='$admin_id'
            AND company_name='$company_name'
            AND status='pending'
        ");

        if (mysqli_num_rows($checkReq) > 0) {

            echo json_encode([
                "status" => false,
                "message" => "Request already pending"
            ]);
            exit;
        }

        // SAVE REQUEST

        $reqInsert = mysqli_query($conn, "

            INSERT INTO company_requests
            (
                admin_id,
                company_name,
                company_code,
                company_address,
                gstin,
                gst_type,
                phone,
                logo,
                status
            )

            VALUES
            (
                '$admin_id',
                '$company_name',
                '$company_code',
                '$company_address',
                '$gstin',
                '$gst_type',
                '$phone',
                '$logo',
                'pending'
            )

        ");

        if (!$reqInsert) {

            echo json_encode([
                "status" => false,
                "message" => mysqli_error($conn)
            ]);
            exit;
        }

        echo json_encode([
            "status" => false,
            "request_sent" => true,
            "message" => "Maximum 3 companies reached. Request sent to Super Admin."
        ]);

        exit;
    }
}


// NORMAL COMPANY CREATE

$insertCompany = mysqli_query($conn, "

    INSERT INTO companies
    (
        admin_id,
        company_name,
        company_code,
        company_address,
        gstin,
        gst_type,
        phone,
        logo
    )

    VALUES
    (
        '$admin_id',
        '$company_name',
        '$company_code',
        '$company_address',
        '$gstin',
        '$gst_type',
        '$phone',
        '$db_path'
    )

");

if (!$insertCompany) {

    echo json_encode([
        "status" => false,
        "message" => "Company insert failed",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

echo json_encode([
    "status" => true,
    "message" => "Company created successfully"
]);

exit();