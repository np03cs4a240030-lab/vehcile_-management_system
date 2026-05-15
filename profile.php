<?php
require_once 'config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Bhatbhatey Rental</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../src/imports/image-0.png" alt="Bhatbhatey Rental" onerror="this.style.display='none'">
            </div>
            <div class="sidebar-menu">
                <a href="./user/user-dashboard.php">📊 Dashboard</a>
                <a href="vehicles.php">🚗 Available Vehicles</a>
                <a href="my-bookings.php">📅 My Bookings</a>
                <a href="profile.php" class="active">👤 Profile</a>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <h1>My Profile</h1>
                <p style="font-size: 14px; color: #64748b;">View and manage your account information</p>
            </div>

            <div class="content-body">
                <div class="vehicle-card">
                    <div style="padding: 32px;">
                        <div style="display: flex; align-items: center; gap: 24px; margin-bottom: 32px; padding-bottom: 32px; border-bottom: 1px solid var(--border-color);">
                            <div style="width: 96px; height: 96px; background: var(--brand-orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 48px; color: white;">
                                👤
                            </div>
                            <div>
                                <h2 style="font-size: 24px; margin-bottom: 4px;"><?php echo htmlspecialchars($currentUser['name']); ?></h2>
                                <p style="color: #64748b;">Member since 2026</p>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px;">
                            <div style="padding: 16px; background: var(--brand-light-gray); border-radius: 8px;">
                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                    <span style="color: var(--brand-orange);">👤</span>
                                    <span style="font-size: 14px; color: #64748b;">Full Name</span>
                                </div>
                                <p style="font-weight: 600; color: var(--brand-dark-blue);"><?php echo htmlspecialchars($currentUser['name']); ?></p>
                            </div>

                            <div style="padding: 16px; background: var(--brand-light-gray); border-radius: 8px;">
                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                    <span style="color: var(--brand-orange);">✉️</span>
                                    <span style="font-size: 14px; color: #64748b;">Email Address</span>
                                </div>
                                <p style="font-weight: 600; color: var(--brand-dark-blue);"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                            </div>

                            <div style="padding: 16px; background: var(--brand-light-gray); border-radius: 8px;">
                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                    <span style="color: var(--brand-orange);">📞</span>
                                    <span style="font-size: 14px; color: #64748b;">Phone Number</span>
                                </div>
                                <p style="font-weight: 600; color: var(--brand-dark-blue);"><?php echo htmlspecialchars($currentUser['phone_number']); ?></p>
                            </div>

                            <div style="padding: 16px; background: var(--brand-light-gray); border-radius: 8px;">
                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                    <span style="color: var(--brand-orange);">🛡️</span>
                                    <span style="font-size: 14px; color: #64748b;">Account Type</span>
                                </div>
                                <p style="font-weight: 600; color: var(--brand-dark-blue); text-transform: capitalize;"><?php echo htmlspecialchars($currentUser['role']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
