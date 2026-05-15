<?php
require_once '../config.php';

// Check login (only normal users)
if (!isLoggedIn() || isAdmin()) {
    redirect('../login.php');
}

$currentUser = getCurrentUser();

// ✅ Available vehicles
$result = $conn->query("SELECT COUNT(*) as total FROM vehicles WHERE availability = 1");
$availableVehicles = $result->fetch_assoc()['total'];

// ✅ Active bookings
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE user_id = ? AND status = 'confirmed'");
$stmt->bind_param("i", $currentUser['id']);
$stmt->execute();
$activeBookings = $stmt->get_result()->fetch_assoc()['total'];

// ✅ Total bookings
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE user_id = ?");
$stmt->bind_param("i", $currentUser['id']);
$stmt->execute();
$totalBookings = $stmt->get_result()->fetch_assoc()['total'];

// ✅ Recent bookings with vehicle info (BEST PRACTICE using JOIN)
$stmt = $conn->prepare("
    SELECT b.*, v.name, v.image 
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $currentUser['id']);
$stmt->execute();
$recentBookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Bhatbhatey Rental</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../src/imports/image-0.png" alt="Logo" onerror="this.style.display='none'">
        </div>
        <div class="sidebar-menu">
            <a href="user-dashboard.php" class="active">📊 Dashboard</a>
            <a href="../vehicles.php">🚗 Available Vehicles</a>
            <a href="../my-bookings.php">📅 My Bookings</a>
            <a href="../profile.php">👤 Profile</a>
            <a href="../logout.php">🚪 Logout</a>
        </div>
    </aside>

    <!-- Main -->
    <main class="main-content">

        <div class="content-header">
            <h1>Welcome, <?php echo htmlspecialchars($currentUser['name']); ?> 👋</h1>
        </div>

        <div class="content-body">

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Available Vehicles</h3>
                    <div class="stat-value"><?php echo $availableVehicles; ?></div>
                </div>

                <div class="stat-card" style="border-left-color:#3b82f6;">
                    <h3>Active Bookings</h3>
                    <div class="stat-value"><?php echo $activeBookings; ?></div>
                </div>

                <div class="stat-card" style="border-left-color:#10b981;">
                    <h3>Total Rentals</h3>
                    <div class="stat-value"><?php echo $totalBookings; ?></div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="table-container">
                <div style="padding:20px; border-bottom:1px solid #ddd;">
                    <h3>Recent Activity</h3>
                </div>

                <div style="padding:20px;">

                    <?php if (count($recentBookings) > 0): ?>

                        <?php foreach ($recentBookings as $booking): ?>

                            <div style="display:flex; justify-content:space-between; align-items:center; padding:15px; background:#f8fafc; border-radius:8px; margin-bottom:10px;">

                                <div style="display:flex; gap:15px; align-items:center;">
                                    <img src="<?php echo htmlspecialchars($booking['image']); ?>"
                                         style="width:60px; height:60px; object-fit:cover; border-radius:8px;">

                                    <div>
                                        <strong><?php echo htmlspecialchars($booking['name']); ?></strong>
                                        <div style="font-size:13px; color:#666;">
                                            <?php echo $booking['start_date']; ?> → <?php echo $booking['end_date']; ?>
                                        </div>
                                    </div>
                                </div>

                                <div style="text-align:right;">
                                    <div style="font-weight:bold; color:#f97316;">
                                        NPR <?php echo number_format($booking['total_price']); ?>
                                    </div>

                                    <span style="padding:5px 10px; border-radius:5px; background:#10b981; color:#fff; font-size:12px;">
                                        <?php echo $booking['status']; ?>
                                    </span>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div style="text-align:center; padding:40px; color:#999;">
                            <h3>No bookings yet</h3>
                            <p>Start by renting a vehicle 🚗</p>
                        </div>

                    <?php endif; ?>

                </div>
            </div>

        </div>
    </main>
</div>
</body>
</html>