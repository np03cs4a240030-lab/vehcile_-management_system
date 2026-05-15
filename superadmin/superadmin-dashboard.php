<?php
require_once '../config.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../superadmin/superadmin-login.php');
}

$total_users    = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='user'")->fetch_assoc()['c'];
$total_admins   = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='admin'")->fetch_assoc()['c'];
$total_vehicles = $conn->query("SELECT COUNT(*) AS c FROM vehicles")->fetch_assoc()['c'];
$total_bookings = $conn->query("SELECT COUNT(*) AS c FROM bookings")->fetch_assoc()['c'];
$total_revenue  = $conn->query("SELECT COALESCE(SUM(total_price),0) AS t FROM bookings WHERE status NOT IN ('cancelled')")->fetch_assoc()['t'];

// Revenue by month (last 6)
$rByMonth = $conn->query("
    SELECT DATE_FORMAT(created_at,'%b') AS month,
           DATE_FORMAT(created_at,'%Y-%m') AS ym,
           COALESCE(SUM(total_price),0) AS rev
    FROM bookings
    WHERE status NOT IN ('cancelled') AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY ym,month ORDER BY ym ASC
");
$rLabels = []; $rValues = [];
while ($r = $rByMonth->fetch_assoc()) { $rLabels[] = $r['month']; $rValues[] = (float)$r['rev']; }

// Booking status counts
$bStats = $conn->query("SELECT status, COUNT(*) AS c FROM bookings GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$bMap = [];
foreach ($bStats as $b) { $bMap[$b['status']] = $b['c']; }

// Most rented vehicles (top 5)
$topVehicles = $conn->query("
    SELECT v.name, v.type, COUNT(b.id) AS cnt
    FROM bookings b JOIN vehicles v ON b.vehicle_id=v.id
    WHERE b.status NOT IN ('cancelled')
    GROUP BY b.vehicle_id, v.name, v.type
    ORDER BY cnt DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Booking trend last 6 months
$bookingTrend = $conn->query("
    SELECT DATE_FORMAT(created_at,'%b') AS month,
           DATE_FORMAT(created_at,'%Y-%m') AS ym,
           COUNT(*) AS cnt
    FROM bookings
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY ym,month ORDER BY ym ASC
");
$btLabels = []; $btValues = [];
while ($r = $bookingTrend->fetch_assoc()) { $btLabels[] = $r['month']; $btValues[] = (int)$r['cnt']; }

$recent_users    = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$recent_bookings = $conn->query("
    SELECT b.*, u.name AS user_name, v.name AS vehicle_name
    FROM bookings b JOIN users u ON b.user_id=u.id JOIN vehicles v ON b.vehicle_id=v.id
    ORDER BY b.created_at DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - Bhatbhatey Rental</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
        body { background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%); min-height:100vh; display:flex; }

        /* SIDEBAR */
        .sidebar { width:240px; background:rgba(255,255,255,0.03); backdrop-filter:blur(20px); border-right:1px solid rgba(255,255,255,0.07); display:flex; flex-direction:column; min-height:100vh; position:fixed; top:0; left:0; z-index:100; }
        .sidebar-logo { padding:22px 24px; border-bottom:1px solid rgba(255,255,255,0.07); display:flex; align-items:center; gap:12px; }
        .sidebar-logo img { height:36px; }
        .sidebar-logo-text .title { font-size:14px; font-weight:700; color:white; }
        .sidebar-logo-text .sub { font-size:11px; color:#a855f7; background:rgba(168,85,247,0.15); border:1px solid rgba(168,85,247,0.3); border-radius:20px; padding:1px 8px; display:inline-block; margin-top:2px; }
        .sidebar-menu { padding:16px 12px; flex:1; display:flex; flex-direction:column; }
        .sidebar-menu a { display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:10px; color:#94a3b8; text-decoration:none; font-size:14px; font-weight:500; margin-bottom:4px; transition:all 0.2s; }
        .sidebar-menu a i { width:18px; text-align:center; }
        .sidebar-menu a:hover { background:rgba(255,255,255,0.07); color:white; }
        .sidebar-menu a.active { background:linear-gradient(135deg,#9333ea,#7c3aed); color:white; box-shadow:0 4px 15px rgba(147,51,234,0.3); }
        .logout-link { margin-top:auto; }
        .logout-link a { color:#fca5a5 !important; }
        .logout-link a:hover { background:rgba(239,68,68,0.1) !important; }

        /* MAIN */
        .main-content { margin-left:240px; flex:1; padding:28px; }
        .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:28px; }
        .top-bar h1 { font-size:22px; font-weight:700; color:white; }
        .top-bar p { color:#94a3b8; font-size:14px; margin-top:3px; }
        .top-badge { background:rgba(168,85,247,0.15); border:1px solid rgba(168,85,247,0.3); color:#c084fc; padding:8px 16px; border-radius:10px; font-size:13px; font-weight:600; display:flex; align-items:center; gap:7px; }

        /* STATS */
        .stats-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:16px; margin-bottom:28px; }
        .stat-card { background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); border-radius:14px; padding:18px; display:flex; align-items:center; gap:14px; transition:all 0.2s; cursor:default; }
        .stat-card:hover { background:rgba(255,255,255,0.08); transform:translateY(-2px); }
        .stat-icon { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
        .stat-label { color:#94a3b8; font-size:12px; margin-bottom:4px; }
        .stat-value { color:white; font-size:22px; font-weight:800; }

        /* SECTION TITLE */
        .section-title { font-size:15px; font-weight:700; color:white; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
        .section-title i { color:#a855f7; }

        /* CONTENT GRID */
        .analytics-row { display:grid; grid-template-columns:1.6fr 1fr; gap:22px; margin-bottom:22px; }
        .analytics-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:22px; margin-bottom:22px; }
        .data-card { background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); border-radius:14px; overflow:hidden; }
        .data-card-header { padding:16px 20px; border-bottom:1px solid rgba(255,255,255,0.07); color:white; font-size:14px; font-weight:700; display:flex; align-items:center; gap:9px; justify-content:space-between; }
        .data-card-header i { color:#a855f7; }
        .data-card-body { padding:20px; }

        table { width:100%; border-collapse:collapse; }
        thead th { background:rgba(255,255,255,0.03); padding:11px 16px; text-align:left; font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid rgba(255,255,255,0.06); }
        tbody tr { border-bottom:1px solid rgba(255,255,255,0.04); transition:background 0.15s; }
        tbody tr:hover { background:rgba(255,255,255,0.03); }
        tbody tr:last-child { border-bottom:none; }
        td { padding:12px 16px; font-size:14px; color:#e2e8f0; vertical-align:middle; }
        .td-secondary { font-size:12px; color:#64748b; }

        .badge { display:inline-block; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge-success  { background:rgba(16,185,129,0.15); color:#34d399; border:1px solid rgba(16,185,129,0.2); }
        .badge-danger   { background:rgba(239,68,68,0.15); color:#f87171; border:1px solid rgba(239,68,68,0.2); }
        .badge-primary  { background:rgba(59,130,246,0.15); color:#60a5fa; border:1px solid rgba(59,130,246,0.2); }
        .badge-warning  { background:rgba(245,158,11,0.15); color:#fbbf24; border:1px solid rgba(245,158,11,0.2); }
        .badge-purple   { background:rgba(168,85,247,0.15); color:#c084fc; border:1px solid rgba(168,85,247,0.2); }
        .badge-pink     { background:rgba(236,72,153,0.15); color:#f472b6; border:1px solid rgba(236,72,153,0.2); }

        /* Booking stat boxes */
        .bstat-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
        .bstat-item { background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:10px; padding:14px; text-align:center; }
        .bstat-num { font-size:26px; font-weight:900; color:white; }
        .bstat-label { font-size:11px; color:#64748b; margin-top:4px; }

        /* Vstat */
        .vstat-row { display:flex; justify-content:space-between; align-items:center; padding:11px 14px; background:rgba(255,255,255,0.03); border-radius:8px; margin-bottom:8px; border:1px solid rgba(255,255,255,0.05); }
        .vstat-label { display:flex; align-items:center; gap:10px; color:#94a3b8; font-size:14px; }
        .vstat-count { font-weight:700; color:white; }

        @media (max-width:1200px) { .stats-grid { grid-template-columns:repeat(3,1fr); } .analytics-row,.analytics-row-2 { grid-template-columns:1fr; } }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="../assets/images/logo.png" alt="Logo">
        <div class="sidebar-logo-text">
            <div class="title">Bhatbhatey</div>
            <div class="sub">Super Admin</div>
        </div>
    </div>
    <nav class="sidebar-menu">
        <a href="superadmin-dashboard.php" class="active"><i class="fas fa-gauge-high"></i> Dashboard</a>
        <a href="superadmin-users.php"><i class="fas fa-users"></i> Users</a>
        <a href="superadmin-admins.php"><i class="fas fa-user-shield"></i> Admins</a>
        <a href="superadmin-vehicles.php"><i class="fas fa-car"></i> Vehicles</a>
        <a href="superadmin-bookings.php"><i class="fas fa-calendar-days"></i> Bookings</a>
        <a href="superadmin-settings.php"><i class="fas fa-gear"></i> Settings</a>
        <div class="logout-link">
            <a href="../logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </div>
    </nav>
</aside>

<main class="main-content">

    <div class="top-bar">
        <div>
            <h1>Super Admin Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Super Admin'); ?></p>
        </div>
        <div class="top-badge"><i class="fas fa-user-shield"></i> Super Admin Access</div>
    </div>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(59,130,246,0.15); color:#60a5fa;"><i class="fas fa-users"></i></div>
            <div><div class="stat-label">Total Users</div><div class="stat-value"><?php echo $total_users; ?></div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(168,85,247,0.15); color:#c084fc;"><i class="fas fa-user-shield"></i></div>
            <div><div class="stat-label">Admins</div><div class="stat-value"><?php echo $total_admins; ?></div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(249,115,22,0.15); color:#fb923c;"><i class="fas fa-car"></i></div>
            <div><div class="stat-label">Vehicles</div><div class="stat-value"><?php echo $total_vehicles; ?></div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(16,185,129,0.15); color:#34d399;"><i class="fas fa-calendar-check"></i></div>
            <div><div class="stat-label">Bookings</div><div class="stat-value"><?php echo $total_bookings; ?></div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(245,158,11,0.15); color:#fbbf24;"><i class="fas fa-wallet"></i></div>
            <div><div class="stat-label">Revenue</div><div class="stat-value" style="font-size:14px;">NPR <?php echo number_format($total_revenue); ?></div></div>
        </div>
    </div>

    <!-- REVENUE ANALYTICS -->
    <div class="section-title"><i class="fas fa-chart-line"></i> Revenue Analytics</div>
    <div class="analytics-row" style="margin-bottom:22px;">
        <div class="data-card">
            <div class="data-card-header">
                <span><i class="fas fa-chart-bar"></i> Monthly Revenue (Last 6 Months)</span>
                <span style="font-size:11px; color:#64748b; font-weight:400;">Auto-updates on refresh</span>
            </div>
            <div class="data-card-body">
                <canvas id="revenueChart" height="110"></canvas>
            </div>
        </div>
        <div class="data-card">
            <div class="data-card-header"><i class="fas fa-wallet"></i> Revenue Summary</div>
            <div class="data-card-body">
                <div style="text-align:center; padding:12px 0 20px;">
                    <div style="font-size:11px; color:#64748b; margin-bottom:6px;">Total Revenue</div>
                    <div style="font-size:28px; font-weight:900; color:#fbbf24;">NPR <?php echo number_format($total_revenue); ?></div>
                </div>
                <div class="vstat-row">
                    <div class="vstat-label"><i class="fas fa-circle-check" style="color:#34d399;"></i> Approved</div>
                    <div class="vstat-count"><?php echo ($bMap['approved'] ?? 0) + ($bMap['confirmed'] ?? 0); ?></div>
                </div>
                <div class="vstat-row">
                    <div class="vstat-label"><i class="fas fa-flag-checkered" style="color:#60a5fa;"></i> Completed</div>
                    <div class="vstat-count"><?php echo $bMap['completed'] ?? 0; ?></div>
                </div>
                <div class="vstat-row" style="margin-bottom:0;">
                    <div class="vstat-label"><i class="fas fa-ban" style="color:#f87171;"></i> Cancelled</div>
                    <div class="vstat-count"><?php echo $bMap['cancelled'] ?? 0; ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- VEHICLE ANALYTICS -->
    <div class="section-title"><i class="fas fa-car"></i> Vehicle Analytics</div>
    <div class="analytics-row" style="margin-bottom:22px;">
        <div class="data-card">
            <div class="data-card-header"><i class="fas fa-trophy"></i> Most Rented Vehicles</div>
            <div style="padding:0;">
                <table>
                    <thead><tr><th>#</th><th>Vehicle</th><th>Type</th><th>Rentals</th><th>Demand</th></tr></thead>
                    <tbody>
                        <?php if (!empty($topVehicles)):
                            $maxR = $topVehicles[0]['cnt'];
                            foreach ($topVehicles as $i => $tv):
                                $pct = $maxR > 0 ? round(($tv['cnt'] / $maxR) * 100) : 0;
                        ?>
                        <tr>
                            <td style="color:#64748b; font-weight:700;"><?php echo $i+1; ?></td>
                            <td style="font-weight:600;"><?php echo htmlspecialchars($tv['name']); ?></td>
                            <td class="td-secondary"><?php echo $tv['type']; ?></td>
                            <td><span class="badge badge-success"><?php echo $tv['cnt']; ?></span></td>
                            <td style="min-width:100px;">
                                <div style="background:rgba(255,255,255,0.07); border-radius:20px; height:7px; overflow:hidden;">
                                    <div style="background:#a855f7; height:100%; width:<?php echo $pct; ?>%; border-radius:20px;"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="5" style="text-align:center; color:#64748b; padding:24px;">No rental data yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="data-card">
            <div class="data-card-header"><i class="fas fa-chart-pie"></i> Booking Breakdown</div>
            <div class="data-card-body">
                <div class="bstat-grid" style="margin-bottom:16px;">
                    <div class="bstat-item"><div class="bstat-num" style="color:#fbbf24;"><?php echo $bMap['pending'] ?? 0; ?></div><div class="bstat-label"><i class="fas fa-clock"></i> Pending</div></div>
                    <div class="bstat-item"><div class="bstat-num" style="color:#34d399;"><?php echo ($bMap['approved'] ?? 0) + ($bMap['confirmed'] ?? 0); ?></div><div class="bstat-label"><i class="fas fa-check"></i> Approved</div></div>
                    <div class="bstat-item"><div class="bstat-num" style="color:#60a5fa;"><?php echo $bMap['completed'] ?? 0; ?></div><div class="bstat-label"><i class="fas fa-flag-checkered"></i> Done</div></div>
                </div>
                <canvas id="bChart" height="130"></canvas>
            </div>
        </div>
    </div>

    <!-- RECENT DATA -->
    <div class="section-title"><i class="fas fa-chart-area"></i> Booking Trend (Last 6 Months)</div>
    <div class="data-card" style="margin-bottom:22px;">
        <div class="data-card-header"><i class="fas fa-chart-line"></i> Monthly Bookings</div>
        <div class="data-card-body">
            <canvas id="trendChart" height="70"></canvas>
        </div>
    </div>

    <!-- RECENT DATA -->
    <div class="section-title"><i class="fas fa-clock"></i> Recent Activity</div>
    <div class="analytics-row-2">

        <!-- RECENT USERS -->
        <div class="data-card">
            <div class="data-card-header"><i class="fas fa-users"></i> Recent Users</div>
            <table>
                <thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Joined</th></tr></thead>
                <tbody>
                    <?php while ($u = $recent_users->fetch_assoc()):
                        $roleBadge = ['super_admin'=>'badge-purple','admin'=>'badge-primary','user'=>'badge-success'];
                        $rc = $roleBadge[$u['role']] ?? 'badge-primary';
                    ?>
                    <tr>
                        <td>
                            <div><?php echo htmlspecialchars($u['name']); ?></div>
                            <div class="td-secondary"><?php echo htmlspecialchars($u['email']); ?></div>
                        </td>
                        <td><span class="badge <?php echo $rc; ?>"><?php echo ucfirst(str_replace('_',' ',$u['role'])); ?></span></td>
                        <td><span class="badge <?php echo $u['status']==='active'?'badge-success':'badge-danger'; ?>"><?php echo ucfirst($u['status']); ?></span></td>
                        <td class="td-secondary"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- RECENT BOOKINGS -->
        <div class="data-card">
            <div class="data-card-header"><i class="fas fa-calendar-days"></i> Recent Bookings</div>
            <table>
                <thead><tr><th>Customer</th><th>Vehicle</th><th>Status</th><th>Total</th></tr></thead>
                <tbody>
                    <?php while ($b = $recent_bookings->fetch_assoc()):
                        $sc = ['approved'=>'badge-success','confirmed'=>'badge-success','pending'=>'badge-warning','cancelled'=>'badge-danger','completed'=>'badge-primary','ongoing'=>'badge-pink'];
                        $bc = $sc[$b['status']] ?? 'badge-primary';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($b['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($b['vehicle_name']); ?></td>
                        <td><span class="badge <?php echo $bc; ?>"><?php echo ucfirst($b['status']); ?></span></td>
                        <td style="color:#fbbf24; font-weight:700;">NPR <?php echo number_format($b['total_price'],2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>
</main>

<script>
// Booking Trend Chart
const btLabels = <?php echo json_encode($btLabels); ?>;
const btValues = <?php echo json_encode($btValues); ?>;
if (btLabels.length > 0) {
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: btLabels,
            datasets: [{
                label: 'Bookings',
                data: btValues,
                borderColor: '#a855f7',
                backgroundColor: 'rgba(168,85,247,0.1)',
                borderWidth: 2,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#a855f7',
                fill: true,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: '#64748b' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                y: { beginAtZero: true, ticks: { color: '#64748b', stepSize: 1 }, grid: { color: 'rgba(255,255,255,0.05)' } }
            }
        }
    });
}

// Revenue Chart
const rLabels = <?php echo json_encode($rLabels); ?>;
const rValues = <?php echo json_encode($rValues); ?>;
if (rLabels.length > 0) {
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: rLabels,
            datasets: [{
                label: 'Revenue (NPR)',
                data: rValues,
                backgroundColor: 'rgba(168,85,247,0.2)',
                borderColor: '#a855f7',
                borderWidth: 2,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: '#64748b' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                y: { beginAtZero: true, ticks: { color: '#64748b', callback: v => 'NPR ' + v.toLocaleString() }, grid: { color: 'rgba(255,255,255,0.05)' } }
            }
        }
    });
}

// Booking Doughnut
new Chart(document.getElementById('bChart'), {
    type: 'doughnut',
    data: {
        labels: ['Pending','Approved','Ongoing','Completed','Cancelled'],
        datasets: [{
            data: [
                <?php echo $bMap['pending']   ?? 0; ?>,
                <?php echo ($bMap['approved'] ?? 0) + ($bMap['confirmed'] ?? 0); ?>,
                <?php echo $bMap['ongoing']   ?? 0; ?>,
                <?php echo $bMap['completed'] ?? 0; ?>,
                <?php echo $bMap['cancelled'] ?? 0; ?>
            ],
            backgroundColor: ['rgba(245,158,11,0.3)','rgba(16,185,129,0.3)','rgba(236,72,153,0.3)','rgba(59,130,246,0.3)','rgba(239,68,68,0.3)'],
            borderColor: ['#fbbf24','#34d399','#f472b6','#60a5fa','#f87171'],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8', font: { size: 11 } } } },
        cutout: '60%'
    }
});
</script>
</body>
</html>