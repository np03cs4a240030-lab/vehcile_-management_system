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

$stmt = $conn->prepare("
    SELECT
        b.*,
        v.name          AS vehicle_name,
        v.type          AS vehicle_type,
        v.image         AS vehicle_image,
        v.price_per_day AS price_per_day,
        v.location      AS vehicle_location
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->bind_param("ii", $bookingId, $currentUser['id']);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    redirect('my-bookings.php');
}

$start = new DateTime($booking['start_date']);
$end   = new DateTime($booking['end_date']);
$days  = $booking['total_days'] ?? $start->diff($end)->days;

$isPending   = $booking['status'] === 'pending';
$isApproved  = in_array($booking['status'], ['approved','confirmed']);
$hireDriver  = !empty($booking['hire_driver']);
$pickupSvc   = !empty($booking['pickup_service']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking <?php echo $isPending ? 'Submitted' : 'Confirmed'; ?> - Bhatbhatey Rental</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --brand-orange:#f97316; --border-color:#e2e8f0; --bg-light:#f8fafc; }

        .confirmation-wrapper { max-width:700px; margin:0 auto; }

        .hero-banner {
            border-radius:16px; padding:36px; text-align:center;
            color:white; margin-bottom:20px;
        }
        .hero-pending   { background:linear-gradient(135deg,#f59e0b,#d97706); }
        .hero-confirmed { background:linear-gradient(135deg,#10b981,#059669); }

        .status-icon {
            width:70px; height:70px; background:rgba(255,255,255,0.2);
            border-radius:50%; display:flex; align-items:center; justify-content:center;
            margin:0 auto 16px; font-size:32px;
        }
        .hero-banner h1 { font-size:26px; font-weight:800; margin-bottom:8px; }
        .hero-banner p  { font-size:14px; opacity:.9; line-height:1.6; }

        /* Notice box for pending */
        .notice-box {
            background:#fffbeb; border:1px solid #fcd34d; border-radius:12px;
            padding:16px 20px; margin-bottom:20px;
            display:flex; gap:14px; align-items:flex-start;
        }
        .notice-box i { color:#d97706; font-size:20px; margin-top:2px; }
        .notice-box p { color:#92400e; font-size:13px; line-height:1.6; margin:0; }

        /* Detail card */
        .detail-card {
            background:white; border:1px solid var(--border-color);
            border-radius:12px; overflow:hidden; margin-bottom:16px;
        }
        .card-head {
            padding:14px 22px; background:var(--bg-light);
            border-bottom:1px solid var(--border-color);
            font-weight:700; font-size:12px; color:#475569;
            text-transform:uppercase; letter-spacing:0.6px;
            display:flex; align-items:center; gap:8px;
        }
        .card-head i { color:var(--brand-orange); font-size:14px; }
        .card-body { padding:20px 22px; }

        .detail-row {
            display:flex; justify-content:space-between; align-items:center;
            padding:9px 0; border-bottom:1px solid #f1f5f9; font-size:14px;
        }
        .detail-row:last-child { border-bottom:none; }
        .detail-row .lbl { color:#64748b; display:flex; align-items:center; gap:7px; }
        .detail-row .lbl i { font-size:12px; color:#94a3b8; }
        .detail-row .val { font-weight:600; color:#1e293b; text-align:right; max-width:55%; }

        .total-row {
            display:flex; justify-content:space-between; align-items:center;
            padding:14px 0 0; margin-top:8px; border-top:2px solid var(--border-color);
        }
        .total-row .lbl { font-size:15px; font-weight:700; color:#1e293b; }
        .total-row .val { font-size:22px; font-weight:800; color:var(--brand-orange); }

        /* Vehicle banner */
        .vehicle-row { display:grid; grid-template-columns:130px 1fr; gap:18px; align-items:center; }
        .vehicle-row img { width:100%; height:88px; object-fit:cover; border-radius:8px; background:#f1f5f9; }
        .vehicle-row .fallback { width:100%; height:88px; border-radius:8px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:32px; }

        /* Status badge */
        .status-pill { display:inline-flex; align-items:center; gap:5px; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; }
        .pill-pending   { background:#fef9c3; color:#ca8a04; }
        .pill-approved  { background:#d1fae5; color:#065f46; }
        .pill-confirmed { background:#d1fae5; color:#065f46; }

        /* Add-ons row */
        .addon-row { display:flex; align-items:center; gap:10px; padding:10px; background:#f8fafc; border-radius:8px; margin-bottom:8px; font-size:13px; color:#1e293b; }
        .addon-row i { color:#f97316; width:16px; }

        /* Action buttons */
        .action-buttons { display:flex; gap:12px; margin-top:8px; }
        .action-buttons a {
            flex:1; text-align:center; padding:13px 16px; border-radius:10px;
            font-weight:700; font-size:14px; text-decoration:none;
            display:flex; align-items:center; justify-content:center; gap:7px; transition:all .2s;
        }
        .btn-primary-action { background:var(--brand-orange); color:white; }
        .btn-primary-action:hover { background:#ea6c10; }
        .btn-secondary-action { background:#f1f5f9; color:#475569; border:1px solid var(--border-color); }
        .btn-secondary-action:hover { background:#e2e8f0; }

        /* Booking ID highlight */
        .booking-id-box {
            display:inline-flex; align-items:center; gap:8px;
            background:#fff7ed; border:1px solid #fed7aa; border-radius:8px;
            padding:6px 14px; font-weight:800; color:#c2410c; font-size:16px; margin-top:8px;
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
            <a href="user/user-dashboard.php"><i class="fas fa-gauge-high"></i> Dashboard</a>
            <a href="vehicles.php"><i class="fas fa-car"></i> Available Vehicles</a>
            <a href="my-bookings.php" class="active"><i class="fas fa-calendar-check"></i> My Bookings</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-header">
            <a href="my-bookings.php" style="color:#64748b; text-decoration:none; display:inline-flex; align-items:center; gap:6px; font-size:14px;">
                <i class="fas fa-arrow-left"></i> Back to My Bookings
            </a>
        </div>

        <div class="content-body">
            <div class="confirmation-wrapper">

                <!-- Hero Banner -->
                <div class="hero-banner <?php echo $isPending ? 'hero-pending' : 'hero-confirmed'; ?>">
                    <div class="status-icon">
                        <i class="fas <?php echo $isPending ? 'fa-clock' : 'fa-circle-check'; ?>"></i>
                    </div>
                    <h1><?php echo $isPending ? 'Booking Submitted!' : 'Booking Confirmed!'; ?></h1>
                    <p>
                        <?php if ($isPending): ?>
                            Your booking request has been received and is <strong>awaiting admin approval</strong>.
                            You'll be notified once it's reviewed.
                        <?php else: ?>
                            Your vehicle has been successfully reserved. A confirmation has been sent to
                            <strong><?php echo htmlspecialchars($currentUser['email']); ?></strong>
                        <?php endif; ?>
                    </p>
                    <div class="booking-id-box" style="margin-top:16px;">
                        <i class="fas fa-hashtag"></i>
                        Booking #<?php echo str_pad($booking['id'], 5, '0', STR_PAD_LEFT); ?>
                    </div>
                </div>

                <!-- Pending Notice -->
                <?php if ($isPending): ?>
                <div class="notice-box">
                    <i class="fas fa-circle-info"></i>
                    <p>
                        <strong>What happens next?</strong><br>
                        An admin will review your booking request. Once approved, the status will change to
                        <strong>Approved</strong> and your vehicle will be reserved for the selected dates.
                        You can track your booking status in <a href="my-bookings.php" style="color:#d97706; font-weight:600;">My Bookings</a>.
                    </p>
                </div>
                <?php endif; ?>

                <!-- Vehicle Info -->
                <div class="detail-card">
                    <div class="card-head"><i class="fas fa-car"></i> Vehicle</div>
                    <div class="card-body">
                        <div class="vehicle-row">
                            <?php if (!empty($booking['vehicle_image'])): ?>
                                <img src="<?php echo htmlspecialchars($booking['vehicle_image']); ?>"
                                     alt="<?php echo htmlspecialchars($booking['vehicle_name']); ?>"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="fallback" style="display:none;"><i class="fas fa-car"></i></div>
                            <?php else: ?>
                                <div class="fallback"><i class="fas fa-car"></i></div>
                            <?php endif; ?>
                            <div>
                                <h2 style="font-size:19px; margin:0 0 4px;"><?php echo htmlspecialchars($booking['vehicle_name']); ?></h2>
                                <p style="color:#64748b; margin:0 0 10px; font-size:13px;"><?php echo htmlspecialchars($booking['vehicle_type']); ?></p>
                                <span class="status-pill pill-<?php echo $booking['status']; ?>">
                                    <i class="fas <?php echo $isPending ? 'fa-clock' : 'fa-circle-check'; ?>"></i>
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booking Details -->
                <div class="detail-card">
                    <div class="card-head"><i class="fas fa-calendar-days"></i> Booking Details</div>
                    <div class="card-body">
                        <div class="detail-row">
                            <span class="lbl"><i class="fas fa-hashtag"></i> Booking ID</span>
                            <span class="val">#<?php echo str_pad($booking['id'], 5, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="lbl"><i class="fas fa-calendar"></i> Start Date</span>
                            <span class="val"><?php echo date('D, d M Y', strtotime($booking['start_date'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="lbl"><i class="fas fa-calendar-check"></i> End Date</span>
                            <span class="val"><?php echo date('D, d M Y', strtotime($booking['end_date'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="lbl"><i class="fas fa-clock"></i> Duration</span>
                            <span class="val"><?php echo $days; ?> day<?php echo $days > 1 ? 's' : ''; ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="lbl"><i class="fas fa-map-pin"></i> Pickup Location</span>
                            <span class="val"><?php echo htmlspecialchars($booking['pickup_location'] ?? $booking['vehicle_location'] ?? 'N/A'); ?></span>
                        </div>
                        <?php if (!empty($booking['pickup_address'])): ?>
                        <div class="detail-row">
                            <span class="lbl"><i class="fas fa-location-dot"></i> Your Pickup Address</span>
                            <span class="val"><?php echo htmlspecialchars($booking['pickup_address']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($booking['drop_address'])): ?>
                        <div class="detail-row">
                            <span class="lbl"><i class="fas fa-flag-checkered"></i> Drop Address</span>
                            <span class="val"><?php echo htmlspecialchars($booking['drop_address']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($booking['special_note'])): ?>
                        <div class="detail-row">
                            <span class="lbl"><i class="fas fa-note-sticky"></i> Special Note</span>
                            <span class="val"><?php echo htmlspecialchars($booking['special_note']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Add-ons -->
                <?php if ($hireDriver || $pickupSvc): ?>
                <div class="detail-card">
                    <div class="card-head"><i class="fas fa-plus-circle"></i> Add-on Services</div>
                    <div class="card-body" style="padding-bottom:12px;">
                        <?php if ($hireDriver): ?>
                        <div class="addon-row">
                            <i class="fas fa-user-tie"></i>
                            <span><strong>Hired Driver</strong> — NPR 1,500/day × <?php echo $days; ?> days</span>
                            <span style="margin-left:auto; font-weight:700; color:#f97316;">
                                NPR <?php echo number_format($days * 1500); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php if ($pickupSvc): ?>
                        <div class="addon-row">
                            <i class="fas fa-van-shuttle"></i>
                            <span><strong>Pickup/Drop Service</strong> — Flat fee</span>
                            <span style="margin-left:auto; font-weight:700; color:#f97316;">NPR 500</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Payment Details -->
                <div class="detail-card">
                    <div class="card-head"><i class="fas fa-credit-card"></i> Payment</div>
                    <div class="card-body">
                        <div class="detail-row">
                            <span class="lbl"><i class="fas fa-tag"></i> Daily Rate</span>
                            <span class="val">NPR <?php echo number_format($booking['price_per_day']); ?>/day</span>
                        </div>
                        <div class="detail-row">
                            <span class="lbl"><i class="fas fa-clock"></i> Duration</span>
                            <span class="val"><?php echo $days; ?> day<?php echo $days > 1 ? 's' : ''; ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="lbl"><i class="fas fa-wallet"></i> Payment Method</span>
                            <span class="val"><?php echo htmlspecialchars($booking['payment_method']); ?></span>
                        </div>
                        <div class="total-row">
                            <span class="lbl">Total Cost</span>
                            <span class="val">NPR <?php echo number_format($booking['total_price']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="my-bookings.php" class="btn-primary-action">
                        <i class="fas fa-calendar-days"></i> View All Bookings
                    </a>
                    <a href="vehicles.php" class="btn-secondary-action">
                        <i class="fas fa-car"></i> Browse More Vehicles
                    </a>
                </div>

            </div>
        </div>
    </main>
</div>
</body>
</html>