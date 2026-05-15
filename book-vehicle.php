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
$hireDriver    = isset($_POST['hire_driver']) ? 1 : 0;
$pickupService = isset($_POST['pickup_service']) ? 1 : 0;
$pickupAddress = sanitize($_POST['pickup_address'] ?? '');
$dropAddress   = sanitize($_POST['drop_address']   ?? '');
$specialNote   = sanitize($_POST['special_note']   ?? '');

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

// Check overlapping bookings (pending, approved, confirmed, ongoing)
$check = $conn->prepare("
    SELECT id FROM bookings
    WHERE vehicle_id = ?
    AND status IN ('pending','approved','confirmed','ongoing')
    AND (
        (start_date <= ? AND end_date >= ?) OR
        (start_date <= ? AND end_date >= ?) OR
        (start_date >= ? AND end_date <= ?)
    )
");
$check->bind_param("issssss", $vehicleId, $endDate, $startDate, $endDate, $endDate, $startDate, $endDate);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    redirect('vehicle-details.php?id=' . $vehicleId . '&error=Vehicle+is+already+booked+for+those+dates');
}

// Calculate total cost (driver adds NPR 1500/day, pickup service flat NPR 500)
$totalCost = $days * $vehicle['price_per_day'];
if ($hireDriver)    $totalCost += $days * 1500;
if ($pickupService) $totalCost += 500;

// Sprint 2: bookings now start as 'pending' — admin must approve
$initialStatus = 'pending';

// Insert booking
$insert = $conn->prepare("
    INSERT INTO bookings
    (user_id, vehicle_id, start_date, end_date, total_days, total_price,
     payment_method, status, pickup_location, hire_driver, pickup_service,
     pickup_address, drop_address, special_note, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");
$insert->bind_param(
    "iissiidssiisss",
    $currentUser['id'],
    $vehicleId,
    $startDate,
    $endDate,
    $days,
    $totalCost,
    $paymentMethod,
    $initialStatus,
    $vehicle['location'],
    $hireDriver,
    $pickupService,
    $pickupAddress,
    $dropAddress,
    $specialNote
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
redirect('booking-confirmation.php?id=' . $bookingId);