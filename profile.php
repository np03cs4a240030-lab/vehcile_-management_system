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

        /* Sidebar footer */
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

        /* PROFILE CARD
           Critical: NO overflow:hidden on outer card — it clips the avatar.
           overflow:hidden only on the banner itself. */
        .profile-card{
            background:white;border-radius:20px;
            border:1px solid var(--border);
            box-shadow:0 4px 20px rgba(0,0,0,0.06);
        }
        .profile-banner{
            height:140px;border-radius:20px 20px 0 0;
            background:linear-gradient(135deg,#0f172a 0%,#1e293b 55%,#334155 100%);
            position:relative;overflow:hidden;
        }
        .profile-banner::before{
            content:'';position:absolute;inset:0;
            background:
                radial-gradient(circle at 10% 70%, rgba(249,115,22,0.22) 0%, transparent 50%),
                radial-gradient(circle at 90% 10%, rgba(249,115,22,0.13) 0%, transparent 45%);
        }

        /* Avatar row — overlaps the banner via negative margin */
        .profile-avatar-row{
            display:flex;align-items:flex-end;gap:20px;
            padding:0 32px;
            margin-top:-46px;
            margin-bottom:24px;
            position:relative;z-index:2;
        }
        .profile-avatar{
            width:90px;height:90px;
            background:linear-gradient(135deg,#f97316,#fb923c);
            border-radius:20px;
            display:flex;align-items:center;justify-content:center;
            font-size:36px;color:white;
            border:4px solid white;
            box-shadow:0 8px 28px rgba(249,115,22,0.30);
            flex-shrink:0;
        }
        .profile-meta{padding-bottom:6px;}
        .profile-name{font-size:22px;font-weight:800;color:var(--dark);line-height:1.2;margin-bottom:5px;}
        .profile-since{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--slate);}
        .profile-since i{color:var(--primary);font-size:11px;}

        .profile-body{padding:0 32px 32px;}
        .info-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px;margin-bottom:28px;}
        .info-item{background:#f8fafc;border:1px solid var(--border);border-radius:14px;padding:18px 20px;transition:0.2s;}
        .info-item:hover{border-color:var(--primary);background:var(--primary-light);}
        .info-label{display:flex;align-items:center;gap:7px;font-size:10.5px;color:var(--slate);font-weight:700;text-transform:uppercase;letter-spacing:0.6px;margin-bottom:10px;}
        .info-label i{color:var(--primary);font-size:12px;}
        .info-value{font-size:15px;font-weight:700;color:var(--dark);}
        .role-badge{display:inline-flex;align-items:center;gap:6px;background:#dbeafe;color:#1d4ed8;padding:5px 13px;border-radius:100px;font-size:12px;font-weight:700;}

        hr.divider{border:none;border-top:1px solid var(--border);margin:24px 0;}

        .profile-actions{display:flex;gap:12px;flex-wrap:wrap;}
        .btn-act-primary{background:var(--primary);color:white;padding:11px 22px;border-radius:10px;font-size:14px;font-weight:700;display:flex;align-items:center;gap:7px;}
        .btn-act-primary:hover{background:#ea6c09;color:white;}
        .btn-act-secondary{background:white;color:var(--slate);padding:11px 22px;border-radius:10px;font-size:14px;font-weight:700;border:1px solid var(--border);display:flex;align-items:center;gap:7px;}
        .btn-act-secondary:hover{border-color:var(--primary);color:var(--primary);}

        @media(max-width:700px){
            .info-grid{grid-template-columns:1fr;}
            .profile-avatar-row{padding:0 20px;}
            .profile-body{padding:0 20px 24px;}
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
        <a href="my-bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a>
        <div class="nav-section-label">Account</div>
        <a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a>
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
        <h1>My Profile</h1>
        <p>View and manage your account information</p>
    </div>

    <div class="profile-card">
        <div class="profile-banner"></div>

        <div class="profile-avatar-row">
            <div class="profile-avatar"><i class="fas fa-user"></i></div>
            <div class="profile-meta">
                <div class="profile-name"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                <div class="profile-since"><i class="fas fa-calendar-check"></i> Member since 2026</div>
            </div>
        </div>

        <div class="profile-body">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-user"></i> Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-envelope"></i> Email Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-phone"></i> Phone Number</div>
                    <div class="info-value"><?php echo htmlspecialchars($currentUser['phone_number']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-shield-halved"></i> Account Type</div>
                    <div class="info-value">
                        <span class="role-badge">
                            <i class="fas fa-circle-check"></i>
                            <?php echo ucfirst(htmlspecialchars($currentUser['role'])); ?>
                        </span>
                    </div>
                </div>
            </div>

            <hr class="divider">

            <div class="profile-actions">
                <a href="my-bookings.php" class="btn-act-secondary">
                    <i class="fas fa-calendar-check"></i> View My Bookings
                </a>
                <a href="vehicles.php" class="btn-act-primary">
                    <i class="fas fa-car"></i> Browse Vehicles
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>