<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include __DIR__ . '/../../config/db.php';

// 💰 TOTAL SALES
$salesQuery = $conn->query("SELECT SUM(total_amount) as total_sales FROM invoices");
$sales = $salesQuery->fetch_assoc()['total_sales'] ?? 0;

// 📦 TOTAL PRODUCTS
$productQuery = $conn->query("SELECT COUNT(*) as total_products FROM products WHERE is_deleted = 0");
$totalProducts = $productQuery->fetch_assoc()['total_products'] ?? 0;

// ⚠️ LOW STOCK (stock < 5)
$lowStockQuery = $conn->query("SELECT COUNT(*) as low_stock FROM products WHERE stock < 5 AND is_deleted = 0");
$lowStock = $lowStockQuery->fetch_assoc()['low_stock'] ?? 0;

echo json_encode([
    "status" => true,
    "data" => [
        "total_sales" => $sales,
        "total_products" => $totalProducts,
        "low_stock" => $lowStock
    ]
]);