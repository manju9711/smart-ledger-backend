<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "../../config/db.php";

// 🔥 GET PARAM
$id = intval($_GET['id'] ?? 0);

if (!$id) {
    echo json_encode([
        "status"=>false,
        "message"=>"Product ID required"
    ]);
    exit;
}

// 🔥 FETCH PRODUCT
$result = mysqli_query($conn, "
SELECT p.*, c.name as category_name 
FROM products p
JOIN categories c ON p.category_id = c.id
WHERE p.id='$id' AND p.is_deleted=0
");

$product = mysqli_fetch_assoc($result);

if (!$product) {
    echo json_encode([
        "status"=>false,
        "message"=>"Product not found"
    ]);
    exit;
}

echo json_encode([
    "status"=>true,
    "data"=>$product
]);
?>