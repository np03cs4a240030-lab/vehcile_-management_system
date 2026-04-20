<?php
require_once '../config.php';

// 🔐 Auth check
if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

// 👤 Current user
$currentUser = getCurrentUser();

// 🛡️ Safety fallback
if (!$currentUser) {
    die("User not found. Please login again.");
}

// 🚗 Available vehicles
$result = $conn->query("SELECT COUNT(*) as total FROM vehicles WHERE availability = 1");
$availableVehicles = $result->fetch_assoc()['total'] ?? 0;

// 📌 Active bookings
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE user_id = ? AND status = 'confirmed'");
$stmt->bind_param("i", $currentUser['id']);
$stmt->execute();
$activeBookings = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// 📊 Total bookings
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE user_id = ?");
$stmt->bind_param("i", $currentUser['id']);
$stmt->execute();
$totalBookings = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// 🕒 Recent bookings
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
$recentBookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Dashboard</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{background:#f1f5f9;display:flex;}

/* SIDEBAR */
.sidebar{
    width:240px;background:#1e293b;color:white;
    position:fixed;height:100%;
}
.sidebar-header{
    padding:20px;border-bottom:1px solid rgba(255,255,255,0.1);
}
.sidebar-menu{padding:15px;}
.sidebar-menu a{
    display:flex;align-items:center;gap:10px;
    padding:12px;border-radius:8px;
    color:#94a3b8;text-decoration:none;
    margin-bottom:5px;
}
.sidebar-menu a:hover{background:#334155;color:white;}
.sidebar-menu a.active{background:#f97316;color:white;}

/* MAIN */
.main{
    margin-left:240px;padding:30px;width:100%;
}

/* HEADER */
.header h1{font-size:22px;color:#1e293b;}
.header p{color:#64748b;margin-top:4px;}

/* STATS */
.stats{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:20px;margin-top:25px;
}
.card{
    background:white;padding:20px;
    border-radius:12px;
    display:flex;align-items:center;gap:15px;
    box-shadow:0 2px 6px rgba(0,0,0,0.05);
}
.icon{
    width:45px;height:45px;border-radius:10px;
    display:flex;align-items:center;justify-content:center;
    font-size:18px;
}
.card h3{font-size:13px;color:#64748b;}
.value{font-size:22px;font-weight:bold;color:#1e293b;}

/* RECENT */
.section{
    margin-top:30px;
    background:white;border-radius:12px;
    box-shadow:0 2px 6px rgba(0,0,0,0.05);
}
.section-header{
    padding:15px 20px;border-bottom:1px solid #eee;
    font-weight:bold;
}
.booking{
    display:flex;justify-content:space-between;
    align-items:center;padding:15px 20px;
    border-bottom:1px solid #f1f5f9;
}
.booking:last-child{border-bottom:none;}

.left{display:flex;gap:15px;align-items:center;}
.left img{
    width:55px;height:55px;border-radius:8px;object-fit:cover;
}
.name{font-weight:600;}
.date{font-size:12px;color:#64748b;}

.price{font-weight:bold;color:#f97316;}

.badge{
    padding:4px 10px;border-radius:20px;
    font-size:11px;font-weight:600;
}
.confirmed{background:#dcfce7;color:#16a34a;}
.pending{background:#fef9c3;color:#ca8a04;}
.cancelled{background:#fee2e2;color:#dc2626;}

.empty{
    text-align:center;padding:30px;color:#94a3b8;
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-header">
        <h2>Bhatbhatey</h2>
    </div>
    <div class="sidebar-menu">
        <a href="user-dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="../vehicles.php"><i class="fas fa-car"></i> Vehicles</a>
        <a href="../my-bookings.php"><i class="fas fa-calendar"></i> Bookings</a>
        <a href="../profile.php"><i class="fas fa-user"></i> Profile</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<!-- MAIN -->
<div class="main">

    <div class="header">
        <h1>Welcome, <?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?> 👋</h1>
        <p>Here’s your rental overview</p>
    </div>

    <!-- STATS -->
    <div class="stats">

        <div class="card">
            <div class="icon" style="background:#eff6ff;color:#3b82f6;">
                <i class="fas fa-car"></i>
            </div>
            <div>
                <h3>Available Vehicles</h3>
                <div class="value"><?php echo $availableVehicles; ?></div>
            </div>
        </div>

        <div class="card">
            <div class="icon" style="background:#f0fdf4;color:#10b981;">
                <i class="fas fa-key"></i>
            </div>
            <div>
                <h3>Active Rentals</h3>
                <div class="value"><?php echo $activeBookings; ?></div>
            </div>
        </div>

        <div class="card">
            <div class="icon" style="background:#fff7ed;color:#f97316;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div>
                <h3>Total Bookings</h3>
                <div class="value"><?php echo $totalBookings; ?></div>
            </div>
        </div>

    </div>

    <!-- RECENT BOOKINGS -->
    <div class="section">
        <div class="section-header">Recent Activity</div>

        <?php if (!empty($recentBookings)): ?>
            <?php foreach ($recentBookings as $b): ?>

                <div class="booking">
                    <div class="left">
                        <img src="<?php echo htmlspecialchars($b['image']); ?>">
                        <div>
                            <div class="name"><?php echo htmlspecialchars($b['name']); ?></div>
                            <div class="date">
                                <?php echo $b['start_date']; ?> → <?php echo $b['end_date']; ?>
                            </div>
                        </div>
                    </div>

                    <div style="text-align:right;">
                        <div class="price">NPR <?php echo number_format($b['total_price']); ?></div>

                        <?php $cls = strtolower($b['status']); ?>

                        <span class="badge <?php echo $cls; ?>">
                            <?php echo ucfirst($b['status']); ?>
                        </span>
                    </div>
                </div>

            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty">
                <h3>No bookings yet</h3>
                <p>Start renting 🚗</p>
            </div>
        <?php endif; ?>

    </div>

</div>

</body>
</html>