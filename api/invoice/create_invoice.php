<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");

include "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$conn->begin_transaction();

try {

    /*
    ============================================
    GET DATA
    ============================================
    */

    $company_id     = intval($data['company_id']);
    $customer_id    = intval($data['customer_id']);
    $cashier_id     = intval($data['cashier_id']);

    $customer_name  = $conn->real_escape_string($data['customer_name']);
    $customer_phone = $conn->real_escape_string($data['customer_phone']);

    $products       = $data['products'];

    $sub_total      = floatval($data['sub_total']);
    $gst_total      = floatval($data['gst_total']);
    $total_amount   = floatval($data['total_amount']);

    $paid_amount    = floatval($data['paid_amount']);

    $payment_method = $conn->real_escape_string($data['payment_method']);
    $payment_type   = $conn->real_escape_string($data['payment_type']);

    $gst_type       = $conn->real_escape_string($data['gst_type']);
    $gst_no         = $conn->real_escape_string($data['gst_no']);

    /*
    ============================================
    GET CUSTOMER BALANCE
    ============================================
    */

    $getCustomer = $conn->query("
        SELECT advance_balance, pending_amount
        FROM customers
        WHERE id = '$customer_id'
    ");

    if (!$getCustomer || $getCustomer->num_rows == 0) {
        throw new Exception("Customer not found");
    }

    $customerData = $getCustomer->fetch_assoc();

    $currentAdvance = floatval($customerData['advance_balance']);
    $currentPending = floatval($customerData['pending_amount']);

    /*
    ============================================
    APPLY ADVANCE BALANCE
    ============================================
    */

    $advanceUsed = min($currentAdvance, $total_amount);

    $cashNeeded = $total_amount - $advanceUsed;

    /*
    ============================================
    CALCULATE EXTRA / PENDING
    ============================================
    */

    $extraAmount   = 0;
    $pendingAmount = 0;

    if ($payment_method != "credit") {

        if ($paid_amount > $cashNeeded) {

            $extraAmount = $paid_amount - $cashNeeded;

        } else if ($paid_amount < $cashNeeded) {

            $pendingAmount = $cashNeeded - $paid_amount;
        }

    } else {

        // Full amount pending for credit sale
        $pendingAmount = $total_amount;
        $advanceUsed   = 0;
    }

    /*
    ============================================
    FINAL CUSTOMER BALANCE
    ============================================
    */

    $newAdvance =
        ($currentAdvance - $advanceUsed) + $extraAmount;

    $newPending =
        $currentPending + $pendingAmount;

    /*
    ============================================
    GENERATE INVOICE NUMBER
    ============================================
    */

    $invoice_no = "INV" . time();

    /*
    ============================================
    PAYMENT STATUS
    ============================================
    */

    $payment_status = "paid";

    if ($pendingAmount > 0) {
        $payment_status = "partial";
    }

    if ($payment_method == "credit") {
        $payment_status = "unpaid";
    }

    /*
    ============================================
    INSERT INVOICE
    ============================================
    */

    $invoiceSql = "
        INSERT INTO invoices
        (
            invoice_no,
            company_id,
            customer_id,
            customer_name,
            customer_phone,
            cashier_id,

            sub_total,
            gst_total,
            total_amount,

            paid_amount,
            advance_used,
            extra_amount,
            pending_amount,

            payment_method,
            payment_type,
            payment_status,

            gst_type,
            gst_no,

            created_at
        )
        VALUES
        (
            '$invoice_no',
            '$company_id',
            '$customer_id',
            '$customer_name',
            '$customer_phone',
            '$cashier_id',

            '$sub_total',
            '$gst_total',
            '$total_amount',

            '$paid_amount',
            '$advanceUsed',
            '$extraAmount',
            '$pendingAmount',

            '$payment_method',
            '$payment_type',
            '$payment_status',

            '$gst_type',
            '$gst_no',

            NOW()
        )
    ";

    if (!$conn->query($invoiceSql)) {
        throw new Exception($conn->error);
    }

    $invoice_id = $conn->insert_id;

    /*
    ============================================
    INSERT PRODUCTS
    ============================================
    */

    foreach ($products as $p) {

        $product_id = intval($p['product_id']);
        $qty        = floatval($p['qty']);
        $price      = floatval($p['price']);
        $gst        = floatval($p['gst']);

        $baseAmount = $price * $qty;

        $gstAmount =
            $gst_type == "with_gst"
                ? ($baseAmount * $gst) / 100
                : 0;

        $rowTotal = $baseAmount + $gstAmount;

        /*
        INSERT ITEM
        */

        $itemSql = "
            INSERT INTO invoice_items
            (
                invoice_id,
                product_id,
                qty,
                price,
                gst_percentage,
                gst_amount,
                total_amount
            )
            VALUES
            (
                '$invoice_id',
                '$product_id',
                '$qty',
                '$price',
                '$gst',
                '$gstAmount',
                '$rowTotal'
            )
        ";

        if (!$conn->query($itemSql)) {
            throw new Exception($conn->error);
        }

        /*
        UPDATE STOCK
        */

        $stockSql = "
            UPDATE products
            SET stock = stock - $qty
            WHERE id = '$product_id'
        ";

        if (!$conn->query($stockSql)) {
            throw new Exception($conn->error);
        }
    }

    /*
    ============================================
    UPDATE CUSTOMER BALANCE
    ============================================
    */

    $updateCustomer = "
        UPDATE customers
        SET
            advance_balance = '$newAdvance',
            pending_amount = '$newPending'
        WHERE id = '$customer_id'
    ";

    if (!$conn->query($updateCustomer)) {
        throw new Exception($conn->error);
    }

    /*
    ============================================
    COMMIT
    ============================================
    */

    $conn->commit();

    echo json_encode([
        "status"          => true,
        "invoice_id"      => $invoice_id,
        "invoice_no"      => $invoice_no,

        "advance_used"    => $advanceUsed,
        "extra_amount"    => $extraAmount,
        "pending_amount"  => $pendingAmount,

        "new_advance"     => $newAdvance,
        "new_pending"     => $newPending,

        "message"         => "Invoice created successfully"
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "status"  => false,
        "message" => $e->getMessage()
    ]);
}
?>