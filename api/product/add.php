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

// Fields
$name = trim($data['product_name'] ?? '');
$product_code = trim($data['product_code'] ?? '');
$category_id = intval($data['category_id'] ?? 0);
$subcategory_id = intval($data['subcategory_id'] ?? 0);
$brand_id = intval($data['brand_id'] ?? 0);
$supplier_id = intval($data['supplier_id'] ?? 0);
$price = floatval($data['price'] ?? 0);
$stock = intval($data['stock'] ?? 0);
$unit = $data['unit'] ?? 'piece';
$gst = floatval($data['gst_percentage'] ?? 0);
$company_id = intval($data['company_id'] ?? 0);

// Validation
if (!$name || !$category_id || !$company_id || !$supplier_id) {
    echo json_encode([
        "status" => false,
        "message" => "Required fields missing"
    ]);
    exit;
}

// 🔥 CHECK CATEGORY EXISTS + MATCH COMPANY
$check = mysqli_query($conn, "SELECT id FROM categories 
WHERE id='$category_id' AND company_id='$company_id' AND is_deleted=0 AND status='active'" );

if (mysqli_num_rows($check) == 0) {
    echo json_encode([
        "status"=>false,
        "message"=>"Invalid category_id or company_id"
    ]);
    exit;
}


// 🔥 GENERATE SEQUENTIAL BARCODE (backend side)
function generateSequentialBarcode($conn) {
    $result = mysqli_query($conn, "SELECT barcode FROM products WHERE barcode LIKE 'PRD%' ORDER BY id DESC LIMIT 1");
    
    if ($row = mysqli_fetch_assoc($result)) {
        $lastNumber = intval(substr($row['barcode'], 3)); // "PRD" remove pannitu number eduthுக்கிறோம்
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 100001; // first barcode
    }

    return "PRD" . $newNumber;
}

$barcode = generateSequentialBarcode($conn);

// Insert
$sql = "INSERT INTO products
(
product_name,
product_code,
category_id,
subcategory_id,
brand_id,
supplier_id,
price,
stock,
barcode,
unit,
gst_percentage,
company_id
)
VALUES
(
'$name',
'$product_code',
'$category_id',
'$subcategory_id',
'$brand_id',
'$supplier_id',
'$price',
'$stock',
'$barcode',
'$unit',
'$gst',
'$company_id'
)";

if ($conn->query($sql)) {
    echo json_encode([
        "status" => true,
        "message" => "Product added",
        "barcode" => $barcode   // frontend ku return, success toast la kaata
    ]);
} else {
    echo json_encode(["status"=>false,"message"=>$conn->error]);
}
?>