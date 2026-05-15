<?php

include '../includes/connection.php';
include 'khalti-config.php';

$booking_id = $_GET['booking_id'];
$amount = $_GET['amount'] * 100;

$url = "https://a.khalti.com/api/v2/epayment/initiate/";

$data = [
    "return_url" => "http://localhost/vehcile_-management_system/khalti/success.php",
    "website_url" => "http://localhost/VEHICLE-MANAGEMENT_SYSTEM",
    "amount" => $amount,
    "purchase_order_id" => $booking_id,
    "purchase_order_name" => "Vehicle Booking",
];

$headers = [
    "Authorization: Key " . KHALTI_SECRET_KEY,
    "Content-Type: application/json"
];

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);

curl_close($ch);

$res = json_decode($response, true);

if(isset($res['payment_url'])) {

    header("Location: ".$res['payment_url']);

} else {

    echo "Payment Error";
    print_r($res);

}

?>