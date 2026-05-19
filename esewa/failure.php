<?php

include '../includes/connection.php';
include '../config.php';
include 'esewa-config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// ─────────────────────────────────────────────
// 1.  Grab query params eSewa sends on failure
//     eSewa may send:  transaction_uuid, status
//     (No signature on failure — just use what we have)
// ─────────────────────────────────────────────

$transaction_uuid = $_GET['transaction_uuid'] ?? '';
$status           = $_GET['status']           ?? 'FAILED';   // FAILED | CANCELED | PENDING

// ─────────────────────────────────────────────
// 2.  Parse booking_id from transaction_uuid
//     Format we set:  {booking_id}_{timestamp}
// ─────────────────────────────────────────────

$booking_id = (int)($_GET['booking_id'] ?? 0);

if ($transaction_uuid && !$booking_id) {
    $parts      = explode('_', $transaction_uuid);
    $booking_id = (int)($parts[0] ?? 0);
}

// ─────────────────────────────────────────────
// 3.  Fetch booking from DB (if we have an ID)
// ─────────────────────────────────────────────

$booking = null;

if ($booking_id) {
    $stmt = $conn->prepare("
        SELECT b.*, u.name AS user_name, u.email AS user_email,
               v.name AS vehicle_name
        FROM   bookings b
        JOIN   users    u ON u.id = b.user_id
        JOIN   vehicles v ON v.id = b.vehicle_id
        WHERE  b.id = ?
        LIMIT  1
    ");
    $stmt->bind_param('i', $booking_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ─────────────────────────────────────────────
// 4.  Mark booking as failed (only if pending)
//     Never overwrite a successfully paid booking
// ─────────────────────────────────────────────

if ($booking && $booking['payment_status'] === 'pending') {
    $upd = $conn->prepare("
        UPDATE bookings
        SET    payment_status = 'failed',
               updated_at     = NOW()
        WHERE  id = ?
    ");
    $upd->bind_param('i', $booking_id);
    $upd->execute();
    $upd->close();

    $booking['payment_status'] = 'failed';
}

// ─────────────────────────────────────────────
// 5.  Human-readable reason
// ─────────────────────────────────────────────

$status_upper = strtoupper($status);

$reason_map = [
    'CANCELED' => [
        'title'   => 'Payment Cancelled',
        'message' => 'You cancelled the payment on eSewa. No amount has been charged.',
        'icon'    => '✕',
    ],
    'PENDING'  => [
        'title'   => 'Payment Pending',
        'message' => 'Your payment is still being processed by eSewa. Please check back shortly or contact support.',
        'icon'    => '⏳',
    ],
    'FAILED'   => [
        'title'   => 'Payment Failed',
        'message' => 'Your payment could not be completed. This may be due to insufficient balance, a network issue, or a timeout.',
        'icon'    => '✕',
    ],
];

$reason = $reason_map[$status_upper] ?? $reason_map['FAILED'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($reason['title']); ?> - Bhatbhatey</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
        }

        .brand {
            font-size: 26px;
            font-weight: 800;
            color: #f97316;
            margin-bottom: 30px;
            letter-spacing: -0.5px;
        }

        .card {
            background: white;
            width: 100%;
            max-width: 780px;
            border-radius: 20px;
            box-shadow: 0 4px 30px rgba(0,0,0,0.07);
            overflow: hidden;
        }

        /* ── Hero ── */
        .hero {
            padding: 48px 40px 36px;
            text-align: center;
            background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%);
        }
        .hero.cancelled {
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
        }
        .hero.pending {
            background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%);
        }

        .icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 34px;
        }
        .icon-circle.failed    { background: #dc2626; color: white; }
        .icon-circle.cancelled { background: #f97316; color: white; }
        .icon-circle.pending   { background: #ca8a04; color: white; }

        .hero h1 { font-size: 26px; font-weight: 800; margin-bottom: 8px; }
        .hero.failed-state    h1 { color: #b91c1c; }
        .hero.cancelled h1 { color: #9a3412; }
        .hero.pending   h1 { color: #854d0e; }

        .hero p { font-size: 15px; color: #64748b; max-width: 420px; margin: 0 auto; line-height: 1.6; }

        /* ── Body ── */
        .body { padding: 36px 40px 40px; }

        .section-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 16px;
        }

        /* ── Info grid ── */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 28px;
        }
        .info-cell {
            padding: 18px 22px;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-cell:nth-child(even)      { border-right: none; }
        .info-cell:nth-last-child(-n+2) { border-bottom: none; }

        .info-cell .label { font-size: 12px; color: #94a3b8; font-weight: 600; margin-bottom: 5px; }
        .info-cell .value { font-size: 15px; font-weight: 700; color: #0f172a; }
        .info-cell .value.amount { color: #f97316; font-size: 18px; }

        /* ── Badges ── */
        .badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }
        .badge.failed    { background: #ffe4e6; color: #b91c1c; }
        .badge.cancelled { background: #ffedd5; color: #9a3412; }
        .badge.pending   { background: #fef9c3; color: #854d0e; }

        /* ── Warning / info box ── */
        .info-box {
            border-radius: 12px;
            padding: 16px 20px;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 28px;
        }
        .info-box.danger {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #9f1239;
        }
        .info-box.warning {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
        }
        .info-box.info {
            background: #fefce8;
            border: 1px solid #fde68a;
            color: #854d0e;
        }
        .info-box strong { display: block; margin-bottom: 4px; font-size: 14px; }

        /* ── No booking fallback ── */
        .no-booking {
            text-align: center;
            padding: 20px 0 10px;
            color: #64748b;
            font-size: 15px;
            margin-bottom: 28px;
        }

        /* ── Divider ── */
        .divider { border: none; border-top: 1px solid #f1f5f9; margin: 28px 0; }

        /* ── Steps (what to do next) ── */
        .steps { display: flex; flex-direction: column; gap: 12px; margin-bottom: 28px; }
        .step {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            background: #f8fafc;
            border-radius: 12px;
            padding: 14px 18px;
        }
        .step-num {
            width: 26px;
            height: 26px;
            min-width: 26px;
            border-radius: 50%;
            background: #0f172a;
            color: white;
            font-size: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .step-text { font-size: 14px; color: #475569; line-height: 1.5; }
        .step-text strong { color: #0f172a; }

        /* ── Action buttons ── */
        .actions { display: flex; gap: 12px; flex-wrap: wrap; }

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
        .btn-orange  { background: #f97316; color: white; }
        .btn-orange:hover  { background: #ea6c0a; transform: translateY(-1px); }
        .btn-outline {
            background: white;
            color: #0f172a;
            border: 2px solid #e2e8f0;
        }
        .btn-outline:hover { border-color: #94a3b8; transform: translateY(-1px); }

        /* ── Support note ── */
        .support-note {
            text-align: center;
            margin-top: 28px;
            font-size: 13px;
            color: #94a3b8;
        }
        .support-note a { color: #f97316; text-decoration: none; font-weight: 600; }
        .support-note a:hover { text-decoration: underline; }

        @media (max-width: 540px) {
            .hero, .body { padding: 28px 24px; }
            .info-grid { grid-template-columns: 1fr; }
            .info-cell { border-right: none !important; }
            .info-cell:last-child { border-bottom: none; }
            .actions { flex-direction: column; }
            .btn { justify-content: center; }
        }
    </style>
</head>
<body>

<div class="brand">भटभटे.</div>

<div class="card">

    <?php
    // Determine hero class
    $heroClass  = 'failed-state';
    $iconClass  = 'failed';
    $badgeClass = 'failed';
    $boxClass   = 'danger';

    if ($status_upper === 'CANCELED') {
        $heroClass  = 'cancelled';
        $iconClass  = 'cancelled';
        $badgeClass = 'cancelled';
        $boxClass   = 'warning';
    } elseif ($status_upper === 'PENDING') {
        $heroClass  = 'pending';
        $iconClass  = 'pending';
        $badgeClass = 'pending';
        $boxClass   = 'info';
    }
    ?>

    <!-- ══  Hero  ══ -->
    <div class="hero <?php echo $heroClass; ?>">
        <div class="icon-circle <?php echo $iconClass; ?>">
            <?php echo $reason['icon']; ?>
        </div>
        <h1><?php echo htmlspecialchars($reason['title']); ?></h1>
        <p><?php echo htmlspecialchars($reason['message']); ?></p>
    </div>

    <div class="body">

        <?php if ($booking): ?>

        <!-- ══  Alert box  ══ -->
        <div class="info-box <?php echo $boxClass; ?>">
            <?php if ($status_upper === 'CANCELED'): ?>
                <strong>⚠️ Payment Cancelled</strong>
                Your booking is still saved. You can retry the payment anytime from your bookings page. No amount has been deducted.
            <?php elseif ($status_upper === 'PENDING'): ?>
                <strong>⏳ Still Processing</strong>
                eSewa hasn't confirmed the payment yet. Wait a few minutes, then check your bookings. If money was deducted but the booking remains unpaid, contact support — it will be resolved within 3–5 business days.
            <?php else: ?>
                <strong>✕ Payment Not Completed</strong>
                No amount has been permanently deducted. If you see a deduction in your eSewa wallet, it will be automatically refunded within 3–5 business days.
            <?php endif; ?>
        </div>

        <!-- ══  Booking details  ══ -->
        <div class="section-label">Booking Reference</div>

        <div class="info-grid">
            <div class="info-cell">
                <div class="label">Booking ID</div>
                <div class="value">#<?php echo str_pad($booking_id, 5, '0', STR_PAD_LEFT); ?></div>
            </div>
            <div class="info-cell">
                <div class="label">Vehicle</div>
                <div class="value"><?php echo htmlspecialchars($booking['vehicle_name']); ?></div>
            </div>
            <div class="info-cell">
                <div class="label">Amount</div>
                <div class="value amount">NPR <?php echo number_format($booking['total_price'], 2); ?></div>
            </div>
            <div class="info-cell">
                <div class="label">Payment Status</div>
                <div class="value">
                    <span class="badge <?php echo $badgeClass; ?>">
                        <?php echo ucfirst(strtolower($status_upper)); ?>
                    </span>
                </div>
            </div>
            <div class="info-cell">
                <div class="label">Pickup Date</div>
                <div class="value"><?php echo htmlspecialchars($booking['start_date']); ?></div>
            </div>
            <div class="info-cell">
                <div class="label">Return Date</div>
                <div class="value"><?php echo htmlspecialchars($booking['end_date']); ?></div>
            </div>
        </div>

        <?php else: ?>
        <!-- No booking found -->
        <div class="no-booking">
            ℹ️ We couldn't find an associated booking for this transaction.<br>
            Please check your bookings page or contact support.
        </div>
        <?php endif; ?>

        <!-- ══  What to do next  ══ -->
        <hr class="divider">
        <div class="section-label">What to do next</div>

        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-text">
                    <strong>Check your eSewa balance</strong> — make sure you have sufficient funds before retrying.
                </div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-text">
                    <strong>Retry the payment</strong> — your booking is saved; just hit "Retry Payment" below.
                </div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-text">
                    <strong>Still having trouble?</strong> Contact us at
                    <strong>support@bhatbhatey.com.np</strong> with your Booking ID.
                </div>
            </div>
        </div>

        <!-- ══  Buttons  ══ -->
        <div class="actions">
            <?php if ($booking): ?>
            <a href="initiate.php?booking_id=<?php echo $booking_id; ?>&amount=<?php echo $booking['total_price']; ?>" class="btn btn-orange">
                🔄 Retry Payment
            </a>
            <?php endif; ?>
            <a href="../my-bookings.php" class="btn btn-primary">
                📋 My Bookings
            </a>
            <a href="../index.php" class="btn btn-outline">
                🏠 Back to Home
            </a>
        </div>

        <div class="support-note">
            Need help? <a href="mailto:support@bhatbhatey.com.np">support@bhatbhatey.com.np</a>
        </div>

    </div><!-- .body -->

</div><!-- .card -->

</body>
</html>