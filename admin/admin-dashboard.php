<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../admin/admin-login.php');
}

$currentUser = getCurrentUser();

// --- Stats ---
$totalVehicles  = $conn->query("SELECT COUNT(*) AS c FROM vehicles")->fetch_assoc()['c'];
$totalBookings  = $conn->query("SELECT COUNT(*) AS c FROM bookings")->fetch_assoc()['c'];
$totalRevenue   = $conn->query("SELECT COALESCE(SUM(total_price),0) AS t FROM bookings WHERE status NOT IN ('cancelled')")->fetch_assoc()['t'];
$totalUsers     = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='user'")->fetch_assoc()['c'];
$activeBookings = $conn->query("SELECT COUNT(*) AS c FROM bookings WHERE status IN ('approved','ongoing')")->fetch_assoc()['c'];

// Revenue by month (last 6 months)
$revenueByMonth = $conn->query("
    SELECT DATE_FORMAT(created_at, '%b') AS month,
           DATE_FORMAT(created_at, '%Y-%m') AS ym,
           COALESCE(SUM(total_price),0) AS revenue
    FROM bookings
    WHERE status NOT IN ('cancelled')
      AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY ym, month
    ORDER BY ym ASC
");
$revenueLabels = []; $revenueValues = [];
while ($r = $revenueByMonth->fetch_assoc()) {
    $revenueLabels[] = $r['month'];
    $revenueValues[] = (float)$r['revenue'];
}

// Most rented vehicles (top 5)
$topVehicles = $conn->query("
    SELECT v.name, v.type, COUNT(b.id) AS rental_count
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.status NOT IN ('cancelled')
    GROUP BY b.vehicle_id, v.name, v.type
    ORDER BY rental_count DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Booking stats per status
$bookingStats = $conn->query("SELECT status, COUNT(*) AS count FROM bookings GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$bStatMap = [];
foreach ($bookingStats as $bs) { $bStatMap[$bs['status']] = $bs['count']; }

// Date-filtered bookings count
$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo   = $_GET['to']   ?? date('Y-m-d');
$stmtFiltered = $conn->prepare("SELECT COUNT(*) AS c FROM bookings WHERE DATE(created_at) BETWEEN ? AND ?");
$stmtFiltered->bind_param("ss", $dateFrom, $dateTo);
$stmtFiltered->execute();
$filteredCount = $stmtFiltered->get_result()->fetch_assoc()['c'];

// Date-filtered revenue
$stmtRevFiltered = $conn->prepare("SELECT COALESCE(SUM(total_price),0) AS t FROM bookings WHERE DATE(created_at) BETWEEN ? AND ? AND status NOT IN ('cancelled')");
$stmtRevFiltered->bind_param("ss", $dateFrom, $dateTo);
$stmtRevFiltered->execute();
$filteredRevenue = $stmtRevFiltered->get_result()->fetch_assoc()['t'];

// Vehicle type counts
$vehicleStatsResult = $conn->query("SELECT type, COUNT(*) AS count FROM vehicles GROUP BY type");
$vehicleCounts = ['Car' => 0, 'Bike' => 0, 'Scooter' => 0];
while ($row = $vehicleStatsResult->fetch_assoc()) { $vehicleCounts[$row['type']] = $row['count']; }

// Recent bookings
$recentBookings = $conn->query("
    SELECT b.*, u.name AS user_name, v.name AS vehicle_name
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
    ORDER BY b.created_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Vehicle rental trend - last 6 months top vehicle
$vehicleRentalTrend = $conn->query("
    SELECT DATE_FORMAT(b.created_at, '%b') AS month,
           DATE_FORMAT(b.created_at, '%Y-%m') AS ym,
           COUNT(b.id) AS count
    FROM bookings b
    WHERE b.status NOT IN ('cancelled')
      AND b.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY ym, month
    ORDER BY ym ASC
");
$trendLabels = []; $trendValues = [];
while ($r = $vehicleRentalTrend->fetch_assoc()) {
    $trendLabels[] = $r['month'];
    $trendValues[] = (int)$r['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bhatbhatey Rental</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
        body { background:#f1f5f9; display:flex; min-height:100vh; }

        /* SIDEBAR */
        .sidebar { width:240px; background:#1e293b; color:white; display:flex; flex-direction:column; min-height:100vh; position:fixed; top:0; left:0; z-index:100; }
        .sidebar-logo { padding:20px 24px; border-bottom:1px solid rgba(255,255,255,0.08); display:flex; align-items:center; gap:12px; }
        .sidebar-logo img { height:36px; }
        .sidebar-logo span { font-size:13px; color:#94a3b8; font-weight:600; }
        .sidebar-menu { padding:16px 12px; flex:1; display:flex; flex-direction:column; }
        .sidebar-menu a { display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:8px; color:#94a3b8; text-decoration:none; font-size:14px; font-weight:500; margin-bottom:4px; transition:all 0.2s; }
        .sidebar-menu a i { width:18px; text-align:center; font-size:15px; }
        .sidebar-menu a:hover { background:rgba(255,255,255,0.07); color:white; }
        .sidebar-menu a.active { background:#f97316; color:white; }
        .logout-link { margin-top:auto; }
        .logout-link a { color:#fca5a5 !important; }
        .logout-link a:hover { background:rgba(239,68,68,0.15) !important; }

        /* MAIN */
        .main-content { margin-left:240px; flex:1; padding:28px; }
        .page-header { margin-bottom:28px; }
        .page-header h1 { font-size:22px; font-weight:700; color:#1e293b; }
        .page-header p { color:#64748b; font-size:14px; margin-top:3px; }

        /* STATS */
        .stats-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:16px; margin-bottom:28px; }
        .stat-card { background:white; border-radius:14px; padding:18px 16px; display:flex; align-items:center; gap:14px; box-shadow:0 1px 4px rgba(0,0,0,0.07); border-left:4px solid #e2e8f0; }
        .stat-icon { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:19px; flex-shrink:0; }
        .stat-card h3 { font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px; }
        .stat-value { font-size:22px; font-weight:800; color:#1e293b; line-height:1; }

        /* SECTION HEADER */
        .section-title { font-size:16px; font-weight:700; color:#1e293b; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
        .section-title i { color:#f97316; }

        /* CARDS */
        .card { background:white; border-radius:14px; box-shadow:0 1px 4px rgba(0,0,0,0.07); overflow:hidden; margin-bottom:24px; }
        .card-header { padding:16px 20px; border-bottom:1px solid #f1f5f9; font-size:14px; font-weight:700; color:#1e293b; display:flex; align-items:center; gap:8px; justify-content:space-between; }
        .card-header i { color:#f97316; }
        .card-body { padding:20px; }

        /* ANALYTICS GRIDS */
        .analytics-row { display:grid; grid-template-columns:1.6fr 1fr; gap:24px; margin-bottom:24px; }
        .analytics-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:24px; margin-bottom:24px; }
        .analytics-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:24px; }

        /* DATE FILTER */
        .filter-form { display:flex; align-items:flex-start; gap:12px; flex-direction:column; }
        .filter-form label { font-size:12px; color:#64748b; font-weight:600; display:block; margin-bottom:4px; }
        .filter-form input[type="date"] { padding:8px 10px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px; outline:none; width:100%; }
        .filter-form input[type="date"]:focus { border-color:#f97316; }
        .btn-filter { padding:8px 16px; background:#f97316; color:white; border:none; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px; }
        .btn-filter:hover { background:#ea6c10; }

        /* BADGES */
        .badge { display:inline-block; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge-pending   { background:#fef9c3; color:#ca8a04; }
        .badge-confirmed { background:#dcfce7; color:#16a34a; }
        .badge-approved  { background:#dcfce7; color:#16a34a; }
        .badge-completed { background:#dbeafe; color:#2563eb; }
        .badge-cancelled { background:#fee2e2; color:#dc2626; }
        .badge-ongoing   { background:#fce7f3; color:#be185d; }

        /* TABLE */
        table { width:100%; border-collapse:collapse; }
        thead th { background:#f8fafc; padding:10px 14px; text-align:left; font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid #e2e8f0; }
        tbody tr { border-bottom:1px solid #f1f5f9; }
        tbody tr:last-child { border-bottom:none; }
        tbody tr:hover { background:#fafbfc; }
        td { padding:11px 14px; font-size:14px; color:#1e293b; }

        /* BOOKING ITEM */
        .booking-item { display:flex; justify-content:space-between; align-items:center; padding:12px; background:#f8fafc; border-radius:8px; margin-bottom:8px; }
        .booking-item:last-child { margin-bottom:0; }
        .booking-vehicle { font-weight:600; font-size:14px; color:#1e293b; }
        .booking-user { font-size:12px; color:#64748b; margin-top:2px; }
        .booking-price { font-weight:700; color:#f97316; font-size:14px; }

        /* BOOKING STATUS GRID */
        .bstat-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:10px; margin-bottom:20px; }
        .bstat-item { background:#f8fafc; border-radius:10px; padding:14px; text-align:center; }
        .bstat-num { font-size:24px; font-weight:800; color:#1e293b; }
        .bstat-label { font-size:11px; color:#64748b; margin-top:4px; text-transform:uppercase; letter-spacing:0.3px; }

        /* VEHICLE STAT ROW */
        .vstat-row { display:flex; justify-content:space-between; align-items:center; padding:10px 12px; background:#f8fafc; border-radius:8px; margin-bottom:8px; }
        .vstat-label { display:flex; align-items:center; gap:10px; color:#475569; font-size:14px; }
        .vstat-count { font-weight:700; color:#1e293b; }

        /* FILTERED COUNT BOX */
        .filtered-box { text-align:center; padding:20px 0 0; }
        .filtered-num { font-size:42px; font-weight:900; color:#f97316; line-height:1; }
        .filtered-rev { font-size:15px; font-weight:700; color:#10b981; margin-top:6px; }
        .filtered-desc { color:#64748b; font-size:12px; margin-top:4px; }

        @media (max-width:1200px) {
            .stats-grid { grid-template-columns:repeat(3,1fr); }
            .analytics-row,.analytics-row-3,.analytics-row-2 { grid-template-columns:1fr; }
        }
        @media (max-width:768px) {
            .stats-grid { grid-template-columns:repeat(2,1fr); }
            .main-content { margin-left:0; padding:16px; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="../assets/images/logo.png" alt="Logo">
        <span>Admin Panel</span>
    </div>
    <nav class="sidebar-menu">
        <a href="admin-dashboard.php" class="active"><i class="fas fa-gauge-high"></i> Dashboard</a>
        <a href="admin-vehicles.php"><i class="fas fa-car"></i> Vehicles</a>
        <a href="admin-bookings.php"><i class="fas fa-calendar-days"></i> Bookings</a>
        <a href="admin-users.php"><i class="fas fa-users"></i> Users</a>
        <div class="logout-link">
            <a href="../logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </div>
    </nav>
</aside>

<main class="main-content">

    <div class="page-header">
        <h1>Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($currentUser['name']); ?></p>
    </div>

    <!-- STATS ROW -->
    <div class="stats-grid">
        <div class="stat-card" style="border-left-color:#3b82f6;">
            <div class="stat-icon" style="background:#eff6ff; color:#3b82f6;"><i class="fas fa-car"></i></div>
            <div><h3>Vehicles</h3><div class="stat-value"><?php echo $totalVehicles; ?></div></div>
        </div>
        <div class="stat-card" style="border-left-color:#10b981;">
            <div class="stat-icon" style="background:#f0fdf4; color:#10b981;"><i class="fas fa-calendar-check"></i></div>
            <div><h3>Total Bookings</h3><div class="stat-value"><?php echo $totalBookings; ?></div></div>
        </div>
        <div class="stat-card" style="border-left-color:#f97316;">
            <div class="stat-icon" style="background:#fff7ed; color:#f97316;"><i class="fas fa-key"></i></div>
            <div><h3>Active Rentals</h3><div class="stat-value"><?php echo $activeBookings; ?></div></div>
        </div>
        <div class="stat-card" style="border-left-color:#8b5cf6;">
            <div class="stat-icon" style="background:#f5f3ff; color:#8b5cf6;"><i class="fas fa-wallet"></i></div>
            <div><h3>Revenue</h3><div class="stat-value" style="font-size:15px;">NPR <?php echo number_format($totalRevenue); ?></div></div>
        </div>
        <div class="stat-card" style="border-left-color:#06b6d4;">
            <div class="stat-icon" style="background:#ecfeff; color:#06b6d4;"><i class="fas fa-users"></i></div>
            <div><h3>Users</h3><div class="stat-value"><?php echo $totalUsers; ?></div></div>
        </div>
    </div>

    <!-- REVENUE ANALYTICS -->
    <div class="section-title"><i class="fas fa-chart-line"></i> Revenue Analytics</div>
    <div class="analytics-row">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-chart-bar"></i> Monthly Revenue (Last 6 Months)</span>
                <span style="font-size:12px; color:#64748b; font-weight:400;">Auto-updates on refresh</span>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="90"></canvas>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><i class="fas fa-wallet"></i> Revenue Summary</div>
            <div class="card-body">
                <div style="text-align:center; padding:14px 0 20px;">
                    <div style="font-size:12px; color:#64748b; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">Total Revenue</div>
                    <div style="font-size:32px; font-weight:900; color:#f97316;">NPR <?php echo number_format($totalRevenue); ?></div>
                    <div style="font-size:12px; color:#94a3b8; margin-top:4px;">Non-cancelled bookings only</div>
                </div>
                <div class="vstat-row">
                    <div class="vstat-label"><i class="fas fa-circle-check" style="color:#16a34a;"></i> Approved</div>
                    <div class="vstat-count"><?php echo $bStatMap['approved'] ?? 0; ?> bookings</div>
                </div>
                <div class="vstat-row">
                    <div class="vstat-label"><i class="fas fa-flag-checkered" style="color:#2563eb;"></i> Completed</div>
                    <div class="vstat-count"><?php echo $bStatMap['completed'] ?? 0; ?> bookings</div>
                </div>
                <div class="vstat-row" style="margin-bottom:0;">
                    <div class="vstat-label"><i class="fas fa-ban" style="color:#dc2626;"></i> Cancelled (excluded)</div>
                    <div class="vstat-count"><?php echo $bStatMap['cancelled'] ?? 0; ?> bookings</div>
                </div>
            </div>
        </div>
    </div>

    <!-- VEHICLE ANALYTICS -->
    <div class="section-title"><i class="fas fa-car"></i> Vehicle Analytics</div>
    <div class="analytics-row">
        <div class="card">
            <div class="card-header"><i class="fas fa-trophy"></i> Most Rented Vehicles</div>
            <div class="card-body" style="padding:0;">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Vehicle</th>
                            <th>Type</th>
                            <th>Rentals</th>
                            <th>Demand</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($topVehicles)):
                            $maxRent = $topVehicles[0]['rental_count'];
                            foreach ($topVehicles as $i => $tv):
                                $pct = $maxRent > 0 ? round(($tv['rental_count'] / $maxRent) * 100) : 0;
                        ?>
                        <tr>
                            <td style="color:#94a3b8; font-weight:700;"><?php echo $i + 1; ?></td>
                            <td style="font-weight:600;"><?php echo htmlspecialchars($tv['name']); ?></td>
                            <td>
                                <?php $ic = ($tv['type'] === 'Car') ? 'fa-car' : 'fa-motorcycle'; ?>
                                <i class="fas <?php echo $ic; ?>" style="color:#94a3b8;"></i>
                                <?php echo $tv['type']; ?>
                            </td>
                            <td><span class="badge badge-confirmed"><?php echo $tv['rental_count']; ?> rentals</span></td>
                            <td style="min-width:120px;">
                                <div style="background:#f1f5f9; border-radius:20px; height:8px; overflow:hidden;">
                                    <div style="background:#f97316; height:100%; width:<?php echo $pct; ?>%; border-radius:20px;"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="5" style="text-align:center; color:#94a3b8; padding:24px;">No rental data yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><i class="fas fa-chart-pie"></i> Vehicle Overview</div>
            <div class="card-body">
                <canvas id="fleetChart" height="140"></canvas>
                <div style="margin-top:16px;">
                    <div class="vstat-row">
                        <div class="vstat-label"><i class="fas fa-car" style="color:#3b82f6;"></i> Cars</div>
                        <div class="vstat-count"><?php echo $vehicleCounts['Car']; ?></div>
                    </div>
                    <div class="vstat-row">
                        <div class="vstat-label"><i class="fas fa-motorcycle" style="color:#f97316;"></i> Bikes</div>
                        <div class="vstat-count"><?php echo $vehicleCounts['Bike']; ?></div>
                    </div>
                    <div class="vstat-row" style="margin-bottom:0;">
                        <div class="vstat-label"><i class="fas fa-motorcycle" style="color:#8b5cf6;"></i> Scooters</div>
                        <div class="vstat-count"><?php echo $vehicleCounts['Scooter']; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BOOKING ANALYTICS -->
    <div class="section-title"><i class="fas fa-calendar-days"></i> Booking Analytics</div>
    <div class="analytics-row-3">

        <!-- Booking Status Breakdown -->
        <div class="card">
            <div class="card-header"><i class="fas fa-chart-donut"></i> Status Breakdown</div>
            <div class="card-body">
                <div class="bstat-grid">
                    <div class="bstat-item">
                        <div class="bstat-num" style="color:#ca8a04;"><?php echo $bStatMap['pending'] ?? 0; ?></div>
                        <div class="bstat-label"><i class="fas fa-clock"></i> Pending</div>
                    </div>
                    <div class="bstat-item">
                        <div class="bstat-num" style="color:#16a34a;"><?php echo $bStatMap['approved'] ?? 0; ?></div>
                        <div class="bstat-label"><i class="fas fa-circle-check"></i> Approved</div>
                    </div>
                    <div class="bstat-item">
                        <div class="bstat-num" style="color:#be185d;"><?php echo $bStatMap['ongoing'] ?? 0; ?></div>
                        <div class="bstat-label"><i class="fas fa-car-side"></i> Ongoing</div>
                    </div>
                    <div class="bstat-item">
                        <div class="bstat-num" style="color:#2563eb;"><?php echo $bStatMap['completed'] ?? 0; ?></div>
                        <div class="bstat-label"><i class="fas fa-flag-checkered"></i> Completed</div>
                    </div>
                </div>
                <canvas id="bookingChart" height="120"></canvas>
            </div>
        </div>

        <!-- Booking Trend Chart -->
        <div class="card">
            <div class="card-header"><i class="fas fa-chart-area"></i> Booking Trend</div>
            <div class="card-body">
                <canvas id="trendChart" height="180"></canvas>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="card">
            <div class="card-header"><i class="fas fa-filter"></i> Date-Range Filter</div>
            <div class="card-body">
                <form method="GET" class="filter-form">
                    <div style="width:100%;">
                        <label>From</label>
                        <input type="date" name="from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                    </div>
                    <div style="width:100%;">
                        <label>To</label>
                        <input type="date" name="to" value="<?php echo htmlspecialchars($dateTo); ?>">
                    </div>
                    <button type="submit" class="btn-filter"><i class="fas fa-magnifying-glass"></i> Apply Filter</button>
                </form>
                <div class="filtered-box">
                    <div class="filtered-num"><?php echo $filteredCount; ?></div>
                    <div class="filtered-desc">Bookings in range</div>
                    <div class="filtered-rev">NPR <?php echo number_format($filteredRevenue); ?></div>
                    <div class="filtered-desc">
                        Revenue from <strong><?php echo date('M d', strtotime($dateFrom)); ?></strong>
                        to <strong><?php echo date('M d, Y', strtotime($dateTo)); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RECENT BOOKINGS -->
    <div class="section-title"><i class="fas fa-clock"></i> Recent Bookings</div>
    <div class="card">
        <div class="card-body">
            <?php if (count($recentBookings) > 0): ?>
                <?php foreach ($recentBookings as $b):
                    $bc = ['approved'=>'badge-approved','pending'=>'badge-pending','cancelled'=>'badge-cancelled','completed'=>'badge-completed','ongoing'=>'badge-ongoing'][$b['status']] ?? 'badge-pending';
                ?>
                <div class="booking-item">
                    <div>
                        <div class="booking-vehicle"><?php echo htmlspecialchars($b['vehicle_name']); ?></div>
                        <div class="booking-user"><i class="fas fa-user" style="font-size:11px;"></i> <?php echo htmlspecialchars($b['user_name']); ?></div>
                    </div>
                    <div style="text-align:right;">
                        <div class="booking-price">NPR <?php echo number_format($b['total_price']); ?></div>
                        <span class="badge <?php echo $bc; ?>"><?php echo ucfirst($b['status']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; color:#94a3b8; padding:32px 0;"><i class="fas fa-calendar-xmark" style="font-size:32px; display:block; margin-bottom:8px;"></i> No bookings yet</p>
            <?php endif; ?>
        </div>
    </div>

</main>

<script>
// Revenue Bar Chart
const revenueLabels = <?php echo json_encode($revenueLabels); ?>;
const revenueValues = <?php echo json_encode($revenueValues); ?>;

if (revenueLabels.length > 0) {
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: revenueLabels,
            datasets: [{
                label: 'Revenue (NPR)',
                data: revenueValues,
                backgroundColor: 'rgba(249,115,22,0.15)',
                borderColor: '#f97316',
                borderWidth: 2,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => 'NPR ' + v.toLocaleString() } }
            }
        }
    });
} else {
    document.getElementById('revenueChart').closest('.card-body').innerHTML =
        '<p style="text-align:center;color:#94a3b8;padding:32px 0;"><i class="fas fa-chart-bar" style="font-size:32px;display:block;margin-bottom:8px;"></i>No revenue data yet</p>';
}

// Fleet Doughnut Chart
new Chart(document.getElementById('fleetChart'), {
    type: 'doughnut',
    data: {
        labels: ['Cars', 'Bikes', 'Scooters'],
        datasets: [{
            data: [<?php echo $vehicleCounts['Car']; ?>, <?php echo $vehicleCounts['Bike']; ?>, <?php echo $vehicleCounts['Scooter']; ?>],
            backgroundColor: ['#eff6ff','#fff7ed','#f5f3ff'],
            borderColor: ['#3b82f6','#f97316','#8b5cf6'],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } },
        cutout: '60%'
    }
});

// Booking Doughnut Chart
const bLabels = ['Pending','Approved','Ongoing','Completed','Cancelled'];
const bData   = [
    <?php echo $bStatMap['pending']   ?? 0; ?>,
    <?php echo $bStatMap['approved']  ?? 0; ?>,
    <?php echo $bStatMap['ongoing']   ?? 0; ?>,
    <?php echo $bStatMap['completed'] ?? 0; ?>,
    <?php echo $bStatMap['cancelled'] ?? 0; ?>
];
new Chart(document.getElementById('bookingChart'), {
    type: 'doughnut',
    data: {
        labels: bLabels,
        datasets: [{
            data: bData,
            backgroundColor: ['#fef9c3','#dcfce7','#fce7f3','#dbeafe','#fee2e2'],
            borderColor:     ['#ca8a04','#16a34a','#be185d','#2563eb','#dc2626'],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } },
        cutout: '60%'
    }
});

// Booking Trend Line Chart
const trendLabels = <?php echo json_encode($trendLabels); ?>;
const trendValues = <?php echo json_encode($trendValues); ?>;
if (trendLabels.length > 0) {
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [{
                label: 'Bookings',
                data: trendValues,
                borderColor: '#f97316',
                backgroundColor: 'rgba(249,115,22,0.08)',
                borderWidth: 2,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#f97316',
                fill: true,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
} else {
    document.getElementById('trendChart').closest('.card-body').innerHTML =
        '<p style="text-align:center;color:#94a3b8;padding:32px 0;">No trend data yet</p>';
}
</script>
</body>
</html>
