<?php
include('config.php');

$booking_id = $_GET['booking_id'];
$amount = $_GET['amount'];
?>

<form action="https://uat.esewa.com.np/epay/main" method="POST">
    
    <input value="<?php echo $amount; ?>" name="tAmt" type="hidden">
    <input value="<?php echo $amount; ?>" name="amt" type="hidden">
    <input value="0" name="txAmt" type="hidden">
    <input value="0" name="psc" type="hidden">
    <input value="0" name="pdc" type="hidden">

    <input value="EPAYTEST" name="scd" type="hidden">
    
    <!-- Unique Booking ID -->
    <input value="<?php echo $booking_id; ?>" name="pid" type="hidden">

    <!-- Success & Failure -->
    <input value="..//esewa-success.php" name="su" type="hidden">
    <input value="../esewa-failure.php" name="fu" type="hidden">

    <button type="submit">Pay with eSewa</button>
</form>