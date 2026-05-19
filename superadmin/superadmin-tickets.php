<?php
session_start();
require_once '../config.php';

if (!isLoggedIn() || !hasRole('super_admin')) {
    redirect('../admin/admin-login.php');
}

// Fetch all tickets with user names
$sql = "
    SELECT st.*, u.name as user_name 
    FROM support_tickets st 
    JOIN users u ON st.user_id = u.id 
    ORDER BY FIELD(st.status, 'open', 'in_progress', 'closed'), st.created_at DESC
";
$tickets = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets - Super Admin</title>
    
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --bg-dark: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.1);
            --success: #10b981;
            --danger: #ef4444;
        }

        body {
            background: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            margin: 0;
        }

        .dashboard-layout { display: flex; min-height: 100vh; }

        .sidebar {
            width: 240px; background: rgba(255,255,255,0.03);
            border-right: 1px solid rgba(255,255,255,0.07);
            display: flex; flex-direction: column; min-height: 100vh;
            position: fixed; top: 0; left: 0;
        }
        .sidebar-logo { padding: 22px 24px; border-bottom: 1px solid rgba(255,255,255,0.07); display: flex; align-items: center; gap: 12px; }
        .sidebar-logo img { height: 36px; }
        .sidebar-logo-text .title { font-size: 14px; font-weight: 700; color: white; }
        .sidebar-logo-text .sub { font-size: 11px; color: #a855f7; background: rgba(168,85,247,0.15); border: 1px solid rgba(168,85,247,0.3); border-radius: 20px; padding: 1px 8px; display: inline-block; margin-top: 2px; }
        
        .sidebar-menu { padding: 16px 12px; flex: 1; display: flex; flex-direction: column; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; padding: 11px 14px; border-radius: 10px; color: #94a3b8; text-decoration: none; font-size: 14px; font-weight: 500; margin-bottom: 4px; transition: all 0.2s; }
        .sidebar-menu a i { width: 18px; text-align: center; }
        .sidebar-menu a:hover { background: rgba(255,255,255,0.07); color: white; }
        .sidebar-menu a.active { background: linear-gradient(135deg, #9333ea, #7c3aed); color: white; }
        
        .logout-link { margin-top: auto; }
        .logout-link a { color: #fca5a5 !important; }
        .logout-link a:hover { background: rgba(239,68,68,0.1) !important; }

        .main-content { margin-left: 240px; width: calc(100% - 240px); padding: 28px; min-height: 100vh; box-sizing: border-box; overflow-x: hidden; }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-header h1 { font-size: 22px; font-weight: 700; color: white; }
        .page-header p { color: #94a3b8; font-size: 14px; margin-top: 3px; }

        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: capitalize; border: none; }
        .badge-open { background: rgba(234, 179, 8, 0.15); color: #eab308; }
        .badge-in_progress { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .badge-closed { background: rgba(148, 163, 184, 0.15); color: #94a3b8; }
        
        .ticket-list { background: var(--card-bg); border-radius: 12px; border: 1px solid var(--border); overflow: hidden; }
        .ticket-item { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; border-bottom: 1px solid var(--border); transition: background 0.2s; text-decoration: none; color: inherit; }
        .ticket-item:hover { background: rgba(255,255,255,0.03); }
        .ticket-item:last-child { border-bottom: none; }
        
        .ticket-main { flex: 1; }
        .ticket-title { font-size: 15px; font-weight: 700; color: white; margin-bottom: 6px; }
        .ticket-meta { font-size: 13px; color: var(--text-muted); display: flex; align-items: center; gap: 12px; }
        
        .ticket-action { padding: 8px 16px; border-radius: 8px; background: rgba(99, 102, 241, 0.1); color: #818cf8; font-size: 13px; font-weight: 600; text-decoration: none; transition: 0.2s; border: 1px solid rgba(99, 102, 241, 0.2); }
        .ticket-action:hover { background: rgba(99, 102, 241, 0.2); }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="../assets/images/logo.png" alt="Logo">
                <div class="sidebar-logo-text">
                    <div class="title">Bhatbhatey</div>
                    <div class="sub">Super Admin</div>
                </div>
            </div>
            <nav class="sidebar-menu">
                <a href="superadmin-dashboard.php"><i class="fas fa-gauge-high"></i> Dashboard</a>
                <a href="superadmin-users.php"><i class="fas fa-users"></i> Users</a>
                <a href="superadmin-admins.php"><i class="fas fa-user-shield"></i> Admins</a>
                <a href="superadmin-vehicles.php"><i class="fas fa-car"></i> Vehicles</a>
                <a href="superadmin-bookings.php"><i class="fas fa-calendar-days"></i> Bookings</a>
                <a href="superadmin-tickets.php" class="active"><i class="fas fa-ticket-alt"></i> Support Tickets</a>
                <a href="superadmin-settings.php"><i class="fas fa-gear"></i> Settings</a>
                <div class="logout-link">
                    <a href="../logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <div>
                    <h1>Support Tickets</h1>
                    <p>Manage and reply to user support requests.</p>
                </div>
            </div>

            <div class="ticket-list">
                <?php if ($tickets->num_rows > 0): ?>
                    <?php while($ticket = $tickets->fetch_assoc()): ?>
                        <div class="ticket-item">
                            <div class="ticket-main">
                                <div class="ticket-title">
                                    <?php echo htmlspecialchars($ticket['subject']); ?>
                                </div>
                                <div class="ticket-meta">
                                    <span><i class="fas fa-hashtag"></i> TKT-<?php echo str_pad($ticket['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($ticket['user_name']); ?></span>
                                    <span><i class="far fa-clock"></i> <?php echo date('M d, Y h:i A', strtotime($ticket['created_at'])); ?></span>
                                    <span class="badge badge-<?php echo $ticket['status']; ?>">
                                        <?php echo str_replace('_', ' ', $ticket['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <a href="superadmin-ticket-view.php?id=<?php echo $ticket['id']; ?>" class="ticket-action">View & Reply</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                        <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;"></i>
                        <h3>No Support Tickets</h3>
                        <p>You're all caught up!</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
