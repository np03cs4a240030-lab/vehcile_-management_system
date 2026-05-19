<?php

require_once 'config.php';
require_once 'mailer.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

$vehicleId = $_GET['id'] ?? 0;

// Fetch vehicle
$stmt = $conn->prepare("SELECT * FROM vehicles WHERE id=?");
$stmt->bind_param("i", $vehicleId);
$stmt->execute();
$vehicle = $stmt->get_result()->fetch_assoc();

if (!$vehicle) {
    redirect('vehicles.php');
}

$currentUser = getCurrentUser();
$error = "";

/* 
   FETCH REVIEWS
*/
$reviewStmt = $conn->prepare("
    SELECT 
        r.rating,
        r.review_text,
        r.created_at,
        u.name AS reviewer_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.vehicle_id = ?
    ORDER BY r.created_at DESC
");
$reviewStmt->bind_param("i", $vehicleId);
$reviewStmt->execute();
$reviewsResult = $reviewStmt->get_result();
$reviews = $reviewsResult->fetch_all(MYSQLI_ASSOC);

$totalReviews = count($reviews);
$avgRating = $totalReviews > 0
    ? array_sum(array_column($reviews, 'rating')) / $totalReviews
    : 0;

/*
   HANDLE BOOKING
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $paymentMethod = $_POST['payment_method'] ?? 'cash';

    if (!$startDate || !$endDate) {
        $error = "Please select dates";
    } else {

        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $days = $start->diff($end)->days;

        if ($days <= 0) {
            $error = "Invalid date range";
        } else {

            // CHECK OVERLAPPING BOOKINGS — only block if already approved/ongoing
            $check = $conn->prepare("
                SELECT id FROM bookings 
                WHERE vehicle_id = ?
                AND status IN ('approved','confirmed','ongoing')
                AND start_date < ?
                AND end_date > ?
            ");

            $check->bind_param(
                "iss",
                $vehicleId,
                $endDate,
                $startDate
            );

            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                $error = "Vehicle is already booked for those dates. Please choose different dates.";
            } else {

                // CALCULATE PRICE
                $totalCost = $days * $vehicle['price_per_day'];

                // INSERT BOOKING — status starts as 'pending', admin must approve
                $stmt = $conn->prepare("
    INSERT INTO bookings 
    (user_id, vehicle_id, start_date, end_date, total_days, total_price, payment_method, status, pickup_location, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
");

                $stmt->bind_param(
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

                if ($stmt->execute()) {
                    $bookingId = $conn->insert_id;

                    // ── Send booking confirmation email ──────────────────────
                    $userEmail = $currentUser['email'];
                    $userName  = $currentUser['name'];

                    $emailSubject = "Booking Received – {$vehicle['name']} | Bhatbhatey Rental";
                    $durationLabel = $days . ' day' . ($days > 1 ? 's' : '');
                    $pickupFormatted = date('F j, Y', strtotime($startDate));
                    $returnFormatted = date('F j, Y', strtotime($endDate));
                    $totalFormatted  = 'NPR ' . number_format($totalCost);
                    $yearNow         = date('Y');

                    $emailBody = "
<h2>Booking Request Received</h2>

<p>Hi <strong>{$userName}</strong>,</p>

<p>Your booking request has been submitted successfully. It is currently <strong>pending admin approval</strong>. You will be notified once it is approved.</p>

<hr>

<p><strong>Booking ID:</strong> #{$bookingId}</p>
<p><strong>Vehicle:</strong> {$vehicle['name']}</p>
<p><strong>Pickup Date:</strong> {$pickupFormatted}</p>
<p><strong>Return Date:</strong> {$returnFormatted}</p>
<p><strong>Duration:</strong> {$durationLabel}</p>
<p><strong>Payment Method:</strong> {$paymentMethod}</p>
<p><strong>Total Price:</strong> {$totalFormatted}</p>
<p><strong>Status:</strong> Pending Approval</p>

<hr>

<p>Thank you for using <strong>Bhatbhatey Rental</strong>.</p>
<p>We will review your booking and confirm it shortly.</p>
";

                    sendMail($userEmail, $emailSubject, $emailBody);
                     

                    if ($paymentMethod === 'online') {
                        redirect('my-bookings.php?msg=booking_pending');
                    } else {
                        redirect('booking-confirmation.php?id=' . $bookingId . '&new=1');
                    }
                } else {
                    $error = "Booking failed";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($vehicle['name']); ?> - Bhatbhatey Rental</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #f97316;
            --primary-light: #fff7ed;
            --dark: #0f172a;
            --dark-blue: #1e293b;
            --slate: #64748b;
            --border: #e2e8f0;
            --bg: #f1f5f9;
        }

        /* SIDEBAR */
        .sidebar {
            width: 250px;
            background: var(--dark-blue);
            position: fixed;
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
            z-index: 100;
        }

        .sidebar-logo {
            padding: 16px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .sidebar-logo img {
            height: 38px;
            width: auto;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }

        .logo-fallback {
            display: none;
            width: 36px;
            height: 36px;
            background: var(--primary);
            border-radius: 9px;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 15px;
            flex-shrink: 0;
        }

        .sidebar-logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .sidebar-logo-text .lt-name {
            color: white;
            font-size: 15px;
            font-weight: 800;
        }

        .sidebar-logo-text .lt-sub {
            color: #64748b;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .sidebar-nav {
            padding: 16px 12px;
            flex: 1;
            overflow-y: auto;
        }

        .nav-section-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #475569;
            font-weight: 700;
            padding: 0 8px;
            margin: 16px 0 6px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 11px 12px;
            border-radius: 10px;
            color: #94a3b8;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 3px;
            text-decoration: none;
        }

        .sidebar-nav a i {
            width: 18px;
            text-align: center;
            font-size: 14px;
        }

        .sidebar-nav a:hover {
            background: #334155;
            color: white;
        }

        .sidebar-nav a.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
        }

        .sidebar-nav a.danger:hover {
            background: #7f1d1d;
            color: #fca5a5;
        }

        .sidebar-footer {
            padding: 12px 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.07);
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.07);
        }

        .user-initials {
            width: 36px;
            height: 36px;
            background: var(--primary);
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: 800;
            flex-shrink: 0;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .user-info-inner {
            flex: 1;
            min-width: 0;
        }

        .u-name {
            color: white;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .u-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            color: #94a3b8;
            margin-top: 2px;
        }

        .u-badge i {
            font-size: 7px;
            color: #22c55e;
        }

        .main-content {
            margin-left: 250px;
        }
    </style>
</head>

<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <a href="index.php" class="sidebar-logo">
                <img src="nobglogo.png" alt="Bhatbhatey"
                    onerror="this.style.display='none'; document.querySelector('.logo-fallback').style.display='flex';">
                <div class="logo-fallback"><i class="fas fa-car"></i></div>
                <div class="sidebar-logo-text">
                    <span class="lt-name">Bhatbhatey</span>
                    <span class="lt-sub">Rental</span>
                </div>
            </a>

            <div class="sidebar-nav">
                <div class="nav-section-label">Main</div>
                <a href="user/user-dashboard.php"><i class="fas fa-gauge-high"></i> Dashboard</a>
                <a href="vehicles.php" class="active"><i class="fas fa-car"></i> Browse Vehicles</a>
                <a href="my-bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a>
            <a href="support-tickets.php"><i class="fas fa-ticket-alt"></i> Support Tickets</a>
                <div class="nav-section-label">Account</div>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="logout.php" class="danger"><i class="fas fa-right-from-bracket"></i> Logout</a>
            </div>

            <div class="sidebar-footer">
                <div class="user-card">
                    <div class="user-initials">
                        <?php echo strtoupper(substr($currentUser['name'] ?? 'U', 0, 2)); ?>
                    </div>
                    <div class="user-info-inner">
                        <div class="u-name"><?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?></div>
                        <div class="u-badge"><i class="fas fa-circle"></i> Active Member</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <a href="vehicles.php" style="color: #64748b; text-decoration: none;">← Back to Vehicles</a>
            </div>

            <div class="content-body">
                <!-- Top 2-col grid: vehicle details + booking panel -->
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px;">
                    <!-- Vehicle Details -->
                    <div>
                        <div class="vehicle-card">
                            <img src="<?php echo htmlspecialchars($vehicle['image']); ?>" 
                                    alt="<?php echo htmlspecialchars($vehicle['name']); ?>"
                                    style="width:100%; height:400px; object-fit:cover; object-position:center; border-radius:12px;">
                            
                            <div class="vehicle-content">
                                <div class="vehicle-header">
                                    <h1 style="font-size: 32px; margin: 0;">
                                        <?php echo htmlspecialchars($vehicle['name']); ?>
                                    </h1>
                                    <span class="vehicle-type"><?php echo htmlspecialchars($vehicle['type']); ?></span>
                                </div>

                                <div
                                    style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin: 24px 0;">
                                    <div
                                        style="padding: 16px; background: var(--brand-light-gray); border-radius: 8px;">
                                        <div style="color: #64748b; font-size: 12px;">Location</div>
                                        <div style="font-weight: 600;">
                                            <?php echo htmlspecialchars($vehicle['location']); ?>
                                        </div>
                                    </div>
                                    <div
                                        style="padding: 16px; background: var(--brand-light-gray); border-radius: 8px;">
                                        <div style="color: #64748b; font-size: 12px;">Fuel Type</div>
                                        <div style="font-weight: 600;">
                                            <?php echo htmlspecialchars($vehicle['fuel_type']); ?>
                                        </div>
                                    </div>
                                    <div
                                        style="padding: 16px; background: var(--brand-light-gray); border-radius: 8px;">
                                        <div style="color: #64748b; font-size: 12px;">Transmission</div>
                                        <div style="font-weight: 600;">
                                            <?php echo htmlspecialchars($vehicle['transmission']); ?>
                                        </div>
                                    </div>
                                    <div
                                        style="padding: 16px; background: var(--brand-light-gray); border-radius: 8px;">
                                        <div style="color: #64748b; font-size: 12px;">Seats</div>
                                        <div style="font-weight: 600;"> <?php echo $vehicle['seats']; ?></div>
                                    </div>
                                </div>

                                <div style="margin: 24px 0;">
                                    <h3 style="font-size: 20px; margin-bottom: 12px;">Description</h3>
                                    <p style="color: #475569; line-height: 1.7;">
                                        <?php echo htmlspecialchars($vehicle['description']); ?>
                                    </p>
                                </div>

                                <div>
                                    <h3 style="font-size: 20px; margin-bottom: 12px;">Features</h3>
                                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                                        <?php
                                        $features = explode(', ', $vehicle['features']);
                                        foreach ($features as $feature):
                                            ?>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <span><?php echo htmlspecialchars($feature); ?></span>
                                            </div>
                                            
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Panel -->
                    <div style="position: sticky; top: 32px;">
                        <div class="vehicle-card">
                            <div class="vehicle-content">
                                <h3 style="font-size: 20px; margin-bottom: 16px;">Book This Vehicle</h3>

                                <div
                                    style="font-size: 32px; font-weight: 700; color: var(--brand-orange); margin-bottom: 24px;">
                                    NPR <?php echo number_format($vehicle['price_per_day']); ?>
                                    <span style="font-size: 16px; color: #64748b;">/day</span>
                                </div>

                                <form method="POST" id="bookingForm">

                                    <?php if ($error): ?>
                                        <div style="background:#fee2e2; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:14px;">
                                            ⚠️ <?php echo htmlspecialchars($error); ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="form-group">
                                        <label>📅 Start Date</label>
                                        <input type="date" name="start_date" id="startDate" class="form-control"
                                            required min="<?php echo date('Y-m-d'); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label>📅 End Date</label>
                                        <input type="date" name="end_date" id="endDate" class="form-control" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                    </div>

                                    <div id="costSummary"
                                        style="display: none; background: var(--brand-light-gray); padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                            <span>Total Days</span>
                                            <span id="totalDays">0</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; font-size: 18px;">
                                            <strong>Total Cost</strong>
                                            <strong style="color: var(--brand-orange);" id="totalCost">NPR 0</strong>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Payment Method</label>
                                        <select name="payment_method" class="form-control">
                                            <option value="cash">Cash on Pickup</option>
                                            <option value="online">Online Payment</option>
                                        </select>
                                    </div>

                                    <button type="submit" class="btn btn-primary" style="width: 100%;">Book Now</button>
                                </form>

                                <?php if (!$vehicle['availability']): ?>
                                    <div
                                        style="margin-top: 16px; padding: 12px; background: #fee2e2; color: #991b1b; border-radius: 8px; text-align: center; font-size: 14px;">
                                        Currently Unavailable
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reviews Section  -->
                <div class="vehicle-card" style="margin-top: 32px;">
                    <div class="vehicle-content">

                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
                            <h3 style="font-size: 22px; margin: 0;"> Customer Reviews</h3>
                            <?php if ($totalReviews > 0): ?>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="font-size: 28px; font-weight: 700; color: var(--brand-orange);">
                                        <?php echo number_format($avgRating, 1); ?>
                                    </div>
                                    <div>
                                        <div style="color: #f59e0b; font-size: 18px; letter-spacing: 2px;">
                                            <?php
                                            $full  = round($avgRating);
                                            $empty = 5 - $full;
                                            echo str_repeat('★', $full);
                                            echo str_repeat('☆', $empty);
                                            ?>
                                        </div>
                                        <div style="color: #64748b; font-size: 13px;">
                                            <?php echo $totalReviews; ?> review<?php echo $totalReviews !== 1 ? 's' : ''; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($totalReviews === 0): ?>
                            <div style="text-align: center; padding: 40px 0; color: #94a3b8;">
                                <div style="font-size: 48px; margin-bottom: 12px;">💬</div>
                                <p style="font-size: 16px;">No reviews yet. Be the first to book and review this vehicle!</p>
                            </div>

                        <?php else: ?>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px;">
                                <?php foreach ($reviews as $review): ?>
                                    <div style="padding: 20px; background: var(--brand-light-gray); border-radius: 10px;">
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <div style="width: 42px; height: 42px; border-radius: 50%; background: var(--brand-orange); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 17px; flex-shrink: 0;">
                                                    <?php echo strtoupper(substr($review['reviewer_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; font-size: 15px;">
                                                        <?php echo htmlspecialchars($review['reviewer_name']); ?>
                                                    </div>
                                                    <div style="color: #64748b; font-size: 12px;">
                                                        <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div style="color: #f59e0b; font-size: 16px; letter-spacing: 1px; flex-shrink: 0;">
                                                <?php echo str_repeat('★', (int)$review['rating']) . str_repeat('☆', 5 - (int)$review['rating']); ?>
                                            </div>
                                        </div>
                                        <p style="color: #475569; line-height: 1.65; margin: 0; font-size: 14px;">
                                            <?php echo htmlspecialchars($review['review_text']); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </main>
    </div>

   

    <script>
        const pricePerDay = <?php echo $vehicle['price_per_day']; ?>;
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');
        const costSummary = document.getElementById('costSummary');
        const totalDaysSpan = document.getElementById('totalDays');
        const totalCostSpan = document.getElementById('totalCost');

        function calculateCost() {
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);

            if (startDate && endDate && endDate > startDate) {
                const diffTime = Math.abs(endDate - startDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                totalDaysSpan.textContent = diffDays;
                totalCostSpan.textContent = 'NPR ' + (diffDays * pricePerDay).toLocaleString();
                costSummary.style.display = 'block';
            } else {
                costSummary.style.display = 'none';
            }
        }

        startDateInput.addEventListener('change', function () {
            endDateInput.min = this.value;
            calculateCost();
        });

        endDateInput.addEventListener('change', calculateCost);
    </script>
</body>

</html>