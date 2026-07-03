<?php
// /supplier_product/add.php
// Saves a product added by a SUPPLIER into a SEPARATE table (supplier_products)
// so it never mixes with the admin's main `products` table.

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

// ── Read & trim inputs ──────────────────────────────────
$supplier_id    = trim($data['supplier_id']    ?? '');
$product_name   = trim($data['product_name']   ?? '');
$product_code   = trim($data['product_code']   ?? '');
$category_id    = trim($data['category_id']    ?? '');
$subcategory_id = trim($data['subcategory_id'] ?? '');
$brand_id       = trim($data['brand_id']       ?? '');
$company_id     = trim($data['company_id']     ?? '');
$price          = trim($data['price']          ?? '');
$stock          = trim($data['stock']          ?? '');
$gst_percentage = trim($data['gst_percentage'] ?? '');
$barcode        = trim($data['barcode']        ?? '');
$unit           = trim($data['unit']           ?? '');

// ── Basic server-side validation ────────────────────────
if (
    $supplier_id === '' ||
    $product_name === '' ||
    $category_id === '' ||
    $subcategory_id === '' ||
    $brand_id === '' ||
    $company_id === '' ||
    $price === '' ||
    $stock === '' ||
    $unit === ''
) {
    echo json_encode([
        "status"  => false,
        "message" => "Missing required fields."
    ]);
    exit();
}

if (!is_numeric($price) || $price < 0) {
    echo json_encode(["status" => false, "message" => "Invalid price."]);
    exit();
}

if (!is_numeric($stock) || $stock < 0) {
    echo json_encode(["status" => false, "message" => "Invalid stock quantity."]);
    exit();
}

if ($gst_percentage !== '' && (!is_numeric($gst_percentage) || $gst_percentage < 0 || $gst_percentage > 100)) {
    echo json_encode(["status" => false, "message" => "Invalid GST percentage."]);
    exit();
}

// ── Duplicate product code check (within same company) ─
if ($product_code !== '') {
    $checkStmt = $conn->prepare(
        "SELECT id FROM supplier_products WHERE product_code = ? AND company_id = ? LIMIT 1"
    );
    $checkStmt->bind_param("si", $product_code, $company_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        echo json_encode([
            "status"  => false,
            "message" => "Product code already exists."
        ]);
        $checkStmt->close();
        exit();
    }
    $checkStmt->close();
}

// ── Insert into supplier_products (separate table) ─────
$stmt = $conn->prepare(
    "INSERT INTO supplier_products
        (supplier_id, product_name, product_code, category_id, subcategory_id, brand_id,
         company_id, price, stock, gst_percentage, barcode, unit, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
);

$stmt->bind_param(
    "issiiiiddsss",
    $supplier_id,
    $product_name,
    $product_code,
    $category_id,
    $subcategory_id,
    $brand_id,
    $company_id,
    $price,
    $stock,
    $gst_percentage,
    $barcode,
    $unit
);

if ($stmt->execute()) {
    echo json_encode([
        "status"      => true,
        "message"     => "Product added successfully.",
        "supplier_product_id" => $stmt->insert_id
    ]);
} else {
    echo json_encode([
        "status"  => false,
        "message" => "Failed to save product: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();