<?php

include '../includes/connection.php';
include '../config.php';
include 'esewa-config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$booking_id = (int)($_GET['booking_id'] ?? 0);
$amount     = (float)($_GET['amount'] ?? 0);

if (!$booking_id || !$amount) {
    die("Invalid booking or amount.");
}

/*
|--------------------------------------------------------------------------
| UNIQUE Transaction UUID
|--------------------------------------------------------------------------
| Example:
| 10_1747569281
|--------------------------------------------------------------------------
*/

$transaction_uuid = $booking_id . '_' . time();

/*
|--------------------------------------------------------------------------
| Amount Details
|--------------------------------------------------------------------------
*/
$total_amount = $amount;
$tax_amount = 0;
$product_service_charge = 0;
$product_delivery_charge = 0;

/*
|--------------------------------------------------------------------------
| Generate Signature
|--------------------------------------------------------------------------
*/

$message = "total_amount={$total_amount},transaction_uuid={$transaction_uuid},product_code=" . ESEWA_MERCHANT_CODE;

$signature = base64_encode(
    hash_hmac(
        'sha256',
        $message,
        ESEWA_SECRET_KEY,
        true
    )
);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to eSewa...</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .loader-box {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            text-align: center;
            width: 350px;
        }

        .loader {
            width: 55px;
            height: 55px;
            border: 5px solid #e2e8f0;
            border-top: 5px solid #16a34a;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: auto auto 20px;
        }

        h2 {
            margin-bottom: 10px;
            color: #0f172a;
        }

        p {
            color: #64748b;
            font-size: 14px;
        }

        @keyframes spin {
            100% {
                transform: rotate(360deg);
            }
        }
    </style>

</head>

<body>

    <div class="loader-box">
        <div class="loader"></div>

        <h2>Redirecting to eSewa</h2>

        <p>Please wait while we connect your payment securely...</p>
    </div>

    <form
        id="esewaForm"
        action="<?= ESEWA_PAYMENT_URL ?>"
        method="POST">

        <input type="hidden" name="amount" value="<?= $amount ?>">

        <input type="hidden" name="tax_amount" value="<?= $tax_amount ?>">

        <input type="hidden" name="total_amount" value="<?= $total_amount ?>">

        <input type="hidden" name="transaction_uuid" value="<?= $transaction_uuid ?>">

        <input type="hidden" name="product_code" value="<?= ESEWA_MERCHANT_CODE ?>">

        <input type="hidden" name="product_service_charge" value="<?= $product_service_charge ?>">

        <input type="hidden" name="product_delivery_charge" value="<?= $product_delivery_charge ?>">

        <input type="hidden" name="success_url" value="<?= ESEWA_SUCCESS_URL ?>">

        <input type="hidden" name="failure_url" value="<?= ESEWA_FAILURE_URL ?>?booking_id=<?= $booking_id ?>">

        <input type="hidden"
            name="signed_field_names"
            value="total_amount,transaction_uuid,product_code">

        <input type="hidden"
            name="signature"
            value="<?= $signature ?>">

    </form>

    <script>
        document.getElementById('esewaForm').submit();
    </script>

</body>

</html>