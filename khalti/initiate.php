<?php

set_time_limit(120);
ignore_user_abort(true);

include '../includes/connection.php';
include 'khalti-config.php';

header('Content-Type: application/json');

$booking_id = (int) ($_POST['booking_id'] ?? 0);
$amount     = (int) ($_POST['amount']     ?? 0);

if (!$booking_id || !$amount) {
    echo json_encode(['error' => 'Invalid booking or amount.']);
    exit;
}

$data = [
    "return_url"          => "http://localhost/vehcile_-management_system/khalti/success.php",
    "website_url"         => "http://localhost/vehcile_-management_system",
    "amount"              => $amount,
    "purchase_order_id"   => (string) $booking_id,
    "purchase_order_name" => "Vehicle Booking #" . $booking_id,
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,            "https://a.khalti.com/api/v2/epayment/initiate/");
curl_setopt($ch, CURLOPT_POST,           1);
curl_setopt($ch, CURLOPT_POSTFIELDS,     json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT,        30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER,     [
    "Authorization: Key " . KHALTI_SECRET_KEY,
    "Content-Type: application/json",
]);

$response = curl_exec($ch);
$curl_err  = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(['error' => 'cURL Error: ' . $curl_err]);
    exit;
}

$res = json_decode($response, true);

if (isset($res['payment_url'])) {
    echo json_encode(['payment_url' => $res['payment_url']]);
} else {
    echo json_encode(['error' => 'Khalti error', 'detail' => $res]);
}