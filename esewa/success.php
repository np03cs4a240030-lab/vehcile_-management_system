<?php

require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Decode eSewa Response
|--------------------------------------------------------------------------
*/

if (!isset($_GET['data'])) {
    die("Invalid payment response.");
}

$decoded = base64_decode($_GET['data']);

$response = json_decode($decoded, true);

if (!$response) {
    die("Unable to decode payment response.");
}

/*
|--------------------------------------------------------------------------
| Get Payment Details
|--------------------------------------------------------------------------
*/

$status           = $response['status']           ?? '';
$transaction_code = $response['transaction_code'] ?? '';
$total_amount     = $response['total_amount']     ?? 0;
$transaction_uuid = $response['transaction_uuid'] ?? '';

/*
|--------------------------------------------------------------------------
| transaction_uuid = booking id
|--------------------------------------------------------------------------
*/

$booking_id = (int) $transaction_uuid;

/*
|--------------------------------------------------------------------------
| Check Payment Success
|--------------------------------------------------------------------------
*/

$success = ($status === 'COMPLETE');

/*
|--------------------------------------------------------------------------
| Update Database (only if Pending — prevents replay attacks)
|--------------------------------------------------------------------------
*/

if ($success) {
    
    // NEW — only update status
$stmt = $conn->prepare("
    UPDATE bookings
    SET 
        status = 'completed'
    WHERE id = ? AND user_id = ?
");

    

    $stmt->bind_param("ii", $booking_id, $user_id);

    $stmt->execute();

    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| Fetch Booking Details
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT 
        b.*,
        v.name     AS vehicle_name,
        v.location AS vehicle_location,
        u.name     AS user_name,
        u.email    AS user_email,
        u.phone_number
    FROM bookings b
    LEFT JOIN vehicles v ON b.vehicle_id = v.id
    
    JOIN users    u ON b.user_id    = u.id
    WHERE b.id = ? AND b.user_id = ?
");

$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();

$booking = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$booking) {
    die("Booking not found.");
}

/*
|--------------------------------------------------------------------------
| Send Receipt Email (only on successful payment)
|--------------------------------------------------------------------------
*/

if ($success) {

    require_once '../mailer.php';

    $invoiceNumber = 'INV-' . str_pad($booking_id, 5, '0', STR_PAD_LEFT);
    $invoiceDate   = date('Y-m-d H:i');
    $startDate     = date('Y-m-d', strtotime($booking['start_date']));
    $endDate       = date('Y-m-d', strtotime($booking['end_date']));
    $days          = $booking['total_days']
                        ?? (new DateTime($booking['start_date']))->diff(new DateTime($booking['end_date']))->days;
    $location      = htmlspecialchars($booking['pickup_location'] ?? $booking['vehicle_location'] ?? 'N/A');
    $vehicleName   = htmlspecialchars($booking['vehicle_name']);
    $userName      = htmlspecialchars($booking['user_name']);
    $userEmail     = $booking['user_email'];
    $totalPrice    = number_format($booking['total_price'], 2);
    $txnId         = strtoupper(htmlspecialchars($transaction_code));
    $phone         = htmlspecialchars($booking['phone_number'] ?? '');

    $receiptBody = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tax Invoice – {$invoiceNumber}</title>
<style>
  body { margin:0; padding:30px 16px; background:#f1f5f9; font-family:Arial,sans-serif; color:#0f172a; }
  .wrapper { max-width:620px; margin:auto; background:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.08); }

  /* ── HEADER ── */
  .header { padding:28px 36px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:flex-start; }
  .logo-text { font-size:30px; font-weight:900; color:#f97316; letter-spacing:-0.5px; }
  .logo-sub  { font-size:11px; color:#94a3b8; margin-top:4px; }
  .inv-label h2 { font-size:18px; font-weight:800; color:#0f172a; text-transform:uppercase; letter-spacing:1px; margin:0 0 4px; text-align:right; }
  .inv-label p  { font-size:12px; color:#64748b; margin:2px 0; text-align:right; }

  /* ── BILLING ROW ── */
  .billing { display:flex; justify-content:space-between; padding:22px 36px; background:#f8fafc; border-bottom:1px solid #e2e8f0; }
  .billing-block h4 { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#94a3b8; margin:0 0 8px; }
  .billing-block p  { font-size:13px; color:#334155; margin:2px 0; }
  .billing-block p strong { color:#0f172a; font-weight:700; }
  .billing-right { text-align:right; }
  .badge-confirmed { display:inline-block; background:#d1fae5; color:#065f46; font-size:11px; font-weight:700; border-radius:100px; padding:3px 10px; }

  /* ── TABLE ── */
  table { width:100%; border-collapse:collapse; }
  thead th { background:#f1f5f9; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.8px; color:#64748b; padding:12px 36px; text-align:left; }
  thead th:last-child { text-align:right; }
  tbody td { padding:20px 36px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#0f172a; vertical-align:top; }
  tbody td:last-child { text-align:right; font-weight:700; }
  .item-name { font-size:15px; font-weight:700; margin-bottom:3px; }
  .item-sub  { font-size:12px; color:#64748b; }

  /* ── TOTALS ── */
  .totals { padding:18px 36px 24px; }
  .totals-row { display:flex; justify-content:space-between; font-size:13px; color:#64748b; padding:4px 0; }
  hr.divider { border:none; border-top:1px solid #e2e8f0; margin:10px 0; }
  .totals-final { display:flex; justify-content:space-between; font-size:20px; font-weight:800; color:#f97316; padding:6px 0 0; }

  /* ── FOOTER ── */
  .footer { text-align:center; padding:18px 36px; font-size:12px; color:#94a3b8; border-top:1px solid #f1f5f9; }
</style>
</head>
<body>
<div class="wrapper">

  <!-- Header -->
  <div class="header">
    <div>
      <div class="logo-text">भटभटे.</div>
      <div class="logo-sub">Nepal's #1 Vehicle Rental Platform</div>
    </div>
    <div class="inv-label">
      <h2>Tax Invoice</h2>
      <p>Invoice #: {$invoiceNumber}</p>
      <p>Date: {$invoiceDate}</p>
    </div>
  </div>

  <!-- Billing -->
  <div class="billing">
    <div class="billing-block">
      <h4>Billed To</h4>
      <p><strong>{$userName}</strong></p>
      <p>{$userEmail}</p>
      <p>{$phone}</p>
    </div>
    <div class="billing-block billing-right">
      <h4>Booking Summary</h4>
      <p>Status: <span class="badge-confirmed">Confirmed</span></p>
      <p>Payment Method: Online (eSewa)</p>
      <p>Payment Status: Completed</p>
      <p>Transaction ID: {$txnId}</p>
    </div>
  </div>

  <!-- Line Items -->
  <table>
    <thead>
      <tr>
        <th>Description</th>
        <th>Period</th>
        <th>Location</th>
        <th>Amount</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>
          <div class="item-name">{$vehicleName}</div>
          <div class="item-sub">Vehicle Rental Service</div>
        </td>
        <td>{$startDate} to {$endDate}</td>
        <td>{$location}</td>
        <td>NPR {$totalPrice}</td>
      </tr>
    </tbody>
  </table>

  <!-- Totals -->
  <div class="totals">
    <div class="totals-row"><span>Subtotal</span><span>NPR {$totalPrice}</span></div>
    <div class="totals-row"><span>Tax (0%)</span><span>NPR 0.00</span></div>
    <hr class="divider">
    <div class="totals-final"><span>Total Paid</span><span>NPR {$totalPrice}</span></div>
  </div>

  <!-- Footer -->
  <div class="footer">
    Thank you for choosing Bhatbhatey Rental. If you have any questions, please contact support@bhatbhatey.com.np
  </div>

</div>
</body>
</html>
HTML;

    sendMail($userEmail, "Payment Receipt – {$invoiceNumber} | Bhatbhatey Rental", $receiptBody);
}

/*
|--------------------------------------------------------------------------
| Page Display Variables
|--------------------------------------------------------------------------
*/

$accent = $success ? '#16a34a' : '#dc2626';
$icon   = $success ? '✔'       : '✖';
$label  = $success ? 'Payment Successful' : 'Payment Failed';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $label ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f1f5f9;
            padding: 40px 20px;
            color: #0f172a;
        }

        .container {
            max-width: 900px;
            margin: auto;
        }

        .card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .card-header {
            background: <?= $accent ?>;
            color: white;
            padding: 35px;
            text-align: center;
        }

        .icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: auto;
            font-size: 28px;
            margin-bottom: 15px;
        }

        .card-body {
            padding: 30px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .row:last-child {
            border: none;
        }

        .label {
            color: #64748b;
        }

        .value {
            font-weight: 700;
        }

        .btns {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            display: inline-block;
        }

        .primary {
            background: <?= $accent ?>;
            color: white;
        }

        .secondary {
            border: 2px solid #cbd5e1;
            color: #0f172a;
            background: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        th {
            background: #f8fafc;
            text-align: left;
            padding: 14px;
            font-size: 13px;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #e2e8f0;
        }

        .total {
            text-align: right;
            margin-top: 25px;
            font-size: 20px;
            font-weight: 800;
            color: #16a34a;
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="card">

            <div class="card-header">

                <div class="icon"><?= $icon ?></div>

                <h1><?= $label ?></h1>

                <p style="margin-top:10px;">
                    <?= $success
                        ? 'Your payment has been completed successfully. A receipt has been sent to your email.'
                        : 'Payment verification failed.'
                    ?>
                </p>

            </div>

            <div class="card-body">

                <div class="row">
                    <div class="label">Booking ID</div>
                    <div class="value">#<?= $booking_id ?></div>
                </div>

                <div class="row">
                    <div class="label">Transaction Code</div>
                    <div class="value"><?= htmlspecialchars($transaction_code) ?></div>
                </div>

                <div class="row">
                    <div class="label">Payment Method</div>
                    <div class="value">eSewa</div>
                </div>

                <div class="row">
                    <div class="label">Amount Paid</div>
                    <div class="value">NPR <?= number_format($booking['total_price'], 2) ?></div>
                </div>

                <div class="row">
                    <div class="label">Status</div>
                    <div class="value" style="color:<?= $accent ?>">
                        <?= $success ? 'Completed ✔' : 'Failed ✖' ?>
                    </div>
                </div>

                <div class="btns">

                    <?php if ($success): ?>
                        <a href="#" onclick="window.print()" class="btn primary">
                            🖨 Print Invoice
                        </a>
                    <?php endif; ?>

                    <a href="../my-bookings.php" class="btn secondary">
                        ← My Bookings
                    </a>

                </div>

            </div>

        </div>

    </div>

</body>

</html>