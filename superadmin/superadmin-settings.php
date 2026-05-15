<?php
require_once '../config.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../login.php');
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['update_profile'])) {
        $name  = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);

        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone_number = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $email, $phone, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $_SESSION['name']  = $name;
            $_SESSION['email'] = $email;
            $success = 'Profile updated successfully.';
        } else {
            $error = 'Failed to update profile.';
        }
    }

    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new     = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        if (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row && password_verify($current, $row['password'])) {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $stmt2  = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt2->bind_param("si", $hashed, $_SESSION['user_id']);
                if ($stmt2->execute()) {
                    $success = 'Password changed successfully.';
                } else {
                    $error = 'Failed to update password.';
                }
            } else {
                $error = 'Current password is incorrect.';
            }
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$total_users    = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='user'")->fetch_assoc()['c'];
$total_admins   = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='admin'")->fetch_assoc()['c'];
$total_vehicles = $conn->query("SELECT COUNT(*) AS c FROM vehicles")->fetch_assoc()['c'];
$total_bookings = $conn->query("SELECT COUNT(*) AS c FROM bookings")->fetch_assoc()['c'];
$total_revenue  = $conn->query("SELECT COALESCE(SUM(total_price),0) AS t FROM bookings WHERE status NOT IN ('cancelled')")->fetch_assoc()['t'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Super Admin | Bhatbhatey Rental</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
        body { background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%); min-height:100vh; display:flex; color:white; }

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
        .page-header { margin-bottom:28px; }
        .page-header h1 { font-size:22px; font-weight:700; color:white; }
        .page-header p { color:#94a3b8; font-size:14px; margin-top:3px; }

        /* ALERT */
        .alert { padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:14px; font-weight:500; display:flex; align-items:center; gap:10px; }
        .alert-success { background:rgba(16,185,129,0.15); border:1px solid rgba(16,185,129,0.3); color:#6ee7b7; }
        .alert-error   { background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.3); color:#fca5a5; }

        /* LAYOUT */
        .settings-grid { display:grid; grid-template-columns:1.4fr 1fr; gap:24px; }

        /* CARD */
        .card { background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); border-radius:14px; overflow:hidden; margin-bottom:22px; }
        .card:last-child { margin-bottom:0; }
        .card-header { padding:16px 20px; border-bottom:1px solid rgba(255,255,255,0.07); font-size:14px; font-weight:700; color:white; display:flex; align-items:center; gap:9px; }
        .card-header i { color:#a855f7; }
        .card-body { padding:24px; }

        /* FORM */
        .form-group { margin-bottom:18px; }
        .form-group:last-child { margin-bottom:0; }
        .form-label { display:block; font-size:12px; color:#94a3b8; font-weight:600; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.4px; }
        .form-input { width:100%; padding:10px 14px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.12); border-radius:8px; color:white; font-size:14px; outline:none; transition:border-color 0.2s; }
        .form-input:focus { border-color:#9333ea; background:rgba(255,255,255,0.07); }
        .form-input::placeholder { color:#475569; }

        /* BUTTON */
        .btn-submit { display:inline-flex; align-items:center; gap:8px; padding:10px 20px; border-radius:8px; border:none; font-size:14px; font-weight:600; cursor:pointer; transition:all 0.2s; }
        .btn-purple { background:linear-gradient(135deg,#9333ea,#7c3aed); color:white; }
        .btn-purple:hover { box-shadow:0 4px 15px rgba(147,51,234,0.4); transform:translateY(-1px); }

        /* STAT ITEMS */
        .stat-item { padding:14px 16px; background:rgba(255,255,255,0.03); border-radius:10px; border:1px solid rgba(255,255,255,0.06); margin-bottom:10px; display:flex; justify-content:space-between; align-items:center; }
        .stat-item:last-child { margin-bottom:0; }
        .stat-item-label { font-size:13px; color:#94a3b8; display:flex; align-items:center; gap:9px; }
        .stat-item-value { font-size:18px; font-weight:800; color:white; }

        /* ACCOUNT INFO ROWS */
        .info-row { padding:12px 0; border-bottom:1px solid rgba(255,255,255,0.06); display:flex; justify-content:space-between; align-items:center; font-size:14px; }
        .info-row:last-child { border-bottom:none; padding-bottom:0; }
        .info-label { color:#94a3b8; }
        .info-value { color:white; font-weight:600; }

        .badge-active { background:rgba(16,185,129,0.15); color:#6ee7b7; border:1px solid rgba(16,185,129,0.3); padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }

        @media (max-width:1024px) { .settings-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="../assets/images/logo.png" alt="Logo">
        <div class="sidebar-logo-text">
            <div class="title">Bhatbhatey Rental</div>
            <div class="sub">Super Admin</div>
        </div>
    </div>
    <nav class="sidebar-menu">
        <a href="superadmin-dashboard.php"><i class="fas fa-gauge-high"></i> Dashboard</a>
        <a href="superadmin-users.php"><i class="fas fa-users"></i> Users</a>
        <a href="superadmin-admins.php"><i class="fas fa-user-shield"></i> Admins</a>
        <a href="superadmin-vehicles.php"><i class="fas fa-car"></i> Vehicles</a>
        <a href="superadmin-bookings.php"><i class="fas fa-calendar-days"></i> Bookings</a>
        <a href="superadmin-settings.php" class="active"><i class="fas fa-gear"></i> Settings</a>
        <div class="logout-link">
            <a href="../logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </div>
    </nav>
</aside>

<main class="main-content">

    <div class="page-header">
        <h1>Settings</h1>
        <p>Manage your account and view system information</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="settings-grid">

        <!-- LEFT COLUMN -->
        <div>
            <!-- Profile Information -->
            <div class="card">
                <div class="card-header"><i class="fas fa-user"></i> Profile Information</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-input" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
                        </div>
                        <button type="submit" name="update_profile" class="btn-submit btn-purple">
                            <i class="fas fa-floppy-disk"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card">
                <div class="card-header"><i class="fas fa-lock"></i> Change Password</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-input" placeholder="Enter current password" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-input" placeholder="At least 6 characters" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-input" placeholder="Repeat new password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn-submit btn-purple">
                            <i class="fas fa-key"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div>
            <!-- Account Info -->
            <div class="card">
                <div class="card-header"><i class="fas fa-circle-info"></i> Account Information</div>
                <div class="card-body">
                    <div class="info-row">
                        <span class="info-label">Role</span>
                        <span class="info-value"><i class="fas fa-crown" style="color:#a855f7; margin-right:5px;"></i>Super Administrator</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Member Since</span>
                        <span class="info-value"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="badge-active"><i class="fas fa-circle-check" style="margin-right:4px;"></i> Active</span>
                    </div>
                </div>
            </div>

            <!-- System Overview -->
            <div class="card">
                <div class="card-header"><i class="fas fa-chart-bar"></i> System Overview</div>
                <div class="card-body">
                    <div class="stat-item">
                        <div class="stat-item-label"><i class="fas fa-users" style="color:#3b82f6;"></i> Total Users</div>
                        <div class="stat-item-value"><?php echo $total_users; ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-item-label"><i class="fas fa-user-shield" style="color:#a855f7;"></i> Admins</div>
                        <div class="stat-item-value"><?php echo $total_admins; ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-item-label"><i class="fas fa-car" style="color:#f97316;"></i> Vehicles</div>
                        <div class="stat-item-value"><?php echo $total_vehicles; ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-item-label"><i class="fas fa-calendar-days" style="color:#10b981;"></i> Total Bookings</div>
                        <div class="stat-item-value"><?php echo $total_bookings; ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-item-label"><i class="fas fa-wallet" style="color:#eab308;"></i> Total Revenue</div>
                        <div class="stat-item-value" style="font-size:14px;">NPR <?php echo number_format($total_revenue); ?></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

</body>
</html>
