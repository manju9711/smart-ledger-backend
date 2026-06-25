<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$gstin = trim($data['gst_no'] ?? '');

if ($gstin == '') {
    echo json_encode([
        "status" => false,
        "message" => "GST Number required"
    ]);
    exit;
}

/* TEST KEY */
$apiKey = "key_live_7157232fd01340bab4657b0bbc90dbb4";
$apiSecret = "secret_live_aabc1f041b3b4544b99b6feab57faeae";

/* STEP 1 : AUTHENTICATE */
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.sandbox.co.in/authenticate",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "x-api-key: $apiKey",
        "x-api-secret: $apiSecret",
        "x-api-version: 1.0.0"
    ]
]);

$authResponse = curl_exec($curl);

curl_close($curl);

$authData = json_decode($authResponse, true);

$accessToken =
    $authData['access_token']
    ?? $authData['data']['access_token']
    ?? '';

if (!$accessToken) {

    echo json_encode([
        "status" => false,
        "step" => "auth",
        "response" => $authData
    ]);

    exit;
}

/* STEP 2 : GST SEARCH */

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.sandbox.co.in/gst/compliance/public/gstin/search",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "authorization: $accessToken",
        "x-api-key: $apiKey",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode([
        "gstin" => $gstin
    ])
]);

$response = curl_exec($curl);

$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

$api = json_decode($response, true);

if ($httpCode == 200 && isset($api['data'])) {

    echo json_encode([
        "status" => true,
        "business_name" => $api['data']['business_name'] ?? "",
        "data" => $api['data']
    ]);

} else {

    echo json_encode([
        "status" => false,
        "message" => "Invalid GST Number",
        "response" => $api
    ]);

}

exit;