<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../admin/admin-login.php');
}

$currentUser = getCurrentUser();

$result = $conn->query("SELECT COUNT(*) as total FROM vehicles");
$totalVehicles = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM bookings");
$totalBookings = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'confirmed'");
$activeBookings = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT SUM(total_price) as total FROM bookings WHERE status != 'cancelled'");
$totalRevenue = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$totalUsers = $result->fetch_assoc()['total'];

$recentBookings = $conn->query("
    SELECT b.*, u.name AS user_name, v.name AS vehicle_name
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
    ORDER BY b.created_at DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$vehicleStatsResult = $conn->query("SELECT type, COUNT(*) as count FROM vehicles GROUP BY type");
$vehicleCounts = ['Car' => 0, 'Bike' => 0, 'Scooter' => 0];
while ($row = $vehicleStatsResult->fetch_assoc()) {
    $vehicleCounts[$row['type']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bhatbhatey Rental</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #f1f5f9; display: flex; min-height: 100vh; }

        /* SIDEBAR */
        .sidebar {
            width: 240px; background: #1e293b; color: white;
            display: flex; flex-direction: column;
            min-height: 100vh; position: fixed; top: 0; left: 0;
        }
        .sidebar-logo {
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex; align-items: center; gap: 12px;
        }
        .sidebar-logo img { height: 36px; }
        .sidebar-logo span { font-size: 13px; color: #94a3b8; font-weight: 600; }
        .sidebar-menu { 
            padding: 16px 12px; 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
        }
        .sidebar-menu a {
            display: flex; align-items: center; gap: 12px;
            padding: 11px 14px; border-radius: 8px;
            color: #94a3b8; text-decoration: none;
            font-size: 14px; font-weight: 500;
            margin-bottom: 4px; transition: all 0.2s;
        }
        .sidebar-menu a i { width: 18px; text-align: center; font-size: 15px; }
        .sidebar-menu a:hover { background: rgba(255,255,255,0.07); color: white; }
        .sidebar-menu a.active { background: #f97316; color: white; }
        .sidebar-menu .logout-link { margin-top: auto; }
        .sidebar-menu .logout-link a { color: #fca5a5; }
        .sidebar-menu .logout-link a:hover { background: rgba(239,68,68,0.15); color: #fca5a5; }

        /* MAIN */
        .main-content { margin-left: 240px; flex: 1; padding: 28px; }
        .page-header { margin-bottom: 28px; }
        .page-header h1 { font-size: 22px; font-weight: 700; color: #1e293b; }
        .page-header p { color: #64748b; font-size: 14px; margin-top: 3px; }

        /* STATS */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 28px; }
        .stat-card {
            background: white; border-radius: 14px;
            padding: 20px; display: flex; align-items: center; gap: 16px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.07);
            border-left: 4px solid #e2e8f0;
        }
        .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }
        .stat-card h3 { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .stat-value { font-size: 24px; font-weight: 800; color: #1e293b; }

        /* GRID */
        .content-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .card {
            background: white; border-radius: 14px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.07); overflow: hidden;
        }
        .card-header {
            padding: 18px 20px; border-bottom: 1px solid #f1f5f9;
            font-size: 15px; font-weight: 700; color: #1e293b;
        }
        .card-body { padding: 20px; }

        /* BOOKING ITEM */
        .booking-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 12px; background: #f8fafc; border-radius: 8px; margin-bottom: 8px;
        }
        .booking-item:last-child { margin-bottom: 0; }
        .booking-vehicle { font-weight: 600; font-size: 14px; color: #1e293b; }
        .booking-user { font-size: 12px; color: #64748b; margin-top: 2px; }
        .booking-price { font-weight: 700; color: #f97316; font-size: 14px; }
        .badge {
            display: inline-block; padding: 3px 8px;
            border-radius: 20px; font-size: 11px; font-weight: 600;
        }
        .badge-success { background: #dcfce7; color: #16a34a; }
        .badge-warning { background: #fef9c3; color: #ca8a04; }
        .badge-danger { background: #fee2e2; color: #dc2626; }
        .badge-info { background: #dbeafe; color: #2563eb; }

        /* VEHICLE STAT ROW */
        .vstat-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 14px 16px; background: #f8fafc; border-radius: 8px; margin-bottom: 8px;
        }
        .vstat-row:last-child { margin-bottom: 0; }
        .vstat-label { display: flex; align-items: center; gap: 10px; color: #475569; font-size: 14px; }
        .vstat-label i { width: 16px; text-align: center; }
        .vstat-count { font-weight: 700; color: #1e293b; font-size: 16px; }

        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .content-grid { grid-template-columns: 1fr; }
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

<!-- MAIN -->
<main class="main-content">

    <div class="page-header">
        <h1>Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($currentUser['name']); ?></p>
    </div>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card" style="border-left-color:#3b82f6;">
            <div class="stat-icon" style="background:#eff6ff; color:#3b82f6;">
                <i class="fas fa-car"></i>
            </div>
            <div>
                <h3>Total Vehicles</h3>
                <div class="stat-value"><?php echo $totalVehicles; ?></div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#10b981;">
            <div class="stat-icon" style="background:#f0fdf4; color:#10b981;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div>
                <h3>Total Bookings</h3>
                <div class="stat-value"><?php echo $totalBookings; ?></div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#f97316;">
            <div class="stat-icon" style="background:#fff7ed; color:#f97316;">
                <i class="fas fa-key"></i>
            </div>
            <div>
                <h3>Active Rentals</h3>
                <div class="stat-value"><?php echo $activeBookings; ?></div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#8b5cf6;">
            <div class="stat-icon" style="background:#f5f3ff; color:#8b5cf6;">
                <i class="fas fa-wallet"></i>
            </div>
            <div>
                <h3>Total Revenue</h3>
                <div class="stat-value" style="font-size:18px;">NPR <?php echo number_format($totalRevenue); ?></div>
            </div>
        </div>
    </div>

    <!-- CONTENT GRID -->
    <div class="content-grid">

        <!-- RECENT BOOKINGS -->
        <div class="card">
            <div class="card-header"><i class="fas fa-clock" style="color:#f97316;margin-right:8px;"></i>Recent Bookings</div>
            <div class="card-body">
                <?php if (count($recentBookings) > 0): ?>
                    <?php foreach ($recentBookings as $b): ?>
                        <div class="booking-item">
                            <div>
                                <div class="booking-vehicle"><?php echo htmlspecialchars($b['vehicle_name']); ?></div>
                                <div class="booking-user"><?php echo htmlspecialchars($b['user_name']); ?></div>
                            </div>
                            <div style="text-align:right;">
                                <div class="booking-price">NPR <?php echo number_format($b['total_price']); ?></div>
                                <?php
                                    $bc = ['confirmed'=>'badge-success','pending'=>'badge-warning','cancelled'=>'badge-danger','completed'=>'badge-info'];
                                    $cls = $bc[$b['status']] ?? 'badge-info';
                                ?>
                                <span class="badge <?php echo $cls; ?>"><?php echo ucfirst($b['status']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center;color:#94a3b8;padding:32px 0;">No bookings yet</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- VEHICLE STATS -->
        <div class="card">
            <div class="card-header"><i class="fas fa-chart-pie" style="color:#3b82f6;margin-right:8px;"></i>Vehicle Statistics</div>
            <div class="card-body">
                <div class="vstat-row">
                    <div class="vstat-label"><i class="fas fa-car" style="color:#3b82f6;"></i> Cars</div>
                    <div class="vstat-count"><?php echo $vehicleCounts['Car']; ?></div>
                </div>
                <div class="vstat-row">
                    <div class="vstat-label"><i class="fas fa-motorcycle" style="color:#f97316;"></i> Bikes</div>
                    <div class="vstat-count"><?php echo $vehicleCounts['Bike']; ?></div>
                </div>
                <div class="vstat-row">
                    <div class="vstat-label"><i class="fas fa-motorcycle" style="color:#8b5cf6;"></i> Scooters</div>
                    <div class="vstat-count"><?php echo $vehicleCounts['Scooter']; ?></div>
                </div>
                <div class="vstat-row">
                    <div class="vstat-label"><i class="fas fa-users" style="color:#10b981;"></i> Total Users</div>
                    <div class="vstat-count"><?php echo $totalUsers; ?></div>
                </div>
            </div>
        </div>

    </div>
</main>

</body>
</html>
