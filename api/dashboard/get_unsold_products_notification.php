<?php

ini_set("display_errors",1);
error_reporting(E_ALL);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");

if($_SERVER["REQUEST_METHOD"]=="OPTIONS"){
    http_response_code(200);
    exit();
}

include "../../config/db.php";

$company_id = intval($_GET["company_id"] ?? 0);

if(!$company_id){
    echo json_encode([
        "status"=>false,
        "message"=>"Company ID required"
    ]);
    exit;
}

/* -----------------------------
   Get all active products
------------------------------ */

$productRes = mysqli_query($conn,"
SELECT
id,
product_name,
created_at
FROM products
WHERE company_id='$company_id'
AND status='active'
AND is_deleted=0
");

$products=[];

while($row=mysqli_fetch_assoc($productRes)){
   $products[$row["id"]] = [
    "id"=>$row["id"],
    "product_name"=>$row["product_name"],
    "created_at"=>$row["created_at"],
    "last_sale"=>null
];
}

/* -----------------------------
   Read all invoices
------------------------------ */

$invoiceRes = mysqli_query($conn,"
SELECT
products,
created_at
FROM invoices
WHERE company_id='$company_id'
");

while($invoice=mysqli_fetch_assoc($invoiceRes)){

    $items=json_decode($invoice["products"],true);

    if(!is_array($items)){
        continue;
    }

    foreach($items as $item){

        $pid=intval($item["product_id"]);

        if(isset($products[$pid])){

            if(
                $products[$pid]["last_sale"]==null ||
                strtotime($invoice["created_at"]) >
                strtotime($products[$pid]["last_sale"])
            ){

                $products[$pid]["last_sale"]=$invoice["created_at"];

            }

        }

    }

}

/* -----------------------------
   Filter 3 months no billing
------------------------------ */

$result=[];

foreach($products as $p){

   if($p["last_sale"]==null){

    $days = floor(
        (time() - strtotime($p["created_at"]))
        /(60*60*24)
    );

    if($days >= 30){

        $result[]=[
            "product_name"=>$p["product_name"],
            "last_sale"=>"Never Billed",
            "days"=>$days
        ];

    }

    continue;

}

   

}

echo json_encode([
    "status"=>true,
    "count"=>count($result),
    "data"=>$result
]);

?>