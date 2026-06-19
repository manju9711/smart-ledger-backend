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

$gstin = trim($data['gst_no'] ?? '');

if ($gstin == '') {
    echo json_encode([
        "status" => false,
        "message" => "GST Number required"
    ]);
    exit;
}

$apiKey = "key_live_0e188acd02be48128fdc290fe337b22e";
$apiSecret = "secret_live_e01a4f9cf5ef461ca0103a9de306b155";

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.sandbox.co.in/gst/compliance/public/gstin/" . $gstin,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "x-api-key: $apiKey",
        "x-api-secret: $apiSecret",
        "Accept: application/json"
    ]
]);

$response = curl_exec($curl);

if (curl_errno($curl)) {
    echo json_encode([
        "status" => false,
        "curl_error" => curl_error($curl)
    ]);
    curl_close($curl);
    exit;
}

$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

echo json_encode([
    "http_code" => $httpCode,
    "api_response" => json_decode($response, true),
    "raw_response" => $response
]);

exit;