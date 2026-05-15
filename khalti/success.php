<?php

include '../includes/connection.php';
include 'khalti-config.php';

// ── Session ───────────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── PDF Generator ─────────────────────────────────────────────────────────────
function generate_payment_pdf(bool $success, string $booking_id, string $pidx, string $date): void
{
    $title        = $success ? 'Payment Successful' : 'Payment Failed';
    $status_label = $success ? 'PAID'               : 'FAILED';
    $W = 595; $H = 842;

    if ($success) {
        $accent_r = 0.11; $accent_g = 0.63; $accent_b = 0.33;
    } else {
        $accent_r = 0.80; $accent_g = 0.18; $accent_b = 0.18;
    }

    $stream = '';
    $stream .= sprintf("%.2f %.2f %.2f rg\n", $accent_r, $accent_g, $accent_b);
    $stream .= "0 772 595 70 re f\n";
    $stream .= "1 1 1 rg\n";
    $stream .= "40 100 515 640 re f\n";
    $stream .= sprintf("%.2f %.2f %.2f rg\n", $accent_r, $accent_g, $accent_b);
    $stream .= "40 738 515 4 re f\n";

    $add_text = function(string &$s, string $text, float $x, float $y, float $size, bool $bold, float $r, float $g, float $b) {
        $font    = $bold ? 'F2' : 'F1';
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $s .= "BT\n";
        $s .= sprintf("/%s %.1f Tf\n", $font, $size);
        $s .= sprintf("%.2f %.2f %.2f rg\n", $r, $g, $b);
        $s .= sprintf("%.1f %.1f Td\n", $x, $y);
        $s .= "($escaped) Tj\nET\n";
    };

    $add_text($stream, 'Booking System',  50, 800, 20, true,  1, 1, 1);
    $add_text($stream, 'Payment Receipt', 50, 782, 10, false, 1, 1, 1);

    $status_y = 690;
    $add_text($stream, $title, 50, $status_y, 26, true, $accent_r, $accent_g, $accent_b);

    $stream .= sprintf("%.2f %.2f %.2f rg\n", $accent_r, $accent_g, $accent_b);
    $stream .= "50 " . ($status_y - 28) . " 80 22 re f\n";
    $add_text($stream, $status_label, 54, $status_y - 20, 12, true, 1, 1, 1);

    $stream .= "0.85 0.85 0.85 RG\n0.5 w\n";
    $stream .= "50 " . ($status_y - 46) . " m 545 " . ($status_y - 46) . " l S\n";

    $rows = [
        ['Date & Time',          $date],
        ['Transaction ID (pidx)', $pidx],
    ];
    if ($success && $booking_id) {
        $rows[] = ['Booking ID',     $booking_id];
        $rows[] = ['Payment Method', 'Khalti'];
        $rows[] = ['Payment Status', 'Completed'];
    }

    $row_y = $status_y - 72;
    foreach ($rows as $row) {
        $add_text($stream, $row[0], 55,  $row_y, 10, true,  0.4, 0.4, 0.4);
        $add_text($stream, $row[1], 220, $row_y, 10, false, 0.1, 0.1, 0.1);
        $stream .= "0.92 0.92 0.92 RG\n0.3 w\n";
        $stream .= "55 " . ($row_y - 6) . " m 540 " . ($row_y - 6) . " l S\n";
        $row_y -= 28;
    }

    $msg = $success
        ? 'Thank you for your payment. Your booking is confirmed.'
        : 'Your payment could not be processed. Please try again or contact support.';
    $add_text($stream, $msg, 50, 140, 10, false, 0.3, 0.3, 0.3);

    $stream .= "0.95 0.95 0.95 rg\n0 40 595 60 re f\n";
    $add_text($stream, 'Generated on ' . $date, 50, 58, 8, false, 0.5, 0.5, 0.5);
    $add_text($stream, 'Powered by Khalti',     50, 46, 8, false, 0.5, 0.5, 0.5);

    $stream_len = strlen($stream);
    $pdf = ''; $xref = [];
    $add_obj = function(int $n, string $body) use (&$pdf, &$xref): void {
        $xref[$n] = strlen($pdf);
        $pdf .= "$n 0 obj\n$body\nendobj\n";
    };

    $pdf .= "%PDF-1.4\n";
    $add_obj(1, "<< /Type /Catalog /Pages 2 0 R >>");
    $add_obj(2, "<< /Type /Pages /Kids [3 0 R] /Count 1 >>");
    $add_obj(3, "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $W $H] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>");
    $add_obj(4, "<< /Length $stream_len >>\nstream\n$stream\nendstream");
    $add_obj(5, "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>");
    $add_obj(6, "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>");

    $xref_offset = strlen($pdf);
    $obj_count   = 7;
    $pdf .= "xref\n0 $obj_count\n0000000000 65535 f \n";
    for ($i = 1; $i < $obj_count; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $xref[$i]);
    }
    $pdf .= "trailer\n<< /Size $obj_count /Root 1 0 R >>\nstartxref\n$xref_offset\n%%EOF\n";

    $filename = $success ? 'payment-success.pdf' : 'payment-failed.pdf';
    header('Content-Type: application/pdf');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header('Content-Length: ' . strlen($pdf));
    header('Cache-Control: no-cache, no-store');
    echo $pdf;
    exit;
}

// ── Download request — serve PDF from session ─────────────────────────────────
$pidx = $_GET['pidx'] ?? '';

if (!empty($_GET['download'])) {
    $s          = $_SESSION['khalti_result'] ?? [];
    $success    = (bool)($s['success']    ?? false);
    $booking_id = (string)($s['booking_id'] ?? '');
    $date       = $s['date'] ?? date('Y-m-d H:i:s');
    $pidx       = $s['pidx'] ?? $pidx;
    generate_payment_pdf($success, $booking_id, $pidx, $date);
}

// ── Verify with Khalti API ────────────────────────────────────────────────────
$success    = false;
$booking_id = '';
$date       = date('Y-m-d H:i:s');

if (!empty($pidx)) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,            'https://a.khalti.com/api/v2/epayment/lookup/');
    curl_setopt($ch, CURLOPT_POST,           1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,     json_encode(['pidx' => $pidx]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER,     [
        'Authorization: Key ' . KHALTI_SECRET_KEY,
        'Content-Type: application/json',
    ]);

    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($res['status']) && $res['status'] === 'Completed') {

        $success    = true;
        $booking_id = (string)($res['purchase_order_id'] ?? '');

        // ── Update bookings table ─────────────────────────────────────────────
        $stmt = $conn->prepare("UPDATE bookings SET payment_status = 'Completed' WHERE id = ?");
        $stmt->bind_param('i', $booking_id);
        $stmt->execute();
        $stmt->close();
        // ─────────────────────────────────────────────────────────────────────
    }
}

// Save to session for PDF download
$_SESSION['khalti_result'] = [
    'success'    => $success,
    'booking_id' => $booking_id,
    'pidx'       => $pidx,
    'date'       => $date,
];

// ── View ──────────────────────────────────────────────────────────────────────
$accent     = $success ? '#1ca153'                              : '#cc2e2e';
$icon       = $success ? '✔'                                    : '✖';
$heading    = $success ? 'Payment Successful'                   : 'Payment Failed';
$sub        = $success
    ? 'Your booking is confirmed. You can download your receipt below.'
    : 'Your payment could not be processed. Please try again or contact support.';
$dl_url     = '?pidx=' . urlencode($pidx) . '&download=1';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($heading) ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            background: #f0f2f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            padding: 24px;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,.10);
            max-width: 460px; width: 100%;
            overflow: hidden;
        }
        .card-header {
            background: <?= $accent ?>;
            padding: 36px 32px 28px;
            text-align: center; color: #fff;
        }
        .icon-circle {
            width: 64px; height: 64px;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,.55);
            display: flex; align-items: center; justify-content: center;
            font-size: 30px; margin: 0 auto 16px;
        }
        .card-header h1 { font-size: 22px; font-weight: 700; }
        .card-header p  { margin-top: 6px; font-size: 14px; opacity: .88; line-height: 1.5; }
        .card-body { padding: 28px 32px 32px; }
        .info-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px;
        }
        .info-row:last-of-type { border-bottom: none; }
        .info-label { color: #888; font-weight: 500; }
        .info-value { color: #222; font-weight: 600; text-align: right; max-width: 60%; word-break: break-all; }
        .actions { display: flex; flex-direction: column; gap: 12px; margin-top: 28px; }
        .btn {
            display: block; width: 100%; padding: 13px;
            border-radius: 10px; font-size: 15px; font-weight: 600;
            text-align: center; text-decoration: none;
            border: none; cursor: pointer;
            transition: opacity .15s, transform .1s;
        }
        .btn:active { transform: scale(.98); }
        .btn-primary { background: <?= $accent ?>; color: #fff; }
        .btn-primary:hover { opacity: .88; }
        .btn-outline { background: transparent; border: 2px solid #d0d0d0; color: #444; }
        .btn-outline:hover { border-color: #aaa; color: #222; }
    </style>
</head>
<body>
<div class="card">

    <div class="card-header">
        <div class="icon-circle"><?= $icon ?></div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p><?= htmlspecialchars($sub) ?></p>
    </div>

    <div class="card-body">

        <div class="info-row">
            <span class="info-label">Date &amp; Time</span>
            <span class="info-value"><?= htmlspecialchars($date) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Transaction ID</span>
            <span class="info-value"><?= htmlspecialchars($pidx ?: 'N/A') ?></span>
        </div>

        <?php if ($success && $booking_id): ?>
        <div class="info-row">
            <span class="info-label">Booking ID</span>
            <span class="info-value">#<?= htmlspecialchars($booking_id) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Payment Method</span>
            <span class="info-value">Khalti</span>
        </div>
        <div class="info-row">
            <span class="info-label">Payment Status</span>
            <span class="info-value" style="color:<?= $accent ?>">Completed ✔</span>
        </div>
        <?php endif; ?>

        <div class="actions">
            <a class="btn btn-primary" href="<?= htmlspecialchars($dl_url) ?>">
                ⬇ Download Receipt (PDF)
            </a>
            <a class="btn btn-outline" href="../my-bookings.php">
                ← Go to My Bookings
            </a>
        </div>

    </div>
</div>
</body>
</html>