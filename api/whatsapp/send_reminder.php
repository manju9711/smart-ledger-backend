<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include "../../config/db.php";

$data = json_decode(
    file_get_contents("php://input"),
    true
);

$invoice_no = $data['invoice_no'] ?? '';

$url = "https://graph.facebook.com/v23.0/<WHATSAPP_BUSINESS_PHONE_NUMBER_ID>/messages";

$headers = [
    "Authorization: Bearer <ACCESS_TOKEN>",
    "Content-Type: application/json"
];

$body = [
    "messaging_product" => "whatsapp",
    "to" => "<WHATSAPP_USER_PHONE_NUMBER>",
    "type" => "template",
    "template" => [
        "name" => "hello_world",
        "language" => [
            "code" => "en_US"
        ]
    ]
];

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

$response = curl_exec($ch);

curl_close($ch);

echo $response;