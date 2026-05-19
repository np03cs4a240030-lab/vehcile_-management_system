<?php
session_start();
require_once '../config.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('admin-login.php');
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
    <title>Support Tickets - Admin</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: capitalize; }
        .badge-open { background: #fef9c3; color: #ca8a04; }
        .badge-in_progress { background: #dbeafe; color: #1d4ed8; }
        .badge-closed { background: #f1f5f9; color: #64748b; }
        
        .ticket-list { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .ticket-item { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; border-bottom: 1px solid #e2e8f0; transition: background 0.2s; text-decoration: none; color: inherit; }
        .ticket-item:hover { background: #f8fafc; }
        .ticket-item:last-child { border-bottom: none; }
        
        .ticket-main { flex: 1; }
        .ticket-title { font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
        .ticket-meta { font-size: 13px; color: #64748b; display: flex; align-items: center; gap: 12px; }
        
        .ticket-action { padding: 8px 16px; border-radius: 8px; background: #eff6ff; color: #3b82f6; font-size: 13px; font-weight: 600; text-decoration: none; transition: 0.2s; }
        .ticket-action:hover { background: #dbeafe; }

        /* sidebar styles */
        .sidebar { width: 240px; background: #1e293b; color: white; display: flex; flex-direction: column; min-height: 100vh; position: fixed; top: 0; left: 0; }
        .sidebar-logo { padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; gap: 12px; }
        .sidebar-logo img { height: 36px; }
        .sidebar-logo span { font-size: 13px; color: #94a3b8; font-weight: 600; }
        .sidebar-menu { padding: 16px 12px; flex: 1; display: flex; flex-direction: column; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; padding: 11px 14px; border-radius: 8px; color: #94a3b8; text-decoration: none; font-size: 14px; font-weight: 500; margin-bottom: 4px; transition: all 0.2s; }
        .sidebar-menu a i { width: 18px; text-align: center; font-size: 15px; }
        .sidebar-menu a:hover { background: rgba(255,255,255,0.07); color: white; }
        .sidebar-menu a.active { background: #f97316; color: white; }
        .sidebar-menu .logout-link { margin-top: auto; }
        .sidebar-menu .logout-link a { color: #fca5a5; }
        .sidebar-menu .logout-link a:hover { background: rgba(239,68,68,0.15); color: #fca5a5; }
    </style>
</head>
<body style="background: var(--brand-light-gray);">
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="../assets/images/logo.png" alt="Logo">
                <span>Admin Panel</span>
            </div>
            <nav class="sidebar-menu">
                <a href="admin-dashboard.php"><i class="fas fa-gauge-high"></i> Dashboard</a>
                <a href="admin-vehicles.php"><i class="fas fa-car"></i> Vehicles</a>
                <a href="admin-bookings.php"><i class="fas fa-calendar-days"></i> Bookings</a>
                <a href="admin-tickets.php" class="active"><i class="fas fa-ticket-alt"></i> Support Tickets</a>
                <a href="admin-users.php"><i class="fas fa-users"></i> Users</a>
                <a href="admin-change-password.php"><i class="fas fa-key"></i> Change Password</a>
                <div class="logout-link">
                    <a href="../logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
                </div>
            </nav>
        </aside>
        
        <main class="main-content">
            <div class="content-header">
                <div>
                    <h1>Support Tickets</h1>
                    <p style="color: var(--text-secondary);">Manage and reply to user support requests.</p>
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
                                <a href="admin-ticket-view.php?id=<?php echo $ticket['id']; ?>" class="ticket-action">View & Reply</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="padding: 40px; text-align: center; color: #64748b;">
                        <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                        <h3>No Support Tickets</h3>
                        <p>You're all caught up!</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
