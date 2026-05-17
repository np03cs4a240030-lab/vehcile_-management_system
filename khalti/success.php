<?php

require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// Khalti returns these in the URL after payment
$pidx       = $_GET['pidx']               ?? '';
$status     = $_GET['status']             ?? '';
$booking_id = (int)($_GET['purchase_order_id'] ?? 0);
$amount     = $_GET['amount']             ?? '';

if (empty($pidx) || empty($booking_id)) {
    die("Invalid payment response.");
}

$user_id = $_SESSION['user_id'];

// ── Verify with Khalti API ────────────────────────────────────────────────────
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,            'https://a.khalti.com/api/v2/epayment/lookup/');
curl_setopt($ch, CURLOPT_POST,           1);
curl_setopt($ch, CURLOPT_POSTFIELDS,     json_encode(['pidx' => $pidx]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT,        30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER,     [
    'Authorization: Key ' . KHALTI_SECRET_KEY,
    'Content-Type: application/json',
]);

$response = curl_exec($ch);
curl_close($ch);

$res     = json_decode($response, true);
$success = isset($res['status']) && $res['status'] === 'Completed';

// ── Update DB if payment confirmed ────────────────────────────────────────────
if ($success) {
    $stmt = $conn->prepare("UPDATE bookings SET payment_status = 'Completed' WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $booking_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

// ── Fetch booking details for invoice ─────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT b.*, v.name AS vehicle_name, u.name AS user_name, u.email AS user_email, u.phone_number
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN users u    ON b.user_id    = u.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->bind_param('ii', $booking_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    die("Booking not found.");
}

$accent = $success ? '#1ca153' : '#cc2e2e';
$icon   = $success ? '✔'       : '✖';
$label  = $success ? 'Payment Successful' : 'Payment Failed';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($label) ?> — Bhatbhatey</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        /* ── Status banner ── */
        .status-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,.07);
            max-width: 520px;
            width: 100%;
            overflow: hidden;
        }
        .status-header {
            background: <?= $accent ?>;
            padding: 32px;
            text-align: center;
            color: white;
        }
        .status-icon {
            width: 60px; height: 60px;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,.5);
            display: flex; align-items: center; justify-content: center;
            font-size: 26px;
            margin: 0 auto 14px;
        }
        .status-header h2 { font-size: 22px; font-weight: 800; }
        .status-header p  { font-size: 13px; opacity: .88; margin-top: 5px; }
        .status-body { padding: 24px 28px; }
        .info-row {
            display: flex; justify-content: space-between;
            padding: 9px 0; border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        .info-row:last-of-type { border-bottom: none; }
        .info-label { color: #888; font-weight: 500; }
        .info-value { font-weight: 700; color: #0f172a; text-align: right; max-width: 60%; word-break: break-all; }
        .status-actions {
            display: flex; flex-direction: column; gap: 10px;
            padding: 0 28px 24px;
        }
        .btn {
            display: block; width: 100%; padding: 12px;
            border-radius: 10px; font-size: 14px; font-weight: 700;
            text-align: center; text-decoration: none; border: none; cursor: pointer;
            transition: opacity .15s;
        }
        .btn:hover { opacity: .88; }
        .btn-primary { background: <?= $accent ?>; color: white; }
        .btn-outline { background: transparent; border: 2px solid #d0d0d0; color: #444; }
        .btn-outline:hover { border-color: #999; }

        /* ── Invoice ── */
        .invoice-wrap { max-width: 800px; width: 100%; }
        .invoice-box {
            background: white;
            padding: 50px;
            box-shadow: 0 0 20px rgba(0,0,0,.05);
            border-radius: 12px;
        }
        .inv-header {
            display: flex; justify-content: space-between;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 28px; margin-bottom: 28px;
        }
        .logo { font-size: 30px; font-weight: 800; color: #f97316; }
        .inv-title { font-size: 22px; font-weight: 700; color: #64748b; text-align: right; }
        .info-section { display: flex; justify-content: space-between; margin-bottom: 36px; }
        .info-block h3 { margin: 0 0 8px; font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }
        .info-block p  { margin: 0 0 4px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 36px; }
        th { background: #f8fafc; padding: 13px 15px; text-align: left; font-size: 12px; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
        td { padding: 13px 15px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        .total-section { display: flex; justify-content: flex-end; }
        .total-box { width: 280px; }
        .total-row { display: flex; justify-content: space-between; padding: 9px 0; font-size: 14px; }
        .total-row.grand { border-top: 2px solid #0f172a; font-weight: 800; font-size: 18px; padding-top: 14px; margin-top: 4px; color: #f97316; }
        .inv-footer { margin-top: 40px; text-align: center; color: #64748b; font-size: 13px; border-top: 1px solid #f1f5f9; padding-top: 18px; }

        .print-actions { display: flex; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
        .print-btn {
            background: #0f172a; color: white; border: none;
            padding: 11px 22px; border-radius: 8px; cursor: pointer;
            font-family: inherit; font-weight: 700; font-size: 14px;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .back-btn {
            background: white; color: #0f172a; border: 2px solid #e2e8f0;
            padding: 11px 22px; border-radius: 8px; cursor: pointer;
            font-family: inherit; font-weight: 700; font-size: 14px;
            text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
        }

        <?php if ($success): ?>
        /* Only show invoice on successful payment */
        .invoice-section { display: block; }
        <?php else: ?>
        .invoice-section { display: none; }
        <?php endif; ?>

        @media print {
            body { padding: 0; background: white; }
            .status-card, .print-actions { display: none; }
            .invoice-box { box-shadow: none; padding: 20px; border-radius: 0; }
        }
    </style>
</head>
<body>

<!-- ── Status Card ── -->
<div class="status-card">
    <div class="status-header">
        <div class="status-icon"><?= $icon ?></div>
        <h2><?= htmlspecialchars($label) ?></h2>
        <p><?= $success ? 'Your booking is confirmed. See your invoice below.' : 'Payment could not be verified. Please contact support.' ?></p>
    </div>
    <div class="status-body">
        <div class="info-row">
            <span class="info-label">Booking ID</span>
            <span class="info-value">#<?= str_pad($booking_id, 5, '0', STR_PAD_LEFT) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Transaction ID (pidx)</span>
            <span class="info-value"><?= htmlspecialchars($pidx) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Amount</span>
            <span class="info-value">NPR <?= number_format($booking['total_price']) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Payment Method</span>
            <span class="info-value">Khalti</span>
        </div>
        <div class="info-row">
            <span class="info-label">Payment Status</span>
            <span class="info-value" style="color:<?= $accent ?>"><?= $success ? 'Completed ✔' : 'Failed ✖' ?></span>
        </div>
    </div>
    <div class="status-actions">
        <?php if ($success): ?>
        <button class="btn btn-primary" onclick="window.print()">🖨️ Print / Save PDF</button>
        <?php endif; ?>
        <a class="btn btn-outline" href="../my-bookings.php">← Go to My Bookings</a>
    </div>
</div>

<!-- ── Invoice (only on success) ── -->
<?php if ($success): ?>
<div class="invoice-section invoice-wrap">
    <div class="print-actions">
        <button class="print-btn" onclick="window.print()">🖨️ Print / Save PDF</button>
        <a class="back-btn" href="../my-bookings.php">← My Bookings</a>
    </div>

    <div class="invoice-box">
        <div class="inv-header">
            <div>
                <div class="logo">भटभटे.</div>
                <div style="color:#64748b;font-size:13px;margin-top:4px;">Nepal's #1 Vehicle Rental Platform</div>
            </div>
            <div>
                <div class="inv-title">TAX INVOICE</div>
                <div style="font-size:13px;text-align:right;margin-top:6px;">
                    <strong>Invoice #:</strong> INV-<?= str_pad($booking['id'], 5, '0', STR_PAD_LEFT) ?><br>
                    <strong>Date:</strong> <?= date('Y-m-d H:i', strtotime($booking['created_at'])) ?>
                </div>
            </div>
        </div>

        <div class="info-section">
            <div class="info-block">
                <h3>Billed To</h3>
                <p><strong><?= htmlspecialchars($booking['user_name']) ?></strong></p>
                <p><?= htmlspecialchars($booking['user_email']) ?></p>
                <p><?= htmlspecialchars($booking['phone_number']) ?></p>
            </div>
            <div class="info-block" style="text-align:right;">
                <h3>Booking Summary</h3>
                <p><strong>Status:</strong> <?= ucfirst($booking['status']) ?></p>
                <p><strong>Payment:</strong> Khalti</p>
                <p><strong>Payment Status:</strong> <span style="color:#1ca153;font-weight:700;">Completed</span></p>
                <p style="font-size:12px;color:#94a3b8;margin-top:4px;"><?= htmlspecialchars($pidx) ?></p>
            </div>
        </div>

        <table>
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
                        <strong><?= htmlspecialchars($booking['vehicle_name']) ?></strong><br>
                        <span style="font-size:12px;color:#64748b;">Vehicle Rental Service</span>
                    </td>
                    <td><?= $booking['start_date'] ?> → <?= $booking['end_date'] ?></td>
                    <td><?= htmlspecialchars($booking['pickup_location'] ?? 'N/A') ?></td>
                    <td style="text-align:right;">NPR <?= number_format($booking['total_price'], 2) ?></td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-box">
                <div class="total-row"><span>Subtotal:</span><span>NPR <?= number_format($booking['total_price'], 2) ?></span></div>
                <div class="total-row"><span>Tax (0%):</span><span>NPR 0.00</span></div>
                <div class="total-row grand"><span>Total Paid:</span><span>NPR <?= number_format($booking['total_price'], 2) ?></span></div>
            </div>
        </div>

        <div class="inv-footer">
            Thank you for choosing Bhatbhatey Rental. Questions? Contact support@bhatbhatey.com.np
        </div>
    </div>
</div>
<?php endif; ?>

</body>
</html>