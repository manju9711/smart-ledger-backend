<?php

include "../../config/db.php";

$res = mysqli_query($conn,"

SELECT
cr.*,
u.name admin_name

FROM company_requests cr

LEFT JOIN users u
ON cr.admin_id=u.id

WHERE cr.status='pending'

ORDER BY cr.id DESC

");

$data=[];

while($row=mysqli_fetch_assoc($res)){
$data[]=$row;
}

echo json_encode([
"status"=>true,
"data"=>$data
]);