<?php
require_once '../config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();
if (!$currentUser) die("User not found. Please login again.");

$result = $conn->query("SELECT COUNT(*) as total FROM vehicles WHERE availability = 1");
$availableVehicles = $result->fetch_assoc()['total'] ?? 0;

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE user_id = ? AND status = 'confirmed'");
$stmt->bind_param("i", $currentUser['id']); $stmt->execute();
$activeBookings = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE user_id = ?");
$stmt->bind_param("i", $currentUser['id']); $stmt->execute();
$totalBookings = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

$stmt = $conn->prepare("SELECT b.*, v.name, v.image FROM bookings b JOIN vehicles v ON b.vehicle_id = v.id WHERE b.user_id = ? ORDER BY b.created_at DESC LIMIT 5");
$stmt->bind_param("i", $currentUser['id']); $stmt->execute();
$recentBookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Bhatbhatey Rental</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    :root{
        --primary:#f97316;--primary-light:#fff7ed;
        --dark:#0f172a;--dark-blue:#1e293b;
        --slate:#64748b;--border:#e2e8f0;--bg:#f1f5f9;
    }
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Plus Jakarta Sans',sans-serif;}
    body{background:var(--bg);display:flex;min-height:100vh;}
    a{text-decoration:none;transition:0.2s;}

    /* SIDEBAR */
    .sidebar{width:250px;background:var(--dark-blue);position:fixed;height:100%;display:flex;flex-direction:column;box-shadow:4px 0 20px rgba(0,0,0,0.15);z-index:100;}

    .sidebar-logo{padding:16px 18px;border-bottom:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;gap:10px;}
    .sidebar-logo img{height:38px;width:auto;object-fit:contain;filter:brightness(0) invert(1);}
    .logo-fallback{display:none;width:36px;height:36px;background:var(--primary);border-radius:9px;align-items:center;justify-content:center;color:white;font-size:15px;flex-shrink:0;}
    .sidebar-logo-text{display:flex;flex-direction:column;line-height:1.2;}
    .sidebar-logo-text .lt-name{color:white;font-size:15px;font-weight:800;}
    .sidebar-logo-text .lt-sub{color:#64748b;font-size:9px;text-transform:uppercase;letter-spacing:1px;}

    .sidebar-nav{padding:16px 12px;flex:1;overflow-y:auto;}
    .nav-section-label{font-size:10px;text-transform:uppercase;letter-spacing:1.5px;color:#475569;font-weight:700;padding:0 8px;margin:16px 0 6px;}
    .sidebar-nav a{display:flex;align-items:center;gap:11px;padding:11px 12px;border-radius:10px;color:#94a3b8;font-size:14px;font-weight:600;margin-bottom:3px;}
    .sidebar-nav a i{width:18px;text-align:center;font-size:14px;}
    .sidebar-nav a:hover{background:#334155;color:white;}
    .sidebar-nav a.active{background:var(--primary);color:white;box-shadow:0 4px 12px rgba(249,115,22,0.3);}
    .sidebar-nav a.danger:hover{background:#7f1d1d;color:#fca5a5;}

    /* Sidebar footer — clean user card with initials */
    .sidebar-footer{padding:12px 14px;border-top:1px solid rgba(255,255,255,0.07);}
    .user-card{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:12px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.07);}
    .user-initials{width:36px;height:36px;background:var(--primary);border-radius:9px;display:flex;align-items:center;justify-content:center;color:white;font-size:12px;font-weight:800;flex-shrink:0;letter-spacing:0.5px;text-transform:uppercase;}
    .user-info-inner{flex:1;min-width:0;}
    .u-name{color:white;font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    .u-badge{display:inline-flex;align-items:center;gap:4px;font-size:10px;color:#94a3b8;margin-top:2px;}
    .u-badge i{font-size:7px;color:#22c55e;}

    /* MAIN */
    .main{margin-left:250px;padding:32px;width:100%;min-height:100vh;}
    .page-header{margin-bottom:28px;}
    .page-header-top{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;}
    .page-header h1{font-size:24px;font-weight:800;color:var(--dark);}
    .page-header p{color:var(--slate);margin-top:4px;font-size:14px;}
    .btn-book-now{background:var(--primary);color:white;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:700;display:flex;align-items:center;gap:7px;box-shadow:0 4px 12px rgba(249,115,22,0.25);}
    .btn-book-now:hover{background:#ea6c09;color:white;}

    /* Stats */
    .stats{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:28px;}
    .stat-card{background:white;padding:22px;border-radius:16px;display:flex;align-items:center;gap:16px;box-shadow:0 2px 8px rgba(0,0,0,0.05);border:1px solid var(--border);transition:0.2s;}
    .stat-card:hover{box-shadow:0 8px 24px rgba(0,0,0,0.08);}
    .stat-icon{width:50px;height:50px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
    .stat-label{font-size:11px;color:var(--slate);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;}
    .stat-value{font-size:28px;font-weight:800;color:var(--dark);}

    /* Recent bookings */
    .section{background:white;border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,0.05);border:1px solid var(--border);}
    .section-head{padding:18px 22px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;}
    .section-head h2{font-size:16px;font-weight:800;color:var(--dark);display:flex;align-items:center;gap:8px;}
    .section-head h2 i{color:var(--primary);}
    .view-all{font-size:13px;font-weight:700;color:var(--primary);display:flex;align-items:center;gap:5px;}
    .view-all:hover{color:#ea6c09;}

    .booking-row{display:flex;justify-content:space-between;align-items:center;padding:16px 22px;border-bottom:1px solid #f8fafc;transition:0.15s;}
    .booking-row:last-child{border-bottom:none;}
    .booking-row:hover{background:#fafafa;}
    .booking-left{display:flex;gap:14px;align-items:center;}
    .booking-thumb{width:58px;height:58px;border-radius:12px;object-fit:cover;border:1px solid var(--border);background:#f8fafc;flex-shrink:0;}
    .booking-name{font-size:15px;font-weight:700;color:var(--dark);margin-bottom:3px;}
    .booking-dates{font-size:12px;color:var(--slate);display:flex;align-items:center;gap:5px;}
    .booking-dates i{font-size:10px;}
    .booking-right{text-align:right;}
    .booking-price{font-size:16px;font-weight:800;color:var(--primary);margin-bottom:5px;}

    .badge{padding:4px 10px;border-radius:100px;font-size:11px;font-weight:700;display:inline-flex;align-items:center;gap:4px;}
    .confirmed,.approved{background:#d1fae5;color:#065f46;}
    .pending{background:#fef9c3;color:#ca8a04;}
    .cancelled{background:#fee2e2;color:#dc2626;}
    .ongoing{background:#fce7f3;color:#be185d;}
    .completed{background:#dbeafe;color:#1d4ed8;}

    .empty{text-align:center;padding:50px 20px;color:var(--slate);}
    .empty i{font-size:40px;color:#e2e8f0;margin-bottom:14px;display:block;}
    .empty h3{font-size:17px;color:#475569;margin-bottom:8px;}
    .empty a{display:inline-flex;align-items:center;gap:7px;margin-top:16px;padding:10px 22px;background:var(--primary);color:white;border-radius:9px;font-weight:700;font-size:14px;}
</style>
</head>
<body>

<div class="sidebar">
    <!-- Logo — ../nobglogo.png because this file lives in user/ subfolder -->
    <a href="../index.php" class="sidebar-logo">
        <img src="../nobglogo.png" alt="Bhatbhatey"
             onerror="this.style.display='none'; document.querySelector('.logo-fallback').style.display='flex';">
        <div class="logo-fallback"><i class="fas fa-car"></i></div>
        <div class="sidebar-logo-text">
            <span class="lt-name">Bhatbhatey</span>
            <span class="lt-sub">Rental</span>
        </div>
    </a>

    <div class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <a href="user-dashboard.php" class="active"><i class="fas fa-gauge-high"></i> Dashboard</a>
        <a href="../vehicles.php"><i class="fas fa-car"></i> Browse Vehicles</a>
        <a href="../my-bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a>
        <a href="../support-tickets.php"><i class="fas fa-ticket-alt"></i> Support Tickets</a>
        <div class="nav-section-label">Account</div>
        <a href="../profile.php"><i class="fas fa-user"></i> Profile</a>
        <a href="../logout.php" class="danger"><i class="fas fa-right-from-bracket"></i> Logout</a>
    </div>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-initials">
                <?php echo strtoupper(substr($currentUser['name'] ?? 'U', 0, 2)); ?>
            </div>
            <div class="user-info-inner">
                <div class="u-name"><?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?></div>
                <div class="u-badge"><i class="fas fa-circle"></i> Active Member</div>
            </div>
        </div>
    </div>
</div>

<div class="main">
    <div class="page-header">
        <div class="page-header-top">
            <div>
                <h1>Welcome back, <?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?></h1>
                <p>Here is your rental overview at a glance.</p>
            </div>
            <a href="../vehicles.php" class="btn-book-now"><i class="fas fa-plus"></i> Book a Vehicle</a>
        </div>
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon" style="background:#eff6ff;color:#3b82f6;"><i class="fas fa-car"></i></div>
            <div>
                <div class="stat-label">Available Vehicles</div>
                <div class="stat-value"><?php echo $availableVehicles; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#f0fdf4;color:#10b981;"><i class="fas fa-key"></i></div>
            <div>
                <div class="stat-label">Active Rentals</div>
                <div class="stat-value"><?php echo $activeBookings; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#fff7ed;color:#f97316;"><i class="fas fa-calendar-check"></i></div>
            <div>
                <div class="stat-label">Total Bookings</div>
                <div class="stat-value"><?php echo $totalBookings; ?></div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-head">
            <h2><i class="fas fa-clock-rotate-left"></i> Recent Activity</h2>
            <a href="../my-bookings.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
        </div>

        <?php if(!empty($recentBookings)): ?>
            <?php foreach($recentBookings as $b):
                $st = strtolower($b['status']);
                $statusIcons = ['pending'=>'fa-clock','approved'=>'fa-circle-check','confirmed'=>'fa-circle-check','ongoing'=>'fa-car-side','completed'=>'fa-flag-checkered','cancelled'=>'fa-ban'];
                $si = $statusIcons[$st] ?? 'fa-clock';
            ?>
            <div class="booking-row">
                <div class="booking-left">
                    <img src="<?php echo htmlspecialchars($b['image']); ?>" class="booking-thumb"
                         alt="<?php echo htmlspecialchars($b['name']); ?>"
                         onerror="this.style.display='none';">
                    <div>
                        <div class="booking-name"><?php echo htmlspecialchars($b['name']); ?></div>
                        <div class="booking-dates">
                            <i class="fas fa-calendar"></i>
                            <?php echo $b['start_date']; ?>
                            <i class="fas fa-arrow-right"></i>
                            <?php echo $b['end_date']; ?>
                        </div>
                    </div>
                </div>
                <div class="booking-right">
                    <div class="booking-price">NPR <?php echo number_format($b['total_price']); ?></div>
                    <span class="badge <?php echo $st; ?>">
                        <i class="fas <?php echo $si; ?>"></i> <?php echo ucfirst($b['status']); ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty">
                <i class="fas fa-calendar-xmark"></i>
                <h3>No bookings yet</h3>
                <p>Start exploring our fleet and book your first vehicle.</p>
                <a href="../vehicles.php"><i class="fas fa-car"></i> Browse Vehicles</a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
