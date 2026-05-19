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

$decoded  = base64_decode($_GET['data']);
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
    $stmt = $conn->prepare("
        UPDATE bookings
        SET status = 'completed'
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
  .header { padding:28px 36px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:flex-start; }
  .logo-text { font-size:30px; font-weight:900; color:#f97316; letter-spacing:-0.5px; }
  .logo-sub  { font-size:11px; color:#94a3b8; margin-top:4px; }
  .inv-label h2 { font-size:18px; font-weight:800; color:#0f172a; text-transform:uppercase; letter-spacing:1px; margin:0 0 4px; text-align:right; }
  .inv-label p  { font-size:12px; color:#64748b; margin:2px 0; text-align:right; }
  .billing { display:flex; justify-content:space-between; padding:22px 36px; background:#f8fafc; border-bottom:1px solid #e2e8f0; }
  .billing-block h4 { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#94a3b8; margin:0 0 8px; }
  .billing-block p  { font-size:13px; color:#334155; margin:2px 0; }
  .billing-block p strong { color:#0f172a; font-weight:700; }
  .billing-right { text-align:right; }
  .badge-confirmed { display:inline-block; background:#d1fae5; color:#065f46; font-size:11px; font-weight:700; border-radius:100px; padding:3px 10px; }
  table { width:100%; border-collapse:collapse; }
  thead th { background:#f1f5f9; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.8px; color:#64748b; padding:12px 36px; text-align:left; }
  thead th:last-child { text-align:right; }
  tbody td { padding:20px 36px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#0f172a; vertical-align:top; }
  tbody td:last-child { text-align:right; font-weight:700; }
  .item-name { font-size:15px; font-weight:700; margin-bottom:3px; }
  .item-sub  { font-size:12px; color:#64748b; }
  .totals { padding:18px 36px 24px; }
  .totals-row { display:flex; justify-content:space-between; font-size:13px; color:#64748b; padding:4px 0; }
  hr.divider { border:none; border-top:1px solid #e2e8f0; margin:10px 0; }
  .totals-final { display:flex; justify-content:space-between; font-size:20px; font-weight:800; color:#f97316; padding:6px 0 0; }
  .footer { text-align:center; padding:18px 36px; font-size:12px; color:#94a3b8; border-top:1px solid #f1f5f9; }
</style>
</head>
<body>
<div class="wrapper">
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
  <div class="totals">
    <div class="totals-row"><span>Subtotal</span><span>NPR {$totalPrice}</span></div>
    <div class="totals-row"><span>Tax (0%)</span><span>NPR 0.00</span></div>
    <hr class="divider">
    <div class="totals-final"><span>Total Paid</span><span>NPR {$totalPrice}</span></div>
  </div>
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
| Page Variables
|--------------------------------------------------------------------------
*/

$invoiceNumber = 'INV-' . str_pad($booking_id, 5, '0', STR_PAD_LEFT);
$invoiceDate   = date('Y-m-d H:i', strtotime($booking['created_at'] ?? 'now'));
$startDate     = date('Y-m-d', strtotime($booking['start_date']));
$endDate       = date('Y-m-d', strtotime($booking['end_date']));
$location      = htmlspecialchars($booking['pickup_location'] ?? $booking['vehicle_location'] ?? 'N/A');
$totalPrice    = number_format($booking['total_price'], 2);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= str_pad($booking_id, 5, '0', STR_PAD_LEFT) ?> - Bhatbhatey</title>
    <link href="https://fonts.googleapis.com/css2?family=Courier+Prime&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
        }

        .page-wrapper {
            width: 100%;
            max-width: 860px;
        }

        /* ── TOP ACTION BAR ── */
        .action-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        /* Status badge at top */
        .status-banner {
            display: flex;
            align-items: center;
            gap: 10px;
            background: <?= $success ? '#dcfce7' : '#fee2e2' ?>;
            border: 1px solid <?= $success ? '#86efac' : '#fca5a5' ?>;
            color: <?= $success ? '#15803d' : '#b91c1c' ?>;
            padding: 10px 18px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
        }

        .status-icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: <?= $success ? '#16a34a' : '#dc2626' ?>;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            border: none;
            font-family: inherit;
        }

        .btn-primary {
            background: #0f172a;
            color: white;
        }

        .btn-secondary {
            background: white;
            color: #0f172a;
            border: 2px solid #e2e8f0;
        }

        /* ── INVOICE BOX ── */
        .invoice-box {
            background: white;
            padding: 50px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.06);
            border-radius: 14px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 30px;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 32px;
            font-weight: 800;
            color: #f97316;
        }

        .logo-sub {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 4px;
        }

        .invoice-title {
            font-size: 24px;
            font-weight: 700;
            color: #64748b;
            text-align: right;
        }

        .invoice-meta {
            font-size: 13px;
            text-align: right;
            margin-top: 6px;
            color: #64748b;
            line-height: 1.7;
        }

        .invoice-meta strong {
            color: #0f172a;
        }

        /* ── INFO SECTION ── */
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            gap: 20px;
            flex-wrap: wrap;
        }

        .info-block h3 {
            margin: 0 0 10px;
            font-size: 11px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }

        .info-block p {
            margin: 0 0 4px;
            font-size: 14px;
            color: #334155;
        }

        .info-block p strong {
            color: #0f172a;
        }

        .info-block.right {
            text-align: right;
        }

        .badge-confirmed {
            display: inline-block;
            background: #d1fae5;
            color: #065f46;
            font-size: 11px;
            font-weight: 700;
            border-radius: 100px;
            padding: 3px 10px;
        }

        /* ── TABLE ── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        th {
            background: #f8fafc;
            padding: 14px 16px;
            text-align: left;
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 1px solid #e2e8f0;
        }

        th:last-child { text-align: right; }

        td {
            padding: 18px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
            color: #0f172a;
            vertical-align: top;
        }

        td:last-child {
            text-align: right;
            font-weight: 700;
        }

        .item-name {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .item-sub {
            font-size: 12px;
            color: #94a3b8;
        }

        /* ── TOTALS ── */
        .total-section {
            display: flex;
            justify-content: flex-end;
        }

        .total-box { width: 300px; }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 9px 0;
            font-size: 14px;
            color: #64748b;
        }

        .total-row.grand {
            border-top: 2px solid #0f172a;
            font-weight: 800;
            font-size: 20px;
            padding-top: 14px;
            margin-top: 4px;
            color: #f97316;
        }

        /* ── FOOTER ── */
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #94a3b8;
            font-size: 13px;
            border-top: 1px solid #f1f5f9;
            padding-top: 24px;
        }

        /* ── PRINT ── */
        @media print {
            body { padding: 0; background: white; }
            .invoice-box { box-shadow: none; border-radius: 0; padding: 30px; }
            .action-bar { display: none; }
        }
    </style>
</head>
<body>

<div class="page-wrapper">

    <!-- Action Bar -->
    <div class="action-bar">

        <div class="status-banner">
            <div class="status-icon"><?= $success ? '✔' : '✖' ?></div>
            <?= $success
                ? 'Payment Successful — A receipt has been sent to your email.'
                : 'Payment verification failed.'
            ?>
        </div>

        <div class="action-buttons">
            <?php if ($success): ?>
                <button class="btn btn-primary" onclick="window.print()">🖨️ Print / Save PDF</button>
            <?php endif; ?>
            <a href="../my-bookings.php" class="btn btn-secondary">← My Bookings</a>
        </div>

    </div>

    <!-- Invoice -->
    <div class="invoice-box">

        <!-- Header -->
        <div class="header">
            <div>
                <div class="logo">भटभटे.</div>
                <div class="logo-sub">Nepal's #1 Vehicle Rental Platform</div>
            </div>
            <div>
                <div class="invoice-title">TAX INVOICE</div>
                <div class="invoice-meta">
                    <strong>Invoice #:</strong> <?= $invoiceNumber ?><br>
                    <strong>Date:</strong> <?= $invoiceDate ?>
                </div>
            </div>
        </div>

        <!-- Billing Info -->
        <div class="info-section">
            <div class="info-block">
                <h3>Billed To</h3>
                <p><strong><?= htmlspecialchars($booking['user_name']) ?></strong></p>
                <p><?= htmlspecialchars($booking['user_email']) ?></p>
                <p><?= htmlspecialchars($booking['phone_number'] ?? '') ?></p>
            </div>
            <div class="info-block right">
                <h3>Booking Summary</h3>
                <p><strong>Booking ID:</strong> #<?= $booking_id ?></p>
                <p><strong>Status:</strong> <span class="badge-confirmed">Confirmed</span></p>
                <p><strong>Payment Method:</strong> Online (eSewa)</p>
                <p><strong>Payment Status:</strong> Completed</p>
                <?php if (!empty($transaction_code)): ?>
                    <p><strong>Transaction ID:</strong> <?= strtoupper(htmlspecialchars($transaction_code)) ?></p>
                <?php endif; ?>
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
                        <div class="item-name"><?= htmlspecialchars($booking['vehicle_name']) ?></div>
                        <div class="item-sub">Vehicle Rental Service</div>
                    </td>
                    <td><?= $startDate ?> to <?= $endDate ?></td>
                    <td><?= $location ?></td>
                    <td>NPR <?= $totalPrice ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="total-section">
            <div class="total-box">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>NPR <?= $totalPrice ?></span>
                </div>
                <div class="total-row">
                    <span>Tax (0%):</span>
                    <span>NPR 0.00</span>
                </div>
                <div class="total-row grand">
                    <span>Total Paid:</span>
                    <span>NPR <?= $totalPrice ?></span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            Thank you for choosing Bhatbhatey Rental. If you have any questions, please contact support@bhatbhatey.com.np
        </div>

    </div><!-- /invoice-box -->

</div><!-- /page-wrapper -->

</body>
</html>