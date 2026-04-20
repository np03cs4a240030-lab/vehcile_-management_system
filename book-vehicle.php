<?php
require_once 'config.php';
require_once __DIR__ . '/includes/mailer.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('vehicles.php');
}

if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();
$vehicleId   = (int)($_POST['vehicle_id'] ?? 0);

if (!$vehicleId) {
    redirect('vehicles.php');
}

// Fetch vehicle
$stmt = $conn->prepare("SELECT * FROM vehicles WHERE id=?");
$stmt->bind_param("i", $vehicleId);
$stmt->execute();
$vehicle = $stmt->get_result()->fetch_assoc();

if (!$vehicle) {
    redirect('vehicles.php');
}

// Collect POST data
$startDate     = $_POST['start_date']     ?? '';
$endDate       = $_POST['end_date']       ?? '';
$paymentMethod = $_POST['payment_method'] ?? 'Cash on Pickup';

// --- Validation ---
if (!$startDate || !$endDate) {
    redirect('vehicle-details.php?id=' . $vehicleId . '&error=Please+select+dates');
}

$start = new DateTime($startDate);
$end   = new DateTime($endDate);
$days  = $start->diff($end)->days;

if ($days <= 0) {
    redirect('vehicle-details.php?id=' . $vehicleId . '&error=Invalid+date+range');
}

// Check overlapping bookings
$check = $conn->prepare("
    SELECT id FROM bookings
    WHERE vehicle_id = ?
    AND status = 'confirmed'
    AND (
        (start_date <= ? AND end_date >= ?) OR
        (start_date <= ? AND end_date >= ?)
    )
");
$check->bind_param("issss", $vehicleId, $startDate, $startDate, $endDate, $endDate);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    redirect('vehicle-details.php?id=' . $vehicleId . '&error=Vehicle+already+booked+for+selected+dates');
}

// Calculate total cost
$totalCost = $days * $vehicle['price_per_day'];

// Insert booking
$insert = $conn->prepare("
    INSERT INTO bookings
    (user_id, vehicle_id, start_date, end_date, total_price, payment_method, status, pickup_location, created_at)
    VALUES (?, ?, ?, ?, ?, ?, 'confirmed', ?, NOW())
");
$insert->bind_param(
    "iissdss",
    $currentUser['id'],
    $vehicleId,
    $startDate,
    $endDate,
    $totalCost,
    $paymentMethod,
    $vehicle['location']
);

if (!$insert->execute()) {
    redirect('vehicle-details.php?id=' . $vehicleId . '&error=Booking+failed.+Please+try+again.');
}

$bookingId = $conn->insert_id;

// Send confirmation email
$newBooking = [
    'start_date'      => $startDate,
    'end_date'        => $endDate,
    'total_price'     => $totalCost,
    'payment_method'  => $paymentMethod,
    'pickup_location' => $vehicle['location'],
];
sendBookingConfirmation($currentUser, $newBooking, $vehicle);

// Redirect to booking confirmation page
redirect('booking-confirmation.php?id=' . $bookingId);