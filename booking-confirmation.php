<?php
require_once 'config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();
$bookingId   = (int)($_GET['id'] ?? 0);

if (!$bookingId) {
    redirect('my-bookings.php');
}

// Fetch booking + vehicle details
$stmt = $conn->prepare("
    SELECT
        b.*,
        v.name          AS vehicle_name,
        v.type          AS vehicle_type,
        v.image         AS vehicle_image,
        v.price_per_day AS price_per_day
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->bind_param("ii", $bookingId, $currentUser['id']);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

// Make sure the booking belongs to this user
if (!$booking) {
    redirect('my-bookings.php');
}

// Calculate number of days
$start = new DateTime($booking['start_date']);
$end   = new DateTime($booking['end_date']);
$days  = $start->diff($end)->days;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - Bhatbhatey Rental</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .nav-icon { width:18px; height:18px; vertical-align:middle; margin-right:8px; stroke-width:2; }

        .confirmation-wrapper {
            max-width: 680px;
            margin: 0 auto;
        }

        .confirmation-hero {
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            color: white;
            margin-bottom: 24px;
        }

        .confirmation-hero .check-circle {
            width: 72px; height: 72px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
        }

        .confirmation-hero h1 { font-size: 28px; margin: 0 0 8px; }
        .confirmation-hero p  { font-size: 15px; opacity: 0.9; margin: 0; }

        .detail-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .detail-card-header {
            padding: 16px 24px;
            background: var(--brand-light-gray);
            border-bottom: 1px solid var(--border-color);
            font-weight: 700;
            font-size: 14px;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-card-body { padding: 24px; }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-row .label { color: #64748b; display: flex; align-items: center; gap: 6px; }
        .detail-row .value { font-weight: 600; color: #1e293b; }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0 0;
            margin-top: 8px;
            border-top: 2px solid var(--border-color);
        }
        .total-row .label { font-size: 16px; font-weight: 700; color: #1e293b; }
        .total-row .value { font-size: 24px; font-weight: 800; color: var(--brand-orange); }

        .vehicle-banner {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 20px;
            align-items: center;
        }
        .vehicle-banner img {
            width: 100%; height: 96px;
            object-fit: cover; border-radius: 8px;
        }

        .action-buttons {
            display: flex; gap: 12px; margin-top: 8px;
        }
        .action-buttons a {
            flex: 1; text-align: center;
        }

        .badge-confirmed {
            display: inline-block;
            padding: 4px 12px;
            background: #d1fae5;
            color: #065f46;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="assets/images/logo.png" alt="Bhatbhatey Rental" onerror="this.style.display='none'">
            </div>
            <div class="sidebar-menu">
                <a href="user/user-dashboard.php"><i data-lucide="layout-dashboard" class="nav-icon"></i> Dashboard</a>
                <a href="vehicles.php"><i data-lucide="car" class="nav-icon"></i> Available Vehicles</a>
                <a href="my-bookings.php" class="active"><i data-lucide="calendar-check" class="nav-icon"></i> My Bookings</a>
                <a href="profile.php"><i data-lucide="user" class="nav-icon"></i> Profile</a>
                <a href="logout.php"><i data-lucide="log-out" class="nav-icon"></i> Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <a href="my-bookings.php" style="color:#64748b; text-decoration:none;">← Back to My Bookings</a>
            </div>

            <div class="content-body">
                <div class="confirmation-wrapper">

                    <!-- Hero Banner -->
                    <div class="confirmation-hero">
                        <div class="check-circle">
                            <i data-lucide="check" style="width:36px;height:36px;stroke-width:3;"></i>
                        </div>
                        <h1>Booking Confirmed!</h1>
                        <p>Your vehicle has been successfully reserved. A confirmation email has been sent to <strong><?php echo htmlspecialchars($currentUser['email']); ?></strong></p>
                    </div>

                    <!-- Vehicle Info -->
                    <div class="detail-card">
                        <div class="detail-card-header">
                            <i data-lucide="car" style="width:16px;height:16px;"></i> Vehicle
                        </div>
                        <div class="detail-card-body">
                            <div class="vehicle-banner">
                                <img src="<?php echo htmlspecialchars($booking['vehicle_image'] ?? ''); ?>"
                                     alt="<?php echo htmlspecialchars($booking['vehicle_name']); ?>">
                                <div>
                                    <h2 style="font-size:20px; margin:0 0 4px;"><?php echo htmlspecialchars($booking['vehicle_name']); ?></h2>
                                    <p style="color:#64748b; margin:0 0 10px;"><?php echo htmlspecialchars($booking['vehicle_type']); ?></p>
                                    <span class="badge-confirmed">Confirmed</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Details -->
                    <div class="detail-card">
                        <div class="detail-card-header">
                            <i data-lucide="calendar" style="width:16px;height:16px;"></i> Booking Details
                        </div>
                        <div class="detail-card-body">
                            <div class="detail-row">
                                <span class="label"><i data-lucide="hash" style="width:14px;height:14px;"></i> Booking ID</span>
                                <span class="value">#<?php echo str_pad($booking['id'], 5, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label"><i data-lucide="calendar" style="width:14px;height:14px;"></i> Start Date</span>
                                <span class="value"><?php echo date('D, d M Y', strtotime($booking['start_date'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label"><i data-lucide="calendar" style="width:14px;height:14px;"></i> End Date</span>
                                <span class="value"><?php echo date('D, d M Y', strtotime($booking['end_date'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label"><i data-lucide="clock" style="width:14px;height:14px;"></i> Duration</span>
                                <span class="value"><?php echo $days; ?> day<?php echo $days > 1 ? 's' : ''; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label"><i data-lucide="map-pin" style="width:14px;height:14px;"></i> Pickup Location</span>
                                <span class="value"><?php echo htmlspecialchars($booking['pickup_location'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Details -->
                    <div class="detail-card">
                        <div class="detail-card-header">
                            <i data-lucide="credit-card" style="width:16px;height:16px;"></i> Payment
                        </div>
                        <div class="detail-card-body">
                            <div class="detail-row">
                                <span class="label"><i data-lucide="banknote" style="width:14px;height:14px;"></i> Rate</span>
                                <span class="value">NPR <?php echo number_format($booking['price_per_day']); ?> / day</span>
                            </div>
                            <div class="detail-row">
                                <span class="label"><i data-lucide="clock" style="width:14px;height:14px;"></i> Duration</span>
                                <span class="value"><?php echo $days; ?> day<?php echo $days > 1 ? 's' : ''; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label"><i data-lucide="credit-card" style="width:14px;height:14px;"></i> Payment Method</span>
                                <span class="value"><?php echo htmlspecialchars($booking['payment_method']); ?></span>
                            </div>
                            <div class="total-row">
                                <span class="label">Total Cost</span>
                                <span class="value">NPR <?php echo number_format($booking['total_price']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <a href="my-bookings.php" class="btn btn-primary">
                            View All Bookings
                        </a>
                        <a href="vehicles.php" class="btn btn-secondary">
                            Browse More Vehicles
                        </a>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>lucide.createIcons();</script>
</body>
</html>