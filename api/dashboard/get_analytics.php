<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include __DIR__ . '/../../config/db.php';

// 🔥 MONTH LIST
$months = [
    1=>"Jan",2=>"Feb",3=>"Mar",4=>"Apr",5=>"May",6=>"Jun",
    7=>"Jul",8=>"Aug",9=>"Sep",10=>"Oct",11=>"Nov",12=>"Dec"
];

// 🔥 DEFAULT 0 VALUES
$data = array_fill(1, 12, 0);

// 🔥 FETCH SALES
$res = $conn->query("
    SELECT MONTH(created_at) as month, SUM(total_amount) as total
    FROM invoices
    GROUP BY MONTH(created_at)
");

while($row = $res->fetch_assoc()){
    $data[(int)$row['month']] = (float)$row['total'];
}

// 🔥 FINAL FORMAT
$monthly = [];

foreach($data as $m => $val){
    $monthly[] = [
        "month" => $months[$m],
        "total" => $val
    ];
}

// RESPONSE
echo json_encode([
    "status" => true,
    "data" => [
        "monthly_sales" => $monthly
    ]
]);