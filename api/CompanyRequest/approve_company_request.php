<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$request_id = intval($data['request_id'] ?? 0);

if (!$request_id) {

    echo json_encode([
        "status" => false,
        "message" => "Request ID required"
    ]);

    exit;
}

$requestQ = mysqli_query($conn, "

    SELECT *

    FROM company_requests

    WHERE id='$request_id'
    AND status='pending'

");

$request = mysqli_fetch_assoc($requestQ);

if (!$request) {

    echo json_encode([
        "status" => false,
        "message" => "Request not found"
    ]);

    exit;
}

mysqli_begin_transaction($conn);

try {

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
            logo,
            owner_name,
            owner_email,
            owner_password
        )

        VALUES
        (
            '".$request['admin_id']."',
            '".$request['company_name']."',
            '".$request['company_code']."',
            '".$request['company_address']."',
            '".$request['gstin']."',
            '".$request['gst_type']."',
            '".$request['phone']."',
            '".$request['logo']."',
            '".$request['owner_name']."',
            '".$request['owner_email']."',
            '".$request['owner_password']."'
        )

    ");

    if (!$insertCompany) {
        throw new Exception(mysqli_error($conn));
    }

    $company_id = mysqli_insert_id($conn);

    $insertUser = mysqli_query($conn, "

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
            '".$request['owner_name']."',
            '".$request['owner_email']."',
            '".$request['owner_password']."',
            'admin',
            '$company_id'
        )

    ");

    if (!$insertUser) {
        throw new Exception(mysqli_error($conn));
    }

    mysqli_query($conn, "

        UPDATE company_requests

        SET status='approved'

        WHERE id='$request_id'

    ");

    mysqli_commit($conn);

    echo json_encode([
        "status" => true,
        "message" => "Company request approved successfully"
    ]);

} catch (Exception $e) {

    mysqli_rollback($conn);

    echo json_encode([
        "status" => false,
        "message" => $e->getMessage()
    ]);
}