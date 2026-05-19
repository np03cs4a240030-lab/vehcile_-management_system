<?php

include '../includes/connection.php';
include '../config.php';
include 'esewa-config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

/*
|--------------------------------------------------------------------------
| 1. Get eSewa response data
|--------------------------------------------------------------------------
*/

$data = $_GET['data'] ?? '';

if (!$data) {
    die("Invalid payment response.");
}

/*
|--------------------------------------------------------------------------
| 2. Decode Base64 JSON response
|--------------------------------------------------------------------------
*/

$response = json_decode(base64_decode($data), true);

if (!$response) {
    die("Invalid payment data.");
}

/*
|--------------------------------------------------------------------------
| 3. Extract transaction details
|--------------------------------------------------------------------------
*/

$transaction_code = $response['transaction_code'] ?? '';
$status           = $response['status'] ?? '';
$total_amount     = $response['total_amount'] ?? 0;
$transaction_uuid = $response['transaction_uuid'] ?? '';

/*
|--------------------------------------------------------------------------
| 4. Extract booking ID
|--------------------------------------------------------------------------
*/

$parts = explode('_', $transaction_uuid);
$booking_id = (int)($parts[0] ?? 0);

if (!$booking_id) {
    die("Invalid booking ID.");
}

/*
|--------------------------------------------------------------------------
| 5. Fetch booking details
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT 
        b.*,
        u.name  AS user_name,
        u.email AS user_email,
        v.name  AS vehicle_name,
        v.location
    FROM bookings b
    JOIN users u    ON u.id = b.user_id
    JOIN vehicles v ON v.id = b.vehicle_id
    WHERE b.id = ?
    LIMIT 1
");

$stmt->bind_param("i", $booking_id);
$stmt->execute();

$booking = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$booking) {
    die("Booking not found.");
}

/*
|--------------------------------------------------------------------------
| 6. Update booking payment status
|--------------------------------------------------------------------------
*/

if ($booking['payment_status'] !== 'paid') {

    $update = $conn->prepare("
        UPDATE bookings
        SET 
            payment_status = 'paid',
            payment_method = 'eSewa',
            status         = 'confirmed',
            updated_at     = NOW()
        WHERE id = ?
    ");

    $update->bind_param("i", $booking_id);
    $update->execute();
    $update->close();

    // Send Email Notification
    require_once '../mailer.php';
    $subject = "Payment Successful - Booking #INV-" . str_pad($booking_id, 5, '0', STR_PAD_LEFT);
    
    // Construct email body with an inline-styled HTML invoice
    $invoice_no = "INV-" . str_pad($booking_id, 5, '0', STR_PAD_LEFT);
    $date_str = date('Y-m-d H:i');
    $user_name = htmlspecialchars($booking['user_name']);
    $user_email = htmlspecialchars($booking['user_email']);
    $vehicle_name = htmlspecialchars($booking['vehicle_name']);
    $period = $booking['start_date'] . ' to ' . $booking['end_date'];
    $location = htmlspecialchars($booking['location']);
    $amount = number_format($booking['total_price'], 2);
    $tx_code = htmlspecialchars($transaction_code);
    
    $emailBody = "
    <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;'>
        <div style='background-color: #f97316; padding: 20px; text-align: center; color: white;'>
            <h1 style='margin: 0; font-size: 24px;'>भटभटे Rental</h1>
            <p style='margin: 5px 0 0 0; opacity: 0.9;'>Payment Successful - Tax Invoice</p>
        </div>
        <div style='padding: 30px;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom: 20px;'>
                <tr>
                    <td style='vertical-align: top;'>
                        <h3 style='margin-top: 0; color: #64748b;'>BILLED TO</h3>
                        <p style='margin: 0;'><strong>{$user_name}</strong></p>
                        <p style='margin: 5px 0 0 0; color: #64748b;'>{$user_email}</p>
                    </td>
                    <td style='text-align: right; vertical-align: top;'>
                        <h3 style='margin-top: 0; color: #64748b;'>INVOICE DETAILS</h3>
                        <p style='margin: 0;'><strong>Invoice #:</strong> {$invoice_no}</p>
                        <p style='margin: 5px 0 0 0;'><strong>Date:</strong> {$date_str}</p>
                        <p style='margin: 5px 0 0 0;'><strong>Status:</strong> <span style='color: #16a34a;'>Paid</span> (eSewa)</p>
                        <p style='margin: 5px 0 0 0;'><strong>Txn ID:</strong> {$tx_code}</p>
                    </td>
                </tr>
            </table>
            
            <table width='100%' cellpadding='12' cellspacing='0' style='border-collapse: collapse; margin-bottom: 20px;'>
                <thead>
                    <tr style='background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;'>
                        <th>Vehicle & Details</th>
                        <th style='text-align: right;'>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style='border-bottom: 1px solid #e2e8f0;'>
                        <td>
                            <strong style='font-size: 16px;'>{$vehicle_name}</strong><br>
                            <span style='color: #64748b; font-size: 13px;'>Period: {$period}</span><br>
                            <span style='color: #64748b; font-size: 13px;'>Location: {$location}</span>
                        </td>
                        <td style='text-align: right; font-weight: bold;'>
                            NPR {$amount}
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <table width='100%' cellpadding='5' cellspacing='0'>
                <tr>
                    <td></td>
                    <td style='color: #64748b; text-align: right; width: 100px;'>Subtotal:</td>
                    <td style='text-align: right; width: 100px;'>NPR {$amount}</td>
                </tr>
                <tr>
                    <td></td>
                    <td style='color: #64748b; text-align: right;'>Tax (0%):</td>
                    <td style='text-align: right;'>NPR 0.00</td>
                </tr>
                <tr>
                    <td></td>
                    <td style='font-size: 16px; font-weight: bold; color: #f97316; padding-top: 10px; text-align: right;'>Total Paid:</td>
                    <td style='text-align: right; font-size: 16px; font-weight: bold; color: #f97316; padding-top: 10px;'>NPR {$amount}</td>
                </tr>
            </table>
        </div>
        <div style='background-color: #f1f5f9; padding: 20px; text-align: center; color: #64748b; font-size: 13px;'>
            Thank you for choosing Bhatbhatey Rental.<br>
            If you have any questions, please contact support@bhatbhatey.com.np
        </div>
    </div>
    ";
    
    sendMail($booking['user_email'], $subject, $emailBody);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Payment Successful - Bhatbhatey</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Plus Jakarta Sans',sans-serif;
    background:#f1f5f9;
    padding:40px 20px;
    color:#0f172a;
}

.container{
    max-width:1000px;
    margin:auto;
}

.top-actions{
    margin-bottom:20px;
}

.print-btn{
    background:#0f172a;
    color:white;
    border:none;
    padding:14px 22px;
    border-radius:10px;
    font-size:14px;
    font-weight:700;
    cursor:pointer;
    font-family:inherit;
}

.print-btn:hover{
    background:#1e293b;
}

.invoice{
    background:white;
    border-radius:20px;
    padding:50px;
    box-shadow:0 10px 35px rgba(0,0,0,0.06);
}

.header{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    margin-bottom:40px;
    flex-wrap:wrap;
    gap:20px;
}

.logo{
    font-size:42px;
    font-weight:800;
    color:#f97316;
    line-height:1;
}

.logo small{
    display:block;
    color:#64748b;
    font-size:14px;
    margin-top:10px;
    font-weight:500;
}

.invoice-info{
    text-align:right;
}

.invoice-info h2{
    font-size:36px;
    color:#64748b;
    margin-bottom:10px;
}

.invoice-info p{
    font-size:15px;
    margin-bottom:6px;
}

.divider{
    border:none;
    border-top:1px solid #e2e8f0;
    margin:35px 0;
}

.info-row{
    display:flex;
    justify-content:space-between;
    gap:40px;
    flex-wrap:wrap;
    margin-bottom:40px;
}

.info-box{
    flex:1;
    min-width:280px;
}

.label{
    color:#64748b;
    font-size:13px;
    font-weight:700;
    margin-bottom:12px;
    text-transform:uppercase;
}

.info-box p{
    margin-bottom:7px;
    font-size:15px;
}

.info-box strong{
    font-size:17px;
}

.summary p{
    text-align:right;
}

.summary strong{
    color:#0f172a;
}

.table{
    width:100%;
    border-collapse:collapse;
    margin-bottom:40px;
}

.table thead{
    background:#f8fafc;
}

.table th{
    text-align:left;
    padding:18px;
    font-size:13px;
    color:#64748b;
    text-transform:uppercase;
}

.table td{
    padding:22px 18px;
    border-bottom:1px solid #e2e8f0;
    font-size:15px;
}

.vehicle-name{
    font-size:18px;
    font-weight:700;
    margin-bottom:4px;
}

.vehicle-sub{
    color:#94a3b8;
    font-size:13px;
}

.total-section{
    width:350px;
    margin-left:auto;
}

.total-row{
    display:flex;
    justify-content:space-between;
    margin-bottom:16px;
    font-size:16px;
}

.total-final{
    border-top:2px solid #0f172a;
    padding-top:18px;
    margin-top:12px;
    font-size:18px;
    font-weight:800;
    color:#f97316;
}

.footer{
    margin-top:70px;
    text-align:center;
    color:#94a3b8;
    font-size:14px;
}

.status-badge{
    display:inline-block;
    background:#dcfce7;
    color:#166534;
    padding:5px 14px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
}

.actions { 
    display: flex; 
    gap: 12px; 
    flex-wrap: wrap; 
    margin-top: 30px; 
    justify-content: center;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 13px 24px;
    border-radius: 10px;
    font-family: inherit;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    border: none;
    text-decoration: none;
    transition: all .18s ease;
}
.btn-primary { background: #0f172a; color: white; }
.btn-primary:hover { background: #1e293b; transform: translateY(-1px); }
.btn-outline {
    background: white;
    color: #0f172a;
    border: 2px solid #e2e8f0;
}
.btn-outline:hover { border-color: #94a3b8; transform: translateY(-1px); }

@media(max-width:768px){

    .invoice{
        padding:30px;
    }

    .header{
        flex-direction:column;
    }

    .invoice-info{
        text-align:left;
    }

    .table{
        display:block;
        overflow-x:auto;
    }

    .total-section{
        width:100%;
    }
}

@media print {

    body{
        background:white;
        padding:0;
    }

    .top-actions{
        display:none;
    }

    .invoice{
        box-shadow:none;
        border-radius:0;
    }
}

</style>
</head>
<body>

<div class="container">

    <div class="top-actions">
        <button onclick="window.print()" class="print-btn">
            🖨 Print / Save PDF
        </button>
    </div>

    <div class="invoice">

        <!-- HEADER -->

        <div class="header">

            <div>
                <div class="logo">
                    भटभटे.
                    <small>Nepal's #1 Vehicle Rental Platform</small>
                </div>
            </div>

            <div class="invoice-info">
                <h2>TAX INVOICE</h2>

                <p>
                    <strong>Invoice #:</strong>
                    INV-<?php echo str_pad($booking_id, 5, '0', STR_PAD_LEFT); ?>
                </p>

                <p>
                    <strong>Date:</strong>
                    <?php echo date('Y-m-d H:i'); ?>
                </p>
            </div>

        </div>

        <hr class="divider">

        <!-- CUSTOMER INFO -->

        <div class="info-row">

            <div class="info-box">

                <div class="label">Billed To:</div>

                <p><strong><?php echo htmlspecialchars($booking['user_name']); ?></strong></p>

                <p><?php echo htmlspecialchars($booking['user_email']); ?></p>

            </div>

            <div class="info-box summary">

                <div class="label">Booking Summary:</div>

                <p>
                    <strong>Status:</strong>
                    <span class="status-badge">Confirmed</span>
                </p>

                <p>
                    <strong>Payment Method:</strong>
                    eSewa
                </p>

                <p>
                    <strong>Payment Status:</strong>
                    Completed
                </p>

                <p>
                    <strong>Transaction ID:</strong>
                    <?php echo htmlspecialchars($transaction_code); ?>
                </p>

            </div>

        </div>

        <!-- TABLE -->

        <table class="table">

            <thead>
                <tr>
                    <th>Description</th>
                    <th>Period</th>
                    <th>Location</th>
                    <th style="text-align:right;">Amount</th>
                </tr>
            </thead>

            <tbody>

                <tr>

                    <td>
                        <div class="vehicle-name">
                            <?php echo htmlspecialchars($booking['vehicle_name']); ?>
                        </div>

                        <div class="vehicle-sub">
                            Vehicle Rental Service
                        </div>
                    </td>

                    <td>
                        <?php echo $booking['start_date']; ?>
                        to
                        <?php echo $booking['end_date']; ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($booking['location']); ?>
                    </td>

                    <td style="text-align:right;">
                        NPR <?php echo number_format($booking['total_price'], 2); ?>
                    </td>

                </tr>

            </tbody>

        </table>

        <!-- TOTAL -->

        <div class="total-section">

            <div class="total-row">
                <span>Subtotal:</span>
                <span>NPR <?php echo number_format($booking['total_price'], 2); ?></span>
            </div>

            <div class="total-row">
                <span>Tax (0%):</span>
                <span>NPR 0.00</span>
            </div>

            <div class="total-row total-final">
                <span>Total Paid:</span>
                <span>NPR <?php echo number_format($booking['total_price'], 2); ?></span>
            </div>

        </div>

        <!-- FOOTER -->

        <div class="footer">
            Thank you for choosing Bhatbhatey Rental.
            If you have any questions, please contact
            support@bhatbhatey.com.np
        </div>

    </div>

    <!-- ══  Buttons  ══ -->
    <div class="actions">
        <a href="../my-bookings.php" class="btn btn-primary">
            📋 My Bookings
        </a>
        <a href="../index.php" class="btn btn-outline">
            🏠 Back to Home
        </a>
    </div>

</div>

</body>
</html>