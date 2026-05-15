<?php
require_once 'config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'cancel_booking') {
        $bid = (int)$_POST['booking_id'];
        $stmt = $conn->prepare("UPDATE bookings SET status='cancelled' WHERE id=? AND user_id=? AND status='pending'");
        $stmt->bind_param("ii", $bid, $currentUser['id']);
        $stmt->execute();
        redirect('my-bookings.php?msg=cancelled');
    }
}

$allowed = ['all','pending','approved','confirmed','ongoing','completed','cancelled'];
$filter  = (isset($_GET['status']) && in_array($_GET['status'], $allowed)) ? $_GET['status'] : 'all';

$countQ = $conn->prepare("SELECT status, COUNT(*) AS c FROM bookings WHERE user_id=? GROUP BY status");
$countQ->bind_param("i", $currentUser['id']); $countQ->execute();
$counts = [];
$countRes = $countQ->get_result();
while($r = $countRes->fetch_assoc()) { $counts[$r['status']] = $r['c']; }
$totalCount = array_sum($counts);

$sql = "SELECT b.*, v.name AS vehicle_name, v.type AS vehicle_type, v.image AS vehicle_image, v.location AS vehicle_location FROM bookings b JOIN vehicles v ON b.vehicle_id = v.id WHERE b.user_id = ?";
if ($filter !== 'all') {
    $stmt = $conn->prepare($sql . " AND b.status = ? ORDER BY b.created_at DESC");
    $stmt->bind_param("is", $currentUser['id'], $filter);
} else {
    $stmt = $conn->prepare($sql . " ORDER BY b.created_at DESC");
    $stmt->bind_param("i", $currentUser['id']);
}
$stmt->execute();
$userBookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Bhatbhatey Rental</title>
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
        .page-header h1{font-size:24px;font-weight:800;color:var(--dark);}
        .page-header p{color:var(--slate);margin-top:4px;font-size:14px;}

        /* Flash */
        .flash{padding:13px 18px;border-radius:10px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600;background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;}

        /* Filter tabs */
        .filter-tabs{display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap;}
        .filter-tab{padding:8px 14px;border-radius:9px;font-size:13px;font-weight:700;border:1px solid var(--border);background:white;color:var(--slate);display:flex;align-items:center;gap:6px;transition:0.2s;}
        .filter-tab:hover{border-color:var(--primary);color:var(--primary);}
        .filter-tab.active{background:var(--primary);color:white;border-color:var(--primary);box-shadow:0 4px 12px rgba(249,115,22,0.2);}
        .tab-count{background:rgba(0,0,0,0.1);border-radius:100px;padding:1px 7px;font-size:11px;}
        .filter-tab.active .tab-count{background:rgba(255,255,255,0.25);}

        /* Booking card */
        .b-card{background:white;border-radius:16px;border:1px solid var(--border);margin-bottom:18px;overflow:hidden;transition:0.2s;}
        .b-card:hover{box-shadow:0 8px 24px rgba(0,0,0,0.07);}

        .b-head{padding:13px 20px;background:#f8fafc;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;}
        .b-id{font-weight:800;font-size:13px;color:var(--dark);display:flex;align-items:center;gap:6px;}
        .b-id i{color:#94a3b8;font-size:11px;}
        .addons{display:flex;gap:6px;}
        .addon-tag{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:100px;font-size:11px;font-weight:700;background:var(--primary-light);color:#c2410c;border:1px solid #fed7aa;}

        .b-body{padding:20px;display:grid;grid-template-columns:130px 1fr;gap:20px;align-items:center;}
        .b-img{width:100%;height:90px;object-fit:cover;border-radius:10px;border:1px solid var(--border);}
        .b-img-fallback{width:100%;height:90px;border-radius:10px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:28px;}

        .b-vehicle-name{font-size:17px;font-weight:800;color:var(--dark);margin-bottom:3px;}
        .b-vehicle-type{font-size:12px;color:var(--slate);margin-bottom:14px;display:flex;align-items:center;gap:5px;}
        .b-meta{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;}
        .meta-label{font-size:11px;color:#94a3b8;margin-bottom:3px;display:flex;align-items:center;gap:4px;}
        .meta-value{font-size:13px;font-weight:700;color:var(--dark);}

        .b-foot{padding:13px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;}
        .b-foot-left{display:flex;align-items:center;gap:20px;flex-wrap:wrap;}
        .foot-meta span{font-size:11px;color:#94a3b8;display:block;margin-bottom:2px;}
        .foot-meta p{font-size:13px;font-weight:700;color:var(--dark);display:flex;align-items:center;gap:6px;}
        .foot-meta p i{color:#94a3b8;font-size:11px;}
        .b-price{font-size:17px;font-weight:800;color:var(--primary);}
        .b-actions{display:flex;align-items:center;gap:10px;}

        .status-pill{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:100px;font-size:12px;font-weight:700;}
        .sp-pending{background:#fef9c3;color:#ca8a04;}
        .sp-approved,.sp-confirmed{background:#d1fae5;color:#065f46;}
        .sp-ongoing{background:#fce7f3;color:#be185d;}
        .sp-completed{background:#dbeafe;color:#1d4ed8;}
        .sp-cancelled{background:#fee2e2;color:#dc2626;}

        .btn-cancel{padding:7px 14px;border-radius:9px;font-size:13px;font-weight:700;background:#fee2e2;color:#dc2626;border:1px solid #fecaca;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:0.2s;}
        .btn-cancel:hover{background:#fecaca;}
        .btn-view-b{padding:7px 14px;border-radius:9px;font-size:13px;font-weight:700;background:#eff6ff;color:#3b82f6;border:1px solid #bfdbfe;display:inline-flex;align-items:center;gap:5px;transition:0.2s;}
        .btn-view-b:hover{background:#dbeafe;}

        .empty-state{text-align:center;padding:60px 20px;background:white;border-radius:16px;border:1px solid var(--border);}
        .empty-icon{width:70px;height:70px;background:var(--primary-light);border-radius:18px;display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:28px;margin:0 auto 18px;}
        .empty-state h3{font-size:18px;color:var(--dark);margin-bottom:8px;}
        .empty-state p{color:var(--slate);font-size:14px;}
        .btn-browse{display:inline-flex;align-items:center;gap:7px;margin-top:20px;padding:11px 24px;background:var(--primary);color:white;border-radius:10px;font-weight:700;font-size:14px;}

        @media(max-width:700px){
            .b-body{grid-template-columns:1fr;}
            .b-meta{grid-template-columns:repeat(2,1fr);}
        }
    </style>
</head>
<body>

<div class="sidebar">
    <a href="index.php" class="sidebar-logo">
        <img src="nobglogo.png" alt="Bhatbhatey"
             onerror="this.style.display='none'; document.querySelector('.logo-fallback').style.display='flex';">
        <div class="logo-fallback"><i class="fas fa-car"></i></div>
        <div class="sidebar-logo-text">
            <span class="lt-name">Bhatbhatey</span>
            <span class="lt-sub">Rental</span>
        </div>
    </a>

    <div class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <a href="user/user-dashboard.php"><i class="fas fa-gauge-high"></i> Dashboard</a>
        <a href="vehicles.php"><i class="fas fa-car"></i> Browse Vehicles</a>
        <a href="my-bookings.php" class="active"><i class="fas fa-calendar-check"></i> My Bookings</a>
        <div class="nav-section-label">Account</div>
        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
        <a href="logout.php" class="danger"><i class="fas fa-right-from-bracket"></i> Logout</a>
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
        <h1>My Bookings</h1>
        <p>Track and manage your vehicle rental history</p>
    </div>

    <?php if(isset($_GET['msg']) && $_GET['msg']==='cancelled'): ?>
    <div class="flash"><i class="fas fa-circle-check"></i> Booking cancelled successfully.</div>
    <?php endif; ?>

    <div class="filter-tabs">
        <?php
        $tabs = [
            'all'       => ['icon'=>'fa-list',           'label'=>'All',       'count'=>$totalCount],
            'pending'   => ['icon'=>'fa-clock',          'label'=>'Pending',   'count'=>$counts['pending']??0],
            'approved'  => ['icon'=>'fa-circle-check',   'label'=>'Approved',  'count'=>($counts['approved']??0)+($counts['confirmed']??0)],
            'ongoing'   => ['icon'=>'fa-car-side',       'label'=>'Ongoing',   'count'=>$counts['ongoing']??0],
            'completed' => ['icon'=>'fa-flag-checkered', 'label'=>'Completed', 'count'=>$counts['completed']??0],
            'cancelled' => ['icon'=>'fa-ban',            'label'=>'Cancelled', 'count'=>$counts['cancelled']??0],
        ];
        foreach($tabs as $key=>$tab): ?>
        <a href="?status=<?php echo $key; ?>" class="filter-tab <?php echo $filter===$key?'active':''; ?>">
            <i class="fas <?php echo $tab['icon']; ?>"></i>
            <?php echo $tab['label']; ?>
            <span class="tab-count"><?php echo $tab['count']; ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if(count($userBookings) > 0): ?>
        <?php foreach($userBookings as $b):
            $st = $b['status'];
            $days = $b['total_days'] ?? ((new DateTime($b['start_date']))->diff(new DateTime($b['end_date']))->days);
            $statusIcons = ['pending'=>'fa-clock','approved'=>'fa-circle-check','confirmed'=>'fa-circle-check','ongoing'=>'fa-car-side','completed'=>'fa-flag-checkered','cancelled'=>'fa-ban'];
            $si = $statusIcons[$st] ?? 'fa-clock';
            $typeIcons = ['Car'=>'fa-car','Bike'=>'fa-motorcycle','Scooter'=>'fa-person-biking'];
            $ti = $typeIcons[$b['vehicle_type']] ?? 'fa-car';
        ?>
        <div class="b-card">
            <div class="b-head">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="b-id"><i class="fas fa-hashtag"></i><?php echo str_pad($b['id'],5,'0',STR_PAD_LEFT); ?></div>
                    <div class="addons">
                        <?php if(!empty($b['hire_driver'])): ?><span class="addon-tag"><i class="fas fa-user-tie"></i> Driver</span><?php endif; ?>
                        <?php if(!empty($b['pickup_service'])): ?><span class="addon-tag"><i class="fas fa-van-shuttle"></i> Pickup/Drop</span><?php endif; ?>
                    </div>
                </div>
                <span class="status-pill sp-<?php echo $st; ?>">
                    <i class="fas <?php echo $si; ?>"></i> <?php echo ucfirst($st); ?>
                </span>
            </div>

            <div class="b-body">
                <?php if(!empty($b['vehicle_image'])): ?>
                    <img src="<?php echo htmlspecialchars($b['vehicle_image']); ?>"
                         alt="<?php echo htmlspecialchars($b['vehicle_name']); ?>"
                         class="b-img"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                    <div class="b-img-fallback" style="display:none;"><i class="fas fa-car"></i></div>
                <?php else: ?>
                    <div class="b-img-fallback"><i class="fas fa-car"></i></div>
                <?php endif; ?>

                <div>
                    <div class="b-vehicle-name"><?php echo htmlspecialchars($b['vehicle_name']); ?></div>
                    <div class="b-vehicle-type"><i class="fas <?php echo $ti; ?>"></i> <?php echo htmlspecialchars($b['vehicle_type']); ?></div>
                    <div class="b-meta">
                        <div>
                            <div class="meta-label"><i class="fas fa-calendar"></i> Rental Period</div>
                            <div class="meta-value"><?php echo date('M d',strtotime($b['start_date'])); ?> <i class="fas fa-arrow-right" style="font-size:10px;color:#94a3b8;"></i> <?php echo date('M d, Y',strtotime($b['end_date'])); ?></div>
                        </div>
                        <div>
                            <div class="meta-label"><i class="fas fa-clock"></i> Duration</div>
                            <div class="meta-value"><?php echo $days; ?> day<?php echo $days>1?'s':''; ?></div>
                        </div>
                        <div>
                            <div class="meta-label"><i class="fas fa-map-pin"></i> Pickup</div>
                            <div class="meta-value"><?php echo htmlspecialchars($b['pickup_location'] ?? $b['vehicle_location'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="b-foot">
                <div class="b-foot-left">
                    <div class="foot-meta">
                        <span>Payment</span>
                        <p><i class="fas fa-<?php echo strtolower($b['payment_method'])==='online'?'credit-card':'money-bill-wave'; ?>"></i> <?php echo htmlspecialchars($b['payment_method']); ?></p>
                    </div>
                    <div class="foot-meta">
                        <span>Booked on</span>
                        <p><i class="fas fa-calendar-plus"></i> <?php echo date('M d, Y',strtotime($b['created_at'])); ?></p>
                    </div>
                </div>
                <div class="b-actions">
                    <span class="b-price">NPR <?php echo number_format($b['total_price']); ?></span>
                    <?php if($st==='pending'): ?>
                    <form method="POST" onsubmit="return confirm('Cancel this booking?');">
                        <input type="hidden" name="action" value="cancel_booking">
                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                        <button type="submit" class="btn-cancel"><i class="fas fa-xmark"></i> Cancel</button>
                    </form>
                    <?php endif; ?>
                    <a href="booking-confirmation.php?id=<?php echo $b['id']; ?>" class="btn-view-b"><i class="fas fa-eye"></i> View</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-calendar-xmark"></i></div>
        <h3><?php echo $filter!=='all'?'No '.ucfirst($filter).' Bookings':'No Bookings Yet'; ?></h3>
        <p><?php echo $filter!=='all'?'You have no '.$filter.' bookings at the moment.':'You have not made any bookings yet. Explore our fleet!'; ?></p>
        <?php if($filter==='all'): ?>
        <a href="vehicles.php" class="btn-browse"><i class="fas fa-car"></i> Browse Vehicles</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

</body>
</html>