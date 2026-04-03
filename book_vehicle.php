<?php
session_start();
include("db.php");

/* TEMP TEST USER (REMOVE LATER) */
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // 👈 for testing only
}

$vehicle_id = $_GET['id'] ?? 0;

/* GET VEHICLE */
$v = $conn->query("SELECT * FROM vehicles WHERE id=$vehicle_id");

if ($v->num_rows == 0) {
    die("Vehicle not found!");
}

$v = $v->fetch_assoc();

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $payment_method = $_POST['payment_method'];

    $days = (strtotime($end) - strtotime($start)) / (60*60*24);

    if ($days <= 0) {
        $message = "Invalid date selection!";
    } else {

        $total = $days * $v['price'];
        $user_id = $_SESSION['user_id'];

        $stmt = $conn->prepare("
        INSERT INTO bookings 
        (user_id, vehicle_id, start_date, end_date, total_days, total_cost, payment_method)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param("iissids",
            $user_id,
            $vehicle_id,
            $start,
            $end,
            $days,
            $total,
            $payment_method
        );

        if ($stmt->execute()) {
            $message = " Booking Successful!";
        } else {
            $message = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Book Vehicle</title>

<style>
body {
    margin: 0;
    font-family: 'Segoe UI';
    background: #f1f5f9;
}

.container {
    max-width: 500px;
    margin: 50px auto;
    background: white;
    padding: 30px;
    border-radius: 12px;
}

h2 { margin-bottom: 10px; }

input, select {
    width: 100%;
    padding: 12px;
    margin: 10px 0;
    border-radius: 8px;
    border: 1px solid #ccc;
}

button {
    width: 100%;
    padding: 12px;
    background: #f97316;
    border: none;
    color: white;
    border-radius: 8px;
    cursor: pointer;
}

button:hover {
    background: #ea580c;
}

.msg {
    margin-top: 15px;
    font-weight: bold;
}
</style>
</head>

<body>

<div class="container">

    <h2><?php echo $v['name']; ?></h2>
    <p>Price per day: <b>NPR <?php echo $v['price']; ?></b></p>

    <form method="POST">

        <label>Start Date</label>
        <input type="date" name="start_date" required>

        <label>End Date</label>
        <input type="date" name="end_date" required>

        <label>Payment Method</label>
        <select name="payment_method" required>
            <option value="Cash">Cash</option>
            <option value="Khalti">Khalti</option>
            <option value="Card">Card</option>
        </select>

        <button type="submit">Book Now</button>

    </form>

    <div class="msg"><?php echo $message; ?></div>

</div>

</body>
</html>