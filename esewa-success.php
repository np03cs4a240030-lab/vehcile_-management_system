<?php
include('config.php');

$amt = $_GET['amt'];
$refId = $_GET['refId'];
$pid = $_GET['oid'];

// Verify with eSewa
$url = "https://uat.esewa.com.np/epay/transrec";

$data = [
    'amt' => $amt,
    'rid' => $refId,
    'pid' => $pid,
    'scd' => 'EPAYTEST'
];

$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded",
        'method'  => 'POST',
        'content' => http_build_query($data)
    ]
];

$context  = stream_context_create($options);
$response = file_get_contents($url, false, $context);

if (strpos($response, "Success") !== false) {
    
    // Update booking as paid
    mysqli_query($conn, "UPDATE bookings SET payment_status='Paid' WHERE id='$pid'");
    
    echo "Payment Successful ✅";

} else {
    echo "Payment Verification Failed ❌";
}
?>