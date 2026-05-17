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
    redirect('vehicle-details.php?id=' . $vehicleId . '&error=Please+select+both+start+and+end+dates');
}

$today = new DateTime('today');
$start = new DateTime($startDate);
$end   = new DateTime($endDate);

if ($start < $today) {
    redirect('vehicle-details.php?id=' . $vehicleId . '&error=Start+date+cannot+be+in+the+past');
}

$days = $start->diff($end)->days;

if ($days <= 0) {
    redirect('vehicle-details.php?id=' . $vehicleId . '&error=End+date+must+be+after+start+date');
}

if ($days > 90) {
    redirect('vehicle-details.php?id=' . $vehicleId . '&error=Maximum+booking+duration+is+90+days');
}

// Check overlapping approved/ongoing bookings — block if vehicle already confirmed for these dates
$check = $conn->prepare("
    SELECT id FROM bookings
    WHERE vehicle_id = ?
    AND status IN ('approved','confirmed','ongoing')
    AND start_date < ?
    AND end_date > ?
");
$check->bind_param("iss", $vehicleId, $endDate, $startDate);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    redirect('vehicle-details.php?id=' . $vehicleId . '&error=Vehicle+is+not+available+for+those+dates');
}

// Calculate total cost
$totalCost = $days * $vehicle['price_per_day'];

// Insert booking — status starts as pending
$insert = $conn->prepare("
    INSERT INTO bookings
    (user_id, vehicle_id, start_date, end_date, total_days, total_price,
     payment_method, status, pickup_location, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
");
$insert->bind_param(
    "iisiidss",
    $currentUser['id'],
    $vehicleId,
    $startDate,
    $endDate,
    $days,
    $totalCost,
    $paymentMethod,
    $vehicle['location']
);

if (!$insert->execute()) {
    redirect('vehicle-details.php?id=' . $vehicleId . '&error=Booking+failed.+Please+try+again.');
}

$bookingId = $conn->insert_id;

// Send confirmation email (best-effort)
$newBooking = [
    'start_date'      => $startDate,
    'end_date'        => $endDate,
    'total_days'      => $days,
    'total_price'     => $totalCost,
    'payment_method'  => $paymentMethod,
    'pickup_location' => $vehicle['location'],
    'hire_driver'     => $hireDriver,
    'pickup_service'  => $pickupService,
];
if (function_exists('sendBookingConfirmation')) {
    @sendBookingConfirmation($currentUser, $newBooking, $vehicle);
}

// Redirect to booking confirmation
redirect('booking-confirmation.php?id=' . $bookingId . '&new=1');