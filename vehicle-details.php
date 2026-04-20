<?php
require_once 'config.php';

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

// Show error passed back from book-vehicle.php
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($vehicle['name']); ?> - Bhatbhatey Rental</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .nav-icon    { width:18px; height:18px; vertical-align:middle; margin-right:8px; stroke-width:2; }
        .detail-icon { width:15px; height:15px; vertical-align:middle; margin-right:5px; stroke-width:2; color:#64748b; }
        .feature-icon{ width:16px; height:16px; vertical-align:middle; color:#10b981; }
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
                <a href="vehicles.php" class="active"><i data-lucide="car" class="nav-icon"></i> Available Vehicles</a>
                <a href="my-bookings.php"><i data-lucide="calendar-check" class="nav-icon"></i> My Bookings</a>
                <a href="profile.php"><i data-lucide="user" class="nav-icon"></i> Profile</a>
                <a href="logout.php"><i data-lucide="log-out" class="nav-icon"></i> Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <a href="vehicles.php" style="color:#64748b; text-decoration:none;">← Back to Vehicles</a>
            </div>

            <div class="content-body">

                <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom:24px;">
                    <i data-lucide="circle-alert" style="width:16px;height:16px;vertical-align:middle;margin-right:6px;"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <div style="display:grid; grid-template-columns:2fr 1fr; gap:32px;">

                    <!-- Vehicle Details -->
                    <div>
                        <div class="vehicle-card">
                            <img src="<?php echo htmlspecialchars($vehicle['image']); ?>"
                                 alt="<?php echo htmlspecialchars($vehicle['name']); ?>"
                                 style="width:100%; height:400px; object-fit:cover;">
                            <div class="vehicle-content">
                                <div class="vehicle-header">
                                    <h1 style="font-size:32px; margin:0;"><?php echo htmlspecialchars($vehicle['name']); ?></h1>
                                    <span class="vehicle-type"><?php echo htmlspecialchars($vehicle['type']); ?></span>
                                </div>

                                <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin:24px 0;">
                                    <div style="padding:16px; background:var(--brand-light-gray); border-radius:8px;">
                                        <div style="color:#64748b; font-size:12px; margin-bottom:4px;">Location</div>
                                        <div style="font-weight:600;"><i data-lucide="map-pin" class="detail-icon"></i><?php echo htmlspecialchars($vehicle['location']); ?></div>
                                    </div>
                                    <div style="padding:16px; background:var(--brand-light-gray); border-radius:8px;">
                                        <div style="color:#64748b; font-size:12px; margin-bottom:4px;">Fuel Type</div>
                                        <div style="font-weight:600;"><i data-lucide="fuel" class="detail-icon"></i><?php echo htmlspecialchars($vehicle['fuel_type']); ?></div>
                                    </div>
                                    <div style="padding:16px; background:var(--brand-light-gray); border-radius:8px;">
                                        <div style="color:#64748b; font-size:12px; margin-bottom:4px;">Transmission</div>
                                        <div style="font-weight:600;"><i data-lucide="settings-2" class="detail-icon"></i><?php echo htmlspecialchars($vehicle['transmission']); ?></div>
                                    </div>
                                    <div style="padding:16px; background:var(--brand-light-gray); border-radius:8px;">
                                        <div style="color:#64748b; font-size:12px; margin-bottom:4px;">Seats</div>
                                        <div style="font-weight:600;"><i data-lucide="users" class="detail-icon"></i><?php echo $vehicle['seats']; ?></div>
                                    </div>
                                </div>

                                <div style="margin:24px 0;">
                                    <h3 style="font-size:20px; margin-bottom:12px;">Description</h3>
                                    <p style="color:#475569; line-height:1.7;"><?php echo htmlspecialchars($vehicle['description']); ?></p>
                                </div>

                                <div>
                                    <h3 style="font-size:20px; margin-bottom:12px;">Features</h3>
                                    <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:12px;">
                                        <?php foreach (explode(', ', $vehicle['features']) as $feature): ?>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <i data-lucide="check-circle" class="feature-icon"></i>
                                            <span><?php echo htmlspecialchars($feature); ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Panel — posts to book-vehicle.php -->
                    <div style="position:sticky; top:32px;">
                        <div class="vehicle-card">
                            <div class="vehicle-content">
                                <h3 style="font-size:20px; margin-bottom:16px;">Book This Vehicle</h3>
                                <div style="font-size:32px; font-weight:700; color:var(--brand-orange); margin-bottom:24px;">
                                    NPR <?php echo number_format($vehicle['price_per_day']); ?>
                                    <span style="font-size:16px; color:#64748b;">/day</span>
                                </div>

                                <form method="POST" action="book-vehicle.php" id="bookingForm">
                                    <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">

                                    <div class="form-group">
                                        <label><i data-lucide="calendar" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;"></i> Start Date</label>
                                        <input type="date" name="start_date" id="startDate" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label><i data-lucide="calendar" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;"></i> End Date</label>
                                        <input type="date" name="end_date" id="endDate" class="form-control" required>
                                    </div>

                                    <div id="costSummary" style="display:none; background:var(--brand-light-gray); padding:16px; border-radius:8px; margin-bottom:24px;">
                                        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                            <span>Total Days</span><span id="totalDays">0</span>
                                        </div>
                                        <div style="display:flex; justify-content:space-between; font-size:18px;">
                                            <strong>Total Cost</strong>
                                            <strong style="color:var(--brand-orange);" id="totalCost">NPR 0</strong>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label><i data-lucide="credit-card" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;"></i> Payment Method</label>
                                        <select name="payment_method" class="form-control">
                                            <option value="Cash on Pickup">Cash on Pickup</option>
                                            <option value="Online Payment">Online Payment (Demo)</option>
                                        </select>
                                    </div>

                                    <button type="submit" class="btn btn-primary" style="width:100%;">Book Now</button>
                                </form>

                                <?php if (!$vehicle['availability']): ?>
                                <div style="margin-top:16px; padding:12px; background:#fee2e2; color:#991b1b; border-radius:8px; text-align:center; font-size:14px;">
                                    Currently Unavailable
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>
        lucide.createIcons();

        const pricePerDay    = <?php echo $vehicle['price_per_day']; ?>;
        const startDateInput = document.getElementById('startDate');
        const endDateInput   = document.getElementById('endDate');
        const costSummary    = document.getElementById('costSummary');
        const totalDaysSpan  = document.getElementById('totalDays');
        const totalCostSpan  = document.getElementById('totalCost');

        function calculateCost() {
            const s = new Date(startDateInput.value);
            const e = new Date(endDateInput.value);
            if (s && e && e > s) {
                const days = Math.ceil(Math.abs(e - s) / (1000*60*60*24));
                totalDaysSpan.textContent = days;
                totalCostSpan.textContent = 'NPR ' + (days * pricePerDay).toLocaleString();
                costSummary.style.display = 'block';
            } else {
                costSummary.style.display = 'none';
            }
        }
        startDateInput.addEventListener('change', function(){ endDateInput.min = this.value; calculateCost(); });
        endDateInput.addEventListener('change', calculateCost);
    </script>
</body>
</html>