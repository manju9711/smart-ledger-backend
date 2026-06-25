<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include "../../config/db.php";

$data = json_decode(
    file_get_contents("php://input"),
    true
);

$invoice_no = $data['invoice_no'] ?? '';
$phone = $data['phone'] ?? '';
$name = $data['name'] ?? '';
$amount = $data['amount'] ?? '';
$due_date = $data['due_date'] ?? '';
$template_name = $data['template_name'] ?? 'payment_reminder'; // Can fall back to hello_world if needed

// If invoice_no is provided, fetch values from database as source of truth
if (!empty($invoice_no) && isset($conn)) {
    $stmt = $conn->prepare("SELECT customer_name, customer_phone, balance_amount, due_date FROM invoices WHERE invoice_no = ?");
    if ($stmt) {
        $stmt->bind_param("s", $invoice_no);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $phone = $row['customer_phone'];
            $name = $row['customer_name'];
            $amount = $row['balance_amount'];
            $due_date = $row['due_date'];
        }
        $stmt->close();
    }
}

// Clean and validate the phone number
$phone = preg_replace('/[^0-9]/', '', $phone);
if (strlen($phone) === 10) {
    $phone = "91" . $phone; // Prepend India country code if 10 digits
}

if (empty($phone)) {
    echo json_encode([
        "status" => false,
        "message" => "Valid phone number is required."
    ]);
    exit();
}

// Format the due date for human reading (e.g. 24-Jun)
$formatted_date = "-";
if (!empty($due_date)) {
    $timestamp = strtotime($due_date);
    if ($timestamp) {
        $formatted_date = date("d-M-Y", $timestamp);
    } else {
        $formatted_date = $due_date;
    }
}

$url = "https://graph.facebook.com/v25.0/1116712711534650/messages";

$access_token = "EAAb2O1UXpDsBR4z1tNDLiDeBt1TZACfakVv0ZAhizbSpWn6REDGZCfPCWE9a98QeVsqz30NpLidAivteuFtZCGgkJL0lSavrgel0qrIwCy7oZB36zatUNHISZBNRZAPMXMY8r45gsLDaCSSmJUEXmleI7sk7S4kzZCw3ZAJULvyxTq1rrfdFOYFq44ZAokZB2Rlon2Ve5vaXUbLJ3PfK9FGwuADwqhNXyL6xkBYk2vhCoEc0fF8ytlOJmtdw7VlJXEnaDRoBZBml7iFeSitpa1zKY4UwNMjG";

$headers = [
    "Authorization: Bearer " . $access_token,
    "Content-Type: application/json"
];

// Build the template details
$template_data = [
    "name" => $template_name,
    "language" => [
        "code" => "en_US"
    ]
];

// Only add components/parameters for templates other than hello_world
if ($template_name !== 'hello_world') {
    $template_data["components"] = [
        [
            "type" => "body",
            "parameters" => [
                [
                    "type" => "text",
                    "text" => $name
                ],
                [
                    "type" => "text",
                    "text" => "₹" . number_format((float)$amount, 2)
                ],
                [
                    "type" => "text",
                    "text" => $formatted_date
                ]
            ]
        ]
    ];
}

$body = [
    "messaging_product" => "whatsapp",
    "to" => $phone,
    "type" => "template",
    "template" => $template_data
];

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bypasses "SSL certificate problem: unable to get local issuer certificate" error in local WampServer/localhost environments

$response = curl_exec($ch);

if (curl_errno($ch)) {
    $error_msg = curl_error($ch);
    echo json_encode([
        "status" => false,
        "message" => "Curl error: " . $error_msg
    ]);
} else {
    echo $response;
}

curl_close($ch);
?>