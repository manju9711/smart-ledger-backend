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

$url = "https://graph.facebook.com/v23.0/859187723481196/messages";

$access_token = "EAAjPuWO18b0BRwGHlQLG0C8GBuKPs3wDkJQKCHQmbGhJ4tlR6uLqq6DKmgBJ8CkHtOmy9PCXWOVMZBop1ui2lkzoyG6V81rSOcl2fOyXPApAT11MWUXGhPEkNDAvAMig3iH4lbsGqNU9a4ZCrqDs4f0rRIXAKZBzz7M5yjUSZBUg9V4K9M2KjxUZCxMXhnul5AtbxIcoDIIvstyvCrHHDTyAZCfz7Irm0kaSLlrnu3Lc8wvHt4dmmybdNJZAGej73VVIoR0wbNzYDXkMANlAyaBor0G";

$headers = [
    "Authorization: Bearer " . $access_token,
    "Content-Type: application/json"
];

$body = [
    "messaging_product" => "whatsapp",
    "to" => "918754768231",
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