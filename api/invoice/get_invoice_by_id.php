<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");

include __DIR__ . '/../../config/db.php';

$invoice_no = $_GET['id'];

// 🔥 JOIN COMPANY TABLE
$result = $conn->query("
SELECT i.*, 
       c.company_name,
       c.company_address,
       c.phone,
       c.gstin,
       c.logo,
        c.gst_type 
FROM invoices i
LEFT JOIN companies c ON i.company_id = c.id
WHERE i.invoice_no='$invoice_no'
");

if($result->num_rows > 0){
    $row = $result->fetch_assoc();

    $row['products'] = json_decode($row['products']);
    $pending_total = 0;

if (!empty($row['customer_id'])) {

    $pendingQry = $conn->query("
        SELECT SUM(balance_amount) AS total_pending
        FROM invoices
        WHERE customer_id = '{$row['customer_id']}'
        AND balance_amount > 0
    ");

    if ($pendingQry && $pendingQry->num_rows > 0) {
        $pending_total = floatval(
            $pendingQry->fetch_assoc()['total_pending']
        );
    }
}

$row['total_pending'] = $pending_total;

    
    

    echo json_encode([
        "status"=>true,
        "data"=>$row
    ]);
}else{
    echo json_encode([
        "status"=>false,
        "message"=>"Invoice not found"
    ]);
}
?>