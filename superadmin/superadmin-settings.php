<?php
require_once '../config.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../login.php');
}

$success = '';
$error = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Update Profile Logic
    if (isset($_POST['update_profile'])) {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);

        $sql = "UPDATE users SET name = ?, email = ?, phone_number = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $name, $email, $phone, $_SESSION['user_id']);

        if ($stmt->execute()) {
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            $success = 'Profile updated successfully!';
        } else {
            $error = 'Failed to update profile: ' . $conn->error;
        }
    }

    // Change Password Logic
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters long';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        } else {
            // Verify current password
            $sql = "SELECT password FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();

            if ($user_data && password_verify($current_password, $user_data['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);

                if ($stmt->execute()) {
                    $success = 'Password changed successfully!';
                } else {
                    $error = 'Failed to update password in database';
                }
            } else {
                $error = 'Current password is incorrect';
            }
        }
    }
}

// Fetch fresh user details for the form
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get system statistics (Using error suppression or null coalesce for safety)
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'] ?? 0;
$total_vehicles = $conn->query("SELECT COUNT(*) as count FROM vehicles")->fetch_assoc()['count'] ?? 0;
$total_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Super Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Sidebar styles */
        .super-admin-sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            border-right: 1px solid rgba(148, 163, 184, 0.1);
        }

        .sidebar-header {
            padding: 2rem;
            text-align: center;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }

        .sidebar-nav {
            padding: 1rem;
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 250px);
        }

        .nav-item {
            padding: 1rem 1.5rem;
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.3s;
        }

        .nav-item:hover {
            background: rgba(148, 163, 184, 0.1);
            color: white;
        }

        .nav-item.active {
            background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);
            color: white;
        }

        /* Card styles */
        .data-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.5rem;
            box-sizing: border-box;
            /* Ensures padding doesn't break width */
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            color: white;
        }

        .alert-success {
            background: #10b981;
        }

        .alert-error {
            background: #ef4444;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
        }
    </style>
</head>

<body style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); min-height: 100vh; color: white;">
    <div class="dashboard-layout" style="display: flex;">
        <aside class="sidebar super-admin-sidebar" style="width: 280px; min-height: 100vh;">
            <div class="sidebar-header">
                <img src="../assets/images/logo.png" alt="Logo" style="height: 3rem;">
                <h3 style="color: white; margin-top: 1rem;">Super Admin</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="superadmin-dashboard.php" class="nav-item"><i class="fas fa-gauge-high"></i> Dashboard</a>
                <a href="superadmin-users.php" class="nav-item"><i class="fas fa-users"></i> Users</a>
                <a href="superadmin-vehicles.php" class="nav-item"><i class="fas fa-car"></i> Vehicles</a>
                <a href="superadmin-bookings.php" class="nav-item"><i class="fas fa-calendar-days"></i> Bookings</a>
                <a href="superadmin-settings.php" class="nav-item active"><i class="fas fa-gear"></i> Settings</a>
                <a href="../logout.php" class="nav-item"><i class="fas fa-right-from-bracket"></i> Logout</a>
            </nav>
        </aside>

        <main class="main-content" style="flex: 1; padding: 2rem;">
            <div class="content-header" style="margin-bottom: 2rem;">
                <h1 style="color: white; margin: 0;">System Settings</h1>
                <p style="color: #94a3b8;">Manage your account and system preferences</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                <div class="left-column">
                    <div class="data-card" style="margin-bottom: 2rem;">
                        <h3
                            style="color: white; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                            <i class="fas fa-user" style="margin-right:8px;"></i>Profile Information
                        </h3>
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-input"
                                    value="<?php echo htmlspecialchars($user['name']); ?>" required
                                    style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white;">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-input"
                                    value="<?php echo htmlspecialchars($user['email']); ?>" required
                                    style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white;">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-input"
                                    value="<?php echo htmlspecialchars($user['phone_number']); ?>" required
                                    style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white;">
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary"
                                style="background: #3b82f6; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 0.5rem; cursor: pointer;">
                                <i class="fas fa-floppy-disk" style="margin-right: 6px;"></i> Update Profile
                            </button>
                        </form>
                    </div>

                    <div class="data-card">
                        <h3
                            style="color: white; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                            <i class="fas fa-lock" style="margin-right:8px;"></i>Change Password
                        </h3>
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-input" required
                                    style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white;">
                            </div>
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-input" required
                                    style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white;">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-input" required
                                    style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white;">
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary"
                                style="background: #3b82f6; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 0.5rem; cursor: pointer;">
                                <i class="fas fa-key" style="margin-right:6px;"></i>Change Password
                            </button>
                        </form>
                    </div>
                </div>

                <div class="right-column">
                    <div class="data-card" style="margin-bottom: 2rem;">
                        <h3 style="color: white; margin-bottom: 1.5rem;"><i class="fas fa-chart-bar" style="margin-right: 8px;"></i> System Overview</h3>
                        <div
                            style="padding: 1rem; background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6; border-radius: 0.5rem; margin-bottom: 1rem;">
                            <div style="color: #93c5fd; font-size: 0.875rem;">Total Users</div>
                            <div style="color: white; font-size: 1.5rem; font-weight: 700;">
                                <?php echo (int) $total_users; ?></div>
                        </div>
                        <div
                            style="padding: 1rem; background: rgba(249, 115, 22, 0.1); border-left: 4px solid #f97316; border-radius: 0.5rem; margin-bottom: 1rem;">
                            <div style="color: #fdba74; font-size: 0.875rem;">Total Vehicles</div>
                            <div style="color: white; font-size: 1.5rem; font-weight: 700;">
                                <?php echo (int) $total_vehicles; ?></div>
                        </div>
                        <div
                            style="padding: 1rem; background: rgba(16, 185, 129, 0.1); border-left: 4px solid #10b981; border-radius: 0.5rem;">
                            <div style="color: #6ee7b7; font-size: 0.875rem;">Total Bookings</div>
                            <div style="color: white; font-size: 1.5rem; font-weight: 700;">
                                <?php echo (int) $total_bookings; ?></div>
                        </div>
                    </div>

                    <div class="data-card">
                        <h3 style="color: white; margin-bottom: 1.5rem;"><i class="fas fa-circle-info" style="margin-right:8px;"></i>Account Info</h3>
                        <div style="margin-bottom: 1rem;">
                            <div style="font-size: 0.875rem; color: #64748b;">Role</div>
                            <div style="color: white; font-weight: 600;"><i class="fas fa-crown" style="margin-right:6px; color:#a855f7;"></i>Super Administrator</div>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <div style="font-size: 0.875rem; color: #64748b;">Account Created</div>
                            <div style="color: white;"><?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                            </div>
                        </div>
                        <div>
                            <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem;">Status</div>
                            <span class="badge-success"><i class="fas fa-circle-check" style="margin-right: 4px;"></i> Active</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>