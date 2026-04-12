<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('admin-login.php');
}

$currentUser = getCurrentUser();



// Total Vehicles
$result = $conn->query("SELECT COUNT(*) as total FROM vehicles");
$totalVehicles = $result->fetch_assoc()['total'];

// Total Bookings
$result = $conn->query("SELECT COUNT(*) as total FROM bookings");
$totalBookings = $result->fetch_assoc()['total'];

// Active Bookings
$result = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'confirmed'");
$activeBookings = $result->fetch_assoc()['total'];

// Total Revenue
$result = $conn->query("SELECT SUM(total_price) as total FROM bookings WHERE status != 'cancelled'");
$totalRevenue = $result->fetch_assoc()['total'] ?? 0;

// Total Users
$result = $conn->query("SELECT COUNT(*) as total FROM users");
$totalUsers = $result->fetch_assoc()['total'];



$recentBookingsQuery = "
    SELECT b.*, u.name AS user_name, v.name AS vehicle_name
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
    ORDER BY b.created_at DESC
    LIMIT 5
";
$recentBookings = $conn->query($recentBookingsQuery)->fetch_all(MYSQLI_ASSOC);


/*
   VEHICLE TYPE STATS
 */
$vehicleStatsQuery = "SELECT type, COUNT(*) as count FROM vehicles GROUP BY type";
$vehicleStatsResult = $conn->query($vehicleStatsQuery);

$vehicleCounts = [
    'Car' => 0,
    'Bike' => 0,
    'Scooter' => 0
];

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
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="dashboard">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/images/logo.png" alt="Bhatbhatey Rental" style="height: 3rem;">
            <p style="font-size: 14px; color: #94a3b8; margin-top: 8px;">Admin Panel</p>
        </div>

        <div class="sidebar-menu">
            <a href="admin-dashboard.php" class="active">📊 Dashboard</a>
            <a href="admin-vehicles.php">🚗 Vehicles</a>
            <a href="admin-bookings.php">📅 Bookings</a>
            <a href="admin-users.php">👥 Users</a>
            <a href="../logout.php">🚪 Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">

        <div class="content-header">
            <h1>Admin Dashboard</h1>
            <p style="font-size: 14px; color: #64748b;">
                Welcome back, <?php echo htmlspecialchars($currentUser['name']); ?>
            </p>
        </div>

        <div class="content-body">

            <!-- STATS -->
            <div class="stats-grid">

                <div class="stat-card" style="border-left-color: #3b82f6;">
                    <h3>Total Vehicles</h3>
                    <div class="stat-value"><?php echo $totalVehicles; ?></div>
                </div>

                <div class="stat-card" style="border-left-color: #10b981;">
                    <h3>Total Bookings</h3>
                    <div class="stat-value"><?php echo $totalBookings; ?></div>
                </div>

                <div class="stat-card">
                    <h3>Active Rentals</h3>
                    <div class="stat-value"><?php echo $activeBookings; ?></div>
                </div>

                <div class="stat-card" style="border-left-color: #8b5cf6;">
                    <h3>Total Revenue</h3>
                    <div class="stat-value">NPR <?php echo number_format($totalRevenue); ?></div>
                </div>

            </div>

            <!-- GRID SECTION -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">

                <!-- RECENT BOOKINGS -->
                <div class="table-container">
                    <div style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                        <h3>Recent Bookings</h3>
                    </div>

                    <div style="padding: 24px;">

                        <?php if (count($recentBookings) > 0): ?>
                            <?php foreach ($recentBookings as $booking): ?>
                                <div style="display: flex; justify-content: space-between; padding: 12px; background: #f8fafc; border-radius: 8px; margin-bottom: 8px;">
                                    
                                    <div>
                                        <strong style="display: block;">
                                            <?php echo htmlspecialchars($booking['vehicle_name']); ?>
                                        </strong>
                                        <small style="color: #64748b;">
                                            <?php echo htmlspecialchars($booking['user_name']); ?>
                                        </small>
                                    </div>

                                    <div style="text-align: right;">
                                        <strong style="color: var(--brand-orange); display: block;">
                                            NPR <?php echo number_format($booking['total_price']); ?>
                                        </strong>
                                        <span class="badge badge-success" style="font-size: 11px;">
                                            <?php echo $booking['status']; ?>
                                        </span>
                                    </div>

                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: #94a3b8; padding: 32px;">
                                No bookings yet
                            </p>
                        <?php endif; ?>

                    </div>
                </div>

                <!-- VEHICLE STATS -->
                <div class="table-container">
                    <div style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                        <h3>Vehicle Statistics</h3>
                    </div>

                    <div style="padding: 24px;">

                        <div style="display: flex; justify-content: space-between; padding: 16px; background: #f8fafc; border-radius: 8px; margin-bottom: 12px;">
                            <span> Cars</span>
                            <strong><?php echo $vehicleCounts['Car']; ?></strong>
                        </div>

                        <div style="display: flex; justify-content: space-between; padding: 16px; background: #f8fafc; border-radius: 8px; margin-bottom: 12px;">
                            <span> Bikes</span>
                            <strong><?php echo $vehicleCounts['Bike']; ?></strong>
                        </div>

                        <div style="display: flex; justify-content: space-between; padding: 16px; background: #f8fafc; border-radius: 8px; margin-bottom: 12px;">
                            <span> Scooters</span>
                            <strong><?php echo $vehicleCounts['Scooter']; ?></strong>
                        </div>

                        <div style="display: flex; justify-content: space-between; padding: 16px; background: #f8fafc; border-radius: 8px;">
                            <span> Total Users</span>
                            <strong><?php echo $totalUsers; ?></strong>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </main>

</div>

</body>
</html>