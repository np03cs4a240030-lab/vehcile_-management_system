<?php
/**
 * Simple Mailer — Bhatbhatey Rental
 * Uses PHP's built-in mail() function.
 * No external library required.
 *
 * To use Gmail SMTP instead, install PHPMailer via Composer and
 * swap the sendMail() body for a PHPMailer instance.
 */

// ── Sender configuration ──────────────────────────────────────────────────────
define('MAIL_FROM_ADDRESS', 'noreply@bhatbhateyrental.com');
define('MAIL_FROM_NAME',    'Bhatbhatey Rental');
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Low-level send wrapper.
 */
function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody): bool
{
    $to      = '"' . addslashes($toName) . '" <' . $toEmail . '>';
    $from    = '"' . MAIL_FROM_NAME . '" <' . MAIL_FROM_ADDRESS . '>';
    $headers = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: '     . $from,
        'Reply-To: ' . $from,
        'X-Mailer: PHP/' . PHP_VERSION,
    ]);

    return mail($to, $subject, $htmlBody, $headers);
}

/**
 * Inline SVG helper — returns a small SVG icon safe for email clients.
 * Color and size can be customised per call.
 */
function mailIcon(string $type, string $color = '#64748b', int $size = 14): string
{
    $icons = [
        'car' => '<path d="M5 17H3a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h1l2-3h8l2 3h1a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2h-2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>',
        'map-pin' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'credit-card' => '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>',
        'banknote' => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/>',
        'check-circle' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'info' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
    ];

    $path = $icons[$type] ?? $icons['info'];

    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" '
         . 'viewBox="0 0 24 24" fill="none" stroke="' . $color . '" '
         . 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" '
         . 'style="vertical-align:middle; margin-right:5px;">'
         . $path
         . '</svg>';
}

/**
 * Send a booking confirmation e-mail to the user.
 */
function sendBookingConfirmation(array $user, array $booking, array $vehicle): bool
{
    $subject = 'Booking Confirmed - ' . $vehicle['name'];

    $html = '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Booking Confirmation</title>
  <style>
    body        { font-family: Arial, sans-serif; background: #f1f5f9; margin: 0; padding: 0; }
    .wrapper    { max-width: 580px; margin: 32px auto; background: #ffffff;
                  border-radius: 10px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
    .header     { background: #ff6b35; padding: 28px 32px; text-align: center; }
    .header h1  { color: #fff; margin: 0; font-size: 22px; letter-spacing: .5px; }
    .header p   { color: rgba(255,255,255,0.85); margin: 6px 0 0; font-size: 14px; }
    .body       { padding: 32px; }
    .greeting   { font-size: 15px; color: #1e293b; margin-bottom: 24px; line-height: 1.6; }
    .card       { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;
                  padding: 20px; margin-bottom: 20px; }
    .card-title { display: flex; align-items: center; margin: 0 0 14px;
                  font-size: 15px; font-weight: 700; color: #0f172a; }
    table       { width: 100%; border-collapse: collapse; font-size: 14px; }
    td          { padding: 9px 0; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
    tr:last-child td { border-bottom: none; }
    td.label    { color: #64748b; width: 45%; }
    td.value    { color: #1e293b; font-weight: 600; }
    .total-row  { background: #fff7ed; border: 1px solid #fed7aa;
                  border-radius: 8px; padding: 14px 16px; margin-top: 16px;
                  display: flex; justify-content: space-between; align-items: center; }
    .total-label { font-size: 14px; color: #92400e; font-weight: 600; }
    .total-value { font-size: 20px; color: #ff6b35; font-weight: 800; }
    .note       { font-size: 13px; color: #64748b; line-height: 1.7;
                  background: #f8fafc; border-left: 3px solid #ff6b35;
                  padding: 12px 16px; border-radius: 0 6px 6px 0; margin-top: 4px; }
    .footer     { background: #f8fafc; border-top: 1px solid #e2e8f0;
                  padding: 20px 32px; text-align: center; font-size: 12px; color: #94a3b8; }
  </style>
</head>
<body>
  <div class="wrapper">

    <!-- Header -->
    <div class="header">
      ' . mailIcon('check-circle', '#ffffff', 28) . '
      <h1>Booking Confirmed!</h1>
      <p>Your vehicle rental is all set</p>
    </div>

    <!-- Body -->
    <div class="body">

      <p class="greeting">
        Hi <strong>' . htmlspecialchars($user['name']) . '</strong>,<br>
        Your vehicle rental has been confirmed. Here is a summary of your booking:
      </p>

      <!-- Vehicle Card -->
      <div class="card">
        <div class="card-title">' . mailIcon('car', '#ff6b35', 16) . ' Vehicle Information</div>
        <table>
          <tr>
            <td class="label">Vehicle</td>
            <td class="value">' . htmlspecialchars($vehicle['name']) . '</td>
          </tr>
          <tr>
            <td class="label">Type</td>
            <td class="value">' . htmlspecialchars($vehicle['type']) . '</td>
          </tr>
          <tr>
            <td class="label">' . mailIcon('map-pin', '#64748b', 13) . ' Pickup Location</td>
            <td class="value">' . htmlspecialchars($booking['pickup_location'] ?? $vehicle['location']) . '</td>
          </tr>
        </table>
      </div>

      <!-- Booking Details Card -->
      <div class="card">
        <div class="card-title">' . mailIcon('calendar', '#ff6b35', 16) . ' Booking Details</div>
        <table>
          <tr>
            <td class="label">' . mailIcon('calendar', '#64748b', 13) . ' Start Date</td>
            <td class="value">' . htmlspecialchars($booking['start_date']) . '</td>
          </tr>
          <tr>
            <td class="label">' . mailIcon('calendar', '#64748b', 13) . ' End Date</td>
            <td class="value">' . htmlspecialchars($booking['end_date']) . '</td>
          </tr>
          <tr>
            <td class="label">' . mailIcon('credit-card', '#64748b', 13) . ' Payment Method</td>
            <td class="value">' . htmlspecialchars($booking['payment_method']) . '</td>
          </tr>
        </table>

        <div class="total-row">
          <span class="total-label">' . mailIcon('banknote', '#92400e', 15) . ' Total Cost</span>
          <span class="total-value">NPR ' . number_format($booking['total_price']) . '</span>
        </div>
      </div>

      <!-- Note -->
      <p class="note">
        ' . mailIcon('info', '#ff6b35', 14) . '
        Please arrive on time at the pickup location with a valid ID.
        If you need to cancel or make changes, visit <em>My Bookings</em> on our website.
      </p>

    </div>

    <!-- Footer -->
    <div class="footer">
      &copy; ' . date('Y') . ' Bhatbhatey Rental &mdash; This is an automated message, please do not reply.
    </div>

  </div>
</body>
</html>';

    return sendMail($user['email'], $user['name'], $subject, $html);
}