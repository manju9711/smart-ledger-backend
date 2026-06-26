<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include "../../config/db.php";

$admin_id = intval($_GET['admin_id'] ?? 0);

if(!$admin_id){
    echo json_encode([
        "status"=>false,
        "message"=>"Admin id required"
    ]);
    exit;
}

$sql = "

SELECT

c.company_name,

cu.id customer_id,
cu.name customer,

i.invoice_no,
i.balance_amount outstanding,
i.due_date

FROM invoices i

INNER JOIN companies c
ON c.id=i.company_id

INNER JOIN customers cu
ON cu.id=i.customer_id

WHERE

c.admin_id='$admin_id'

AND i.balance_amount>0

AND CURDATE()>i.due_date

ORDER BY i.due_date ASC

";

$res=mysqli_query($conn,$sql);

$list=[];

while($row=mysqli_fetch_assoc($res)){

    $list[]=$row;

}

echo json_encode([
    "status"=>true,
    "count"=>count($list),
    "data"=>$list
]);

?>