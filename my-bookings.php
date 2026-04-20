<?php
require_once 'config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();

// Fetch bookings with vehicle details
$stmt = $conn->prepare("
    SELECT 
        b.*, 
        v.name AS vehicle_name,
        v.type AS vehicle_type,
        v.image AS vehicle_image
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.user_id = ?
    ORDER BY b.id DESC
");

$stmt->bind_param("i", $currentUser['id']);
$stmt->execute();
$result = $stmt->get_result();

$userBookings = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Bhatbhatey Rental</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .nav-icon {
            width: 18px; height: 18px;
            vertical-align: middle; margin-right: 8px; stroke-width: 2;
        }
        .info-icon {
            width: 14px; height: 14px;
            vertical-align: middle; margin-right: 4px; stroke-width: 2; color: #64748b;
        }
        .empty-icon {
            width: 56px; height: 56px; color: #94a3b8; margin-bottom: 16px;
        }
        .success-icon {
            width: 16px; height: 16px; vertical-align: middle; margin-right: 6px;
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
                <a href="./user/user-dashboard.php">
                    <i data-lucide="layout-dashboard" class="nav-icon"></i> Dashboard
                </a>
                <a href="vehicles.php">
                    <i data-lucide="car" class="nav-icon"></i> Available Vehicles
                </a>
                <a href="my-bookings.php" class="active">
                    <i data-lucide="calendar-check" class="nav-icon"></i> My Bookings
                </a>
                <a href="profile.php">
                    <i data-lucide="user" class="nav-icon"></i> Profile
                </a>
                <a href="logout.php">
                    <i data-lucide="log-out" class="nav-icon"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <h1>My Bookings</h1>
                <p style="font-size: 14px; color: #64748b;">View and manage your rental bookings</p>
            </div>

            <div class="content-body">
                <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success" style="margin-bottom: 24px;">
                    <i data-lucide="circle-check" class="success-icon"></i>
                    Booking confirmed successfully!
                </div>
                <?php endif; ?>

                <?php if (count($userBookings) > 0): ?>
                    <?php foreach ($userBookings as $booking): ?>
                        <div class="vehicle-card" style="margin-bottom: 24px;">
                            <div style="padding: 24px;">
                                <div style="display: grid; grid-template-columns: 200px 1fr; gap: 24px;">

                                    <img src="<?php echo htmlspecialchars($booking['vehicle_image'] ?? ''); ?>"
                                         alt="<?php echo htmlspecialchars($booking['vehicle_name'] ?? ''); ?>"
                                         style="width: 100%; height: 128px; object-fit: cover; border-radius: 8px;">

                                    <div>
                                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px;">
                                            <div>
                                                <h3 style="font-size: 20px; margin-bottom: 4px;">
                                                    <?php echo htmlspecialchars($booking['vehicle_name'] ?? ''); ?>
                                                </h3>
                                                <p style="color: #64748b;">
                                                    <?php echo htmlspecialchars($booking['vehicle_type'] ?? ''); ?>
                                                </p>
                                            </div>

                                            <span class="badge badge-success">
                                                <?php echo $booking['status'] ?? 'pending'; ?>
                                            </span>
                                        </div>

                                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                                            <div>
                                                <div style="color: #64748b; font-size: 12px; margin-bottom: 4px; display: flex; align-items: center;">
                                                    <i data-lucide="calendar" class="info-icon"></i> Rental Period
                                                </div>
                                                <div style="font-weight: 600; font-size: 14px;">
                                                    <?php echo $booking['start_date']; ?> to <?php echo $booking['end_date']; ?>
                                                </div>
                                            </div>

                                            <div>
                                                <div style="color: #64748b; font-size: 12px; margin-bottom: 4px; display: flex; align-items: center;">
                                                    <i data-lucide="map-pin" class="info-icon"></i> Pickup Location
                                                </div>
                                                <div style="font-weight: 600; font-size: 14px;">
                                                    <?php echo htmlspecialchars($booking['pickup_location'] ?? 'N/A'); ?>
                                                </div>
                                            </div>

                                            <div>
                                                <div style="color: #64748b; font-size: 12px; margin-bottom: 4px; display: flex; align-items: center;">
                                                    <i data-lucide="banknote" class="info-icon"></i> Total Cost
                                                </div>
                                                <div style="font-weight: 600; font-size: 14px;">
                                                    NPR <?php echo number_format($booking['total_price'] ?? 0); ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border-color); display: flex; gap: 24px; font-size: 14px;">
                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                <i data-lucide="credit-card" class="info-icon"></i>
                                                <span style="color: #64748b;">Payment:</span>
                                                <strong><?php echo htmlspecialchars($booking['payment_method'] ?? 'N/A'); ?></strong>
                                            </div>

                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                <?php $paymentStatus = $booking['payment_status'] ?? 'pending'; ?>
                                                <i data-lucide="<?php echo $paymentStatus === 'paid' ? 'circle-check' : 'clock'; ?>"
                                                   class="info-icon"
                                                   style="color: <?php echo $paymentStatus === 'paid' ? '#10b981' : '#f59e0b'; ?>">
                                                </i>
                                                <span style="color: #64748b;">Status:</span>
                                                <strong style="color: <?php echo $paymentStatus === 'paid' ? '#10b981' : '#f59e0b'; ?>">
                                                    <?php echo $paymentStatus; ?>
                                                </strong>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="vehicle-card">
                        <div style="padding: 64px; text-align: center;">
                            <div style="display: flex; justify-content: center; margin-bottom: 16px;">
                                <i data-lucide="calendar-x" class="empty-icon"></i>
                            </div>
                            <h3 style="font-size: 20px; color: #475569; margin-bottom: 8px;">No Bookings Yet</h3>
                            <p style="color: #94a3b8; margin-bottom: 24px;">You haven't made any bookings yet. Start exploring our vehicles!</p>
                            <a href="vehicles.php" class="btn btn-primary">Browse Vehicles</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>