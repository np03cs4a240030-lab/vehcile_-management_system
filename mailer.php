<?php
/**
 * mailer.php
 * PHPMailer wrapper for Bhatbhatey Rental Management System
 * 
 * Usage: sendMail($to, $subject, $body)
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

// ─── SMTP CONFIGURATION ────────────────────────────────────────────────────────
// Update these values with your actual SMTP credentials before going live.
// For Gmail: enable "App Passwords" (2FA required) and use the app password below.
define('MAIL_HOST',       'smtp.gmail.com');
define('MAIL_PORT',       587);                     // 587 = TLS  |  465 = SSL
define('MAIL_USERNAME',   'BhatBhateyRental@gmail.com');  
define('MAIL_PASSWORD',   'mrjtgsizyssiyyez');  
define('MAIL_FROM_EMAIL', 'BhatBhateyRental@gmail.com');
define('MAIL_FROM_NAME',  'Bhatbhatey Rental');
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Send an HTML email.
 *
 * @param string $to      Recipient email address
 * @param string $subject Email subject line
 * @param string $body    HTML email body
 * @return bool           TRUE on success, FALSE on failure
 */
function sendMail(string $to, string $subject, string $body): bool
{
    $mail = new PHPMailer(true); // true = throw exceptions

    try {
        // ── Server settings ──────────────────────────────────
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use ENCRYPTION_SMTPS for port 465
        $mail->Port       = MAIL_PORT;

        // ── Sender & recipient ───────────────────────────────
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($to);

        // ── Content ──────────────────────────────────────────
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $body;
        // Plain-text fallback (strip HTML tags)
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Log the error instead of crashing the page
        error_log('[Mailer Error] ' . $mail->ErrorInfo);
        return false;
    }
}