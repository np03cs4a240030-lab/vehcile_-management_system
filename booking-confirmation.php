<?php
require_once 'config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();
$bookingId   = (int)($_GET['id'] ?? 0);

if (!$bookingId) { redirect('my-bookings.php'); }

$stmt = $conn->prepare("
    SELECT b.*, v.name AS vehicle_name, v.type AS vehicle_type,
           v.image AS vehicle_image, v.price_per_day AS price_per_day,
           v.location AS vehicle_location
    FROM bookings b JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->bind_param("ii", $bookingId, $currentUser['id']);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) { redirect('my-bookings.php'); }

$start = new DateTime($booking['start_date']);
$end   = new DateTime($booking['end_date']);
$days  = $booking['total_days'] ?? $start->diff($end)->days;

$isPending  = $booking['status'] === 'pending';
$isApproved = in_array($booking['status'], ['approved','confirmed']);

// ── ONE-TIME POPUP: show only when ?new=1 AND DB flag not yet set ──
$showPopup = false;
if (isset($_GET['new']) && $_GET['new'] === '1' && empty($booking['popup_shown'])) {
    $showPopup = true;
    // Immediately mark as shown so any refresh/revisit won't trigger it again
    $upd = $conn->prepare("UPDATE bookings SET popup_shown = 1 WHERE id = ?");
    $upd->bind_param("i", $bookingId);
    $upd->execute();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking <?php echo $isPending ? 'Submitted' : 'Confirmed'; ?> - Bhatbhatey Rental</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary:#f97316; --primary-light:#fff7ed;
            --dark:#0f172a;    --dark-blue:#1e293b;
            --slate:#64748b;   --border:#e2e8f0; --bg:#f1f5f9;
        }
        *{ margin:0; padding:0; box-sizing:border-box; font-family:'Plus Jakarta Sans',sans-serif; }
        body{ background:var(--bg); display:flex; min-height:100vh; }
        a{ text-decoration:none; transition:0.2s; }

        /* ── SIDEBAR ── */
        .sidebar{ width:250px; background:var(--dark-blue); position:fixed; height:100%; display:flex; flex-direction:column; box-shadow:4px 0 20px rgba(0,0,0,0.15); z-index:100; }
        .sidebar-logo{ padding:16px 18px; border-bottom:1px solid rgba(255,255,255,0.08); display:flex; align-items:center; gap:10px; }
        .sidebar-logo img{ height:38px; width:auto; object-fit:contain; filter:brightness(0) invert(1); }
        .logo-fallback{ display:none; width:36px; height:36px; background:var(--primary); border-radius:9px; align-items:center; justify-content:center; color:white; font-size:15px; flex-shrink:0; }
        .sidebar-logo-text{ display:flex; flex-direction:column; line-height:1.2; }
        .sidebar-logo-text .lt-name{ color:white; font-size:15px; font-weight:800; }
        .sidebar-logo-text .lt-sub{ color:#64748b; font-size:9px; text-transform:uppercase; letter-spacing:1px; }
        .sidebar-nav{ padding:16px 12px; flex:1; overflow-y:auto; }
        .nav-section-label{ font-size:10px; text-transform:uppercase; letter-spacing:1.5px; color:#475569; font-weight:700; padding:0 8px; margin:16px 0 6px; }
        .sidebar-nav a{ display:flex; align-items:center; gap:11px; padding:11px 12px; border-radius:10px; color:#94a3b8; font-size:14px; font-weight:600; margin-bottom:3px; }
        .sidebar-nav a i{ width:18px; text-align:center; font-size:14px; }
        .sidebar-nav a:hover{ background:#334155; color:white; }
        .sidebar-nav a.active{ background:var(--primary); color:white; box-shadow:0 4px 12px rgba(249,115,22,0.3); }
        .sidebar-nav a.danger:hover{ background:#7f1d1d; color:#fca5a5; }
        .sidebar-footer{ padding:12px 14px; border-top:1px solid rgba(255,255,255,0.07); }
        .user-card{ display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:12px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.07); }
        .user-initials{ width:36px; height:36px; background:var(--primary); border-radius:9px; display:flex; align-items:center; justify-content:center; color:white; font-size:12px; font-weight:800; flex-shrink:0; letter-spacing:0.5px; text-transform:uppercase; }
        .user-info-inner{ flex:1; min-width:0; }
        .u-name{ color:white; font-size:13px; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .u-badge{ display:inline-flex; align-items:center; gap:4px; font-size:10px; color:#94a3b8; margin-top:2px; }
        .u-badge i{ font-size:7px; color:#22c55e; }

        /* ── MAIN ── */
        .main{ margin-left:250px; padding:32px; width:100%; min-height:100vh; }
        .back-link{ display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:600; color:var(--slate); margin-bottom:28px; }
        .back-link:hover{ color:var(--primary); }
        .confirmation-wrapper{ max-width:620px; }

        /* ── HERO ── */
        .hero-banner{
            border-radius:20px; padding:38px 32px; text-align:center;
            color:white; margin-bottom:20px; position:relative; overflow:hidden;
        }
        .hero-pending  { background:linear-gradient(135deg,#f59e0b,#d97706); }
        .hero-confirmed{ background:linear-gradient(135deg,#10b981,#059669); }
        .hero-banner::before{
            content:''; position:absolute; top:-40px; right:-40px;
            width:180px; height:180px; border-radius:50%;
            background:rgba(255,255,255,0.07);
        }
        .hero-banner::after{
            content:''; position:absolute; bottom:-50px; left:-30px;
            width:140px; height:140px; border-radius:50%;
            background:rgba(255,255,255,0.05);
        }
        .status-icon{
            width:68px; height:68px; background:rgba(255,255,255,0.2);
            border-radius:50%; display:flex; align-items:center; justify-content:center;
            margin:0 auto 16px; font-size:28px;
            border:2px solid rgba(255,255,255,0.3); position:relative; z-index:1;
        }
        .hero-banner h1{ font-size:23px; font-weight:800; margin-bottom:8px; position:relative; z-index:1; }
        .hero-banner p{ font-size:13px; opacity:.9; line-height:1.65; position:relative; z-index:1; }
        .booking-id-chip{
            display:inline-flex; align-items:center; gap:7px;
            background:rgba(255,255,255,0.18); border:1px solid rgba(255,255,255,0.35);
            border-radius:100px; padding:6px 18px;
            font-weight:800; font-size:13px; margin-top:16px;
            color:white; position:relative; z-index:1;
        }

        /* ── NOTICE BOX ── */
        .notice-box{
            background:#fffbeb; border:1px solid #fcd34d; border-radius:14px;
            padding:15px 18px; margin-bottom:16px;
            display:flex; gap:12px; align-items:flex-start;
        }
        .notice-icon{
            width:30px; height:30px; background:#fef3c7; border-radius:8px;
            display:flex; align-items:center; justify-content:center;
            flex-shrink:0; color:#d97706; font-size:13px; margin-top:1px;
        }
        .notice-box p{ color:#92400e; font-size:13px; line-height:1.65; margin:0; }
        .notice-box a{ color:#d97706; font-weight:700; }

        /* ── DETAIL CARDS ── */
        .detail-card{ background:white; border:1px solid var(--border); border-radius:16px; overflow:hidden; margin-bottom:14px; box-shadow:0 1px 3px rgba(0,0,0,0.04); }
        .card-head{ padding:12px 20px; background:#f8fafc; border-bottom:1px solid var(--border); font-weight:700; font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:0.8px; display:flex; align-items:center; gap:8px; }
        .card-head i{ color:var(--primary); font-size:13px; }
        .card-body{ padding:4px 20px 8px; }

        .detail-row{ display:flex; justify-content:space-between; align-items:center; padding:11px 0; border-bottom:1px solid #f8fafc; font-size:13.5px; }
        .detail-row:last-child{ border-bottom:none; }
        .detail-row .lbl{ color:var(--slate); display:flex; align-items:center; gap:8px; }
        .detail-row .lbl i{ font-size:11px; color:#cbd5e1; width:14px; text-align:center; }
        .detail-row .val{ font-weight:700; color:var(--dark); }

        .total-row{ display:flex; justify-content:space-between; align-items:center; padding:14px 20px; background:var(--primary-light); border-top:1px solid #fed7aa; }
        .total-row .lbl{ font-size:14px; font-weight:700; color:#c2410c; }
        .total-row .val{ font-size:21px; font-weight:800; color:var(--primary); }

        /* ── VEHICLE ROW ── */
        .vehicle-row{ display:flex; gap:16px; align-items:center; padding:14px 0; }
        .vehicle-thumb{ width:96px; height:68px; border-radius:10px; object-fit:cover; border:1px solid var(--border); flex-shrink:0; }
        .vehicle-thumb-fallback{ width:96px; height:68px; border-radius:10px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#cbd5e1; font-size:24px; border:1px solid var(--border); flex-shrink:0; }
        .vehicle-name{ font-size:16px; font-weight:800; color:var(--dark); margin-bottom:3px; }
        .vehicle-type{ font-size:12px; color:var(--slate); margin-bottom:8px; }

        /* ── BADGE ── */
        .badge{ padding:4px 11px; border-radius:100px; font-size:11px; font-weight:700; display:inline-flex; align-items:center; gap:4px; }
        .badge.confirmed,.badge.approved{ background:#d1fae5; color:#065f46; }
        .badge.pending{ background:#fef9c3; color:#ca8a04; }
        .badge.cancelled{ background:#fee2e2; color:#dc2626; }
        .badge.ongoing{ background:#fce7f3; color:#be185d; }
        .badge.completed{ background:#dbeafe; color:#1d4ed8; }

        /* ── ACTIONS ── */
        .action-buttons{ display:flex; gap:12px; margin-top:4px; }
        .action-buttons a{ flex:1; padding:13px; border-radius:12px; font-weight:700; font-size:14px; display:flex; align-items:center; justify-content:center; gap:7px; transition:0.2s; }
        .btn-primary-action{ background:var(--primary); color:white; box-shadow:0 4px 14px rgba(249,115,22,0.3); }
        .btn-primary-action:hover{ background:#ea6c10; color:white; }
        .btn-secondary-action{ background:white; color:var(--slate); border:1px solid var(--border); }
        .btn-secondary-action:hover{ background:#f8fafc; }

        /* ── POPUP ── */
        #bookingPopup{
            display:none; position:fixed; inset:0; z-index:9999;
            background:rgba(15,23,42,0.55); backdrop-filter:blur(5px);
            align-items:center; justify-content:center;
        }
        .popup-inner{
            background:white; border-radius:24px; padding:38px 34px;
            max-width:390px; width:90%; text-align:center;
            box-shadow:0 32px 80px rgba(0,0,0,0.2);
            animation:popIn .4s cubic-bezier(.34,1.56,.64,1);
            position:relative;
        }
        .popup-close{
            position:absolute; top:14px; right:14px; background:#f1f5f9;
            border:none; width:28px; height:28px; border-radius:50%;
            font-size:12px; color:var(--slate); cursor:pointer;
            display:flex; align-items:center; justify-content:center; transition:0.2s;
        }
        .popup-close:hover{ background:#e2e8f0; }
        .popup-icon{
            width:76px; height:76px; border-radius:50%; margin:0 auto 18px;
            display:flex; align-items:center; justify-content:center; font-size:32px;
        }
        .popup-title{ font-size:20px; font-weight:800; color:var(--dark); margin-bottom:8px; }
        .popup-subtitle{ color:var(--slate); font-size:13px; line-height:1.65; margin-bottom:18px; }
        .popup-id{
            display:inline-flex; align-items:center; gap:6px;
            background:var(--primary-light); border:1px solid #fed7aa;
            border-radius:100px; padding:5px 14px;
            font-weight:800; color:#c2410c; font-size:13px; margin-bottom:18px;
        }
        .popup-strip{
            background:#f8fafc; border-radius:12px; border:1px solid var(--border);
            overflow:hidden; margin-bottom:20px; text-align:left; font-size:13px;
        }
        .popup-strip-row{
            display:flex; justify-content:space-between;
            padding:10px 15px; border-bottom:1px solid var(--border); color:var(--slate);
        }
        .popup-strip-row:last-child{ border-bottom:none; }
        .popup-strip-row strong{ color:var(--dark); font-weight:700; }
        .popup-actions{ display:flex; gap:10px; }
        .popup-actions a,.popup-actions button{
            flex:1; padding:12px; border-radius:11px; font-weight:700; font-size:13px;
            display:flex; align-items:center; justify-content:center; gap:6px;
            cursor:pointer; transition:0.2s;
        }
        .popup-actions a{
            background:var(--primary); color:white; text-decoration:none;
            box-shadow:0 4px 12px rgba(249,115,22,0.25);
        }
        .popup-actions a:hover{ background:#ea6c10; }
        .popup-actions button{ background:#f1f5f9; color:var(--slate); border:1px solid var(--border); }
        .popup-actions button:hover{ background:#e2e8f0; }

        @keyframes popIn{
            from{ opacity:0; transform:scale(0.88) translateY(16px); }
            to  { opacity:1; transform:scale(1)    translateY(0);     }
        }
    </style>
</head>
<body>

<!-- ── SIDEBAR ── -->
<div class="sidebar">
    <a href="index.php" class="sidebar-logo">
        <img src="assets/images/logo.png" alt="Bhatbhatey Rental"
             onerror="this.style.display='none'; document.querySelector('.logo-fallback').style.display='flex';">
        <div class="logo-fallback"><i class="fas fa-car"></i></div>
        <div class="sidebar-logo-text">
            <span class="lt-name" style="display:none">Bhatbhatey</span>
            <span class="lt-sub">Rental</span>
        </div>
    </a>
    <div class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <a href="user/user-dashboard.php"><i class="fas fa-gauge-high"></i> Dashboard</a>
        <a href="vehicles.php"><i class="fas fa-car"></i> Browse Vehicles</a>
        <a href="my-bookings.php" class="active"><i class="fas fa-calendar-check"></i> My Bookings</a>
        <div class="nav-section-label">Account</div>
        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
        <a href="logout.php" class="danger"><i class="fas fa-right-from-bracket"></i> Logout</a>
    </div>
    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-initials"><?php echo strtoupper(substr($currentUser['name'] ?? 'U', 0, 2)); ?></div>
            <div class="user-info-inner">
                <div class="u-name"><?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?></div>
                <div class="u-badge"><i class="fas fa-circle"></i> Active Member</div>
            </div>
        </div>
    </div>
</div>

<!-- ── MAIN ── -->
<div class="main">
    <a href="my-bookings.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to My Bookings</a>

    <div class="confirmation-wrapper">

        <!-- Hero -->
        <div class="hero-banner <?php echo $isPending ? 'hero-pending' : 'hero-confirmed'; ?>">
            <div class="status-icon">
                <i class="fas <?php echo $isPending ? 'fa-clock' : 'fa-circle-check'; ?>"></i>
            </div>
            <h1><?php echo $isPending ? 'Booking Submitted!' : 'Booking Confirmed!'; ?></h1>
            <p>
                <?php if ($isPending): ?>
                    Your request is <strong>awaiting admin approval</strong>. We'll notify you once it's reviewed.
                <?php else: ?>
                    Your vehicle is reserved. Confirmation sent to <strong><?php echo htmlspecialchars($currentUser['email']); ?></strong>.
                <?php endif; ?>
            </p>
            <div class="booking-id-chip">
                <i class="fas fa-hashtag"></i>
                Booking #<?php echo str_pad($booking['id'], 5, '0', STR_PAD_LEFT); ?>
            </div>
        </div>

        <!-- Pending notice -->
        <?php if ($isPending): ?>
        <div class="notice-box">
            <div class="notice-icon"><i class="fas fa-circle-info"></i></div>
            <p>
                <strong>What happens next?</strong><br>
                An admin will review your request. Once approved, your vehicle will be reserved for the selected dates.
                Track your status in <a href="my-bookings.php">My Bookings</a>.
            </p>
        </div>
        <?php endif; ?>

        <!-- Vehicle -->
        <div class="detail-card">
            <div class="card-head"><i class="fas fa-car"></i> Vehicle</div>
            <div class="card-body">
                <div class="vehicle-row">
                    <?php if (!empty($booking['vehicle_image'])): ?>
                        <img src="<?php echo htmlspecialchars($booking['vehicle_image']); ?>"
                             alt="<?php echo htmlspecialchars($booking['vehicle_name']); ?>"
                             class="vehicle-thumb"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                        <div class="vehicle-thumb-fallback" style="display:none;"><i class="fas fa-car"></i></div>
                    <?php else: ?>
                        <div class="vehicle-thumb-fallback"><i class="fas fa-car"></i></div>
                    <?php endif; ?>
                    <div>
                        <div class="vehicle-name"><?php echo htmlspecialchars($booking['vehicle_name']); ?></div>
                        <div class="vehicle-type"><?php echo htmlspecialchars($booking['vehicle_type']); ?></div>
                        <span class="badge <?php echo $booking['status']; ?>">
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
            </div>
        </div>

        <!-- Payment -->
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
            </div>
            <div class="total-row">
                <span class="lbl">Total Cost</span>
                <span class="val">NPR <?php echo number_format($booking['total_price']); ?></span>
            </div>
        </div>

        <!-- Actions -->
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

<!-- ── POPUP ── -->
<div id="bookingPopup">
    <div class="popup-inner">
        <button class="popup-close" onclick="closePopup()">✕</button>

        <div class="popup-icon" style="<?php echo $isPending ? 'background:#fff7ed;color:#f97316;' : 'background:#d1fae5;color:#059669;'; ?>">
            <i class="fas <?php echo $isPending ? 'fa-clock' : 'fa-circle-check'; ?>"></i>
        </div>

        <div class="popup-title"><?php echo $isPending ? 'Request Sent!' : 'Booking Confirmed!'; ?></div>

        <div class="popup-id">
            <i class="fas fa-hashtag"></i>
            Booking #<?php echo str_pad($booking['id'], 5, '0', STR_PAD_LEFT); ?>
        </div>

        <p class="popup-subtitle">
            <?php if ($isPending): ?>
                <strong><?php echo htmlspecialchars($booking['vehicle_name']); ?></strong> is pending admin approval.
                We'll notify you once confirmed.
            <?php else: ?>
                <strong><?php echo htmlspecialchars($booking['vehicle_name']); ?></strong> is reserved from
                <strong><?php echo date('M d', strtotime($booking['start_date'])); ?></strong> to
                <strong><?php echo date('M d, Y', strtotime($booking['end_date'])); ?></strong>.
            <?php endif; ?>
        </p>

        <div class="popup-strip">
            <div class="popup-strip-row">
                <span>Vehicle</span>
                <strong><?php echo htmlspecialchars($booking['vehicle_name']); ?></strong>
            </div>
            <div class="popup-strip-row">
                <span>Duration</span>
                <strong><?php echo $days; ?> day<?php echo $days > 1 ? 's' : ''; ?></strong>
            </div>
            <div class="popup-strip-row">
                <span>Payment</span>
                <strong><?php echo htmlspecialchars($booking['payment_method']); ?></strong>
            </div>
            <div class="popup-strip-row">
                <span>Total</span>
                <strong style="color:var(--primary);">NPR <?php echo number_format($booking['total_price']); ?></strong>
            </div>
        </div>

        <div class="popup-actions">
            <a href="my-bookings.php"><i class="fas fa-calendar-days"></i> My Bookings</a>
            <button onclick="closePopup()"><i class="fas fa-eye"></i> View Details</button>
        </div>
    </div>
</div>

<script>
    // PHP already consumed the ?new=1 flag and updated the DB.
    // JS simply reads the server-rendered boolean — no sessionStorage needed.
    const showOnLoad = <?php echo $showPopup ? 'true' : 'false'; ?>;

    window.addEventListener('load', function () {
        if (showOnLoad) {
            document.getElementById('bookingPopup').style.display = 'flex';
        }
    });

    function closePopup() {
        const popup = document.getElementById('bookingPopup');
        popup.style.transition = 'opacity .2s';
        popup.style.opacity = '0';
        setTimeout(() => popup.style.display = 'none', 200);
    }

    // Close on backdrop click
    document.getElementById('bookingPopup').addEventListener('click', function (e) {
        if (e.target === this) closePopup();
    });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closePopup();
    });
</script>
</body>
</html>