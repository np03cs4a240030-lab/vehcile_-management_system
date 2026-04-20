<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../admin/admin-login.php');
}

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_status') {
        $user_id    = (int)$_POST['user_id'];
        $cur_status = $_POST['current_status'];
        $new_status = ($cur_status === 'active') ? 'disabled' : 'active';

        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'user'");
        $stmt->bind_param("si", $new_status, $user_id);
        $stmt->execute();
        redirect('admin-users.php');
    }
}

// Search
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

if ($search) {
    $like = "%$search%";
    $stmt = $conn->prepare("SELECT * FROM users WHERE role = 'user' AND (name LIKE ? OR email LIKE ?) ORDER BY created_at DESC");
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $users = $stmt->get_result();
} else {
    $users = $conn->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Admin | Bhatbhatey Rental</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #f1f5f9; display: flex; min-height: 100vh; }

        .sidebar {
            width: 240px; background: #1e293b; color: white;
            display: flex; flex-direction: column; min-height: 100vh;
            position: fixed; top: 0; left: 0;
        }
        .sidebar-logo { padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; gap: 12px; }
        .sidebar-logo img { height: 36px; }
        .sidebar-logo span { font-size: 13px; color: #94a3b8; font-weight: 600; }
        .sidebar-menu { padding: 16px 12px; flex: 1; display: flex; flex-direction: column; }
        .sidebar-menu a {
            display: flex; align-items: center; gap: 12px;
            padding: 11px 14px; border-radius: 8px; color: #94a3b8;
            text-decoration: none; font-size: 14px; font-weight: 500;
            margin-bottom: 4px; transition: all 0.2s;
        }
        .sidebar-menu a i { width: 18px; text-align: center; }
        .sidebar-menu a:hover { background: rgba(255,255,255,0.07); color: white; }
        .sidebar-menu a.active { background: #f97316; color: white; }
        .logout-link { margin-top: auto; }
        .logout-link a { color: #fca5a5 !important; }
        .logout-link a:hover { background: rgba(239,68,68,0.15) !important; }

        .main-content { margin-left: 240px; flex: 1; padding: 28px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-header h1 { font-size: 22px; font-weight: 700; color: #1e293b; }
        .page-header p { color: #64748b; font-size: 14px; margin-top: 3px; }

        .search-bar {
            display: flex; gap: 10px; margin-bottom: 22px;
            background: white; padding: 14px 18px;
            border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.07);
        }
        .search-bar input {
            flex: 1; border: 1px solid #e2e8f0; border-radius: 8px;
            padding: 9px 14px; font-size: 14px; outline: none; color: #1e293b;
        }
        .search-bar input:focus { border-color: #f97316; }
        .btn { padding: 9px 18px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 7px; transition: all 0.2s; text-decoration: none; }
        .btn-primary { background: #f97316; color: white; }
        .btn-primary:hover { background: #ea6c0a; }
        .btn-secondary { background: #f1f5f9; color: #475569; }
        .btn-secondary:hover { background: #e2e8f0; }

        .users-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
        .user-card {
            background: white; border-radius: 14px;
            padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.07);
            transition: all 0.2s;
        }
        .user-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
        .user-card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
        .user-avatar {
            width: 48px; height: 48px; border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 20px; flex-shrink: 0;
        }
        .user-name { font-weight: 700; font-size: 15px; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-email { font-size: 12px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-meta { background: #f8fafc; border-radius: 8px; padding: 12px; margin-bottom: 14px; }
        .user-meta-row { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #475569; margin-bottom: 7px; }
        .user-meta-row:last-child { margin-bottom: 0; }
        .user-meta-row i { width: 14px; color: #94a3b8; }
        .user-card-footer { border-top: 1px solid #f1f5f9; padding-top: 14px; }
        .toggle-btn {
            width: 100%; padding: 8px; border-radius: 8px;
            border: none; cursor: pointer; font-size: 13px; font-weight: 600;
            display: flex; align-items: center; justify-content: center; gap: 6px;
            transition: all 0.2s;
        }
        .toggle-active { background: #dcfce7; color: #16a34a; }
        .toggle-active:hover { background: #bbf7d0; }
        .toggle-disabled { background: #fee2e2; color: #dc2626; }
        .toggle-disabled:hover { background: #fecaca; }

        .empty-state { grid-column: 1/-1; text-align: center; padding: 60px; color: #94a3b8; }
        .empty-state i { font-size: 48px; margin-bottom: 14px; display: block; }

        @media (max-width: 1100px) { .users-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 700px) { .users-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="../assets/images/logo.png" alt="Logo">
        <span>Admin Panel</span>
    </div>
    <nav class="sidebar-menu">
        <a href="admin-dashboard.php"><i class="fas fa-gauge-high"></i> Dashboard</a>
        <a href="admin-vehicles.php"><i class="fas fa-car"></i> Vehicles</a>
        <a href="admin-bookings.php"><i class="fas fa-calendar-days"></i> Bookings</a>
        <a href="admin-users.php" class="active"><i class="fas fa-users"></i> Users</a>
        <div class="logout-link">
            <a href="../logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </div>
    </nav>
</aside>

<main class="main-content">

    <div class="page-header">
        <div>
            <h1>User Management</h1>
            <p>View and manage registered users</p>
        </div>
    </div>

    <!-- SEARCH -->
    <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Search by name or email..."
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-primary"><i class="fas fa-magnifying-glass"></i> Search</button>
        <?php if ($search): ?>
            <a href="admin-users.php" class="btn btn-secondary"><i class="fas fa-xmark"></i> Clear</a>
        <?php endif; ?>
    </form>

    <!-- USERS GRID -->
    <div class="users-grid">
        <?php if ($users->num_rows > 0): ?>
            <?php while ($user = $users->fetch_assoc()): ?>
                <div class="user-card">
                    <div class="user-card-header">
                        <div class="user-avatar"><i class="fas fa-user"></i></div>
                        <div style="min-width:0;">
                            <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                            <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                    </div>
                    <div class="user-meta">
                        <div class="user-meta-row">
                            <i class="fas fa-phone"></i>
                            <?php echo htmlspecialchars($user['phone_number']); ?>
                        </div>
                        <div class="user-meta-row">
                            <i class="fas fa-calendar"></i>
                            Joined <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                        </div>
                        <div class="user-meta-row">
                            <i class="fas fa-hashtag"></i>
                            User ID #<?php echo $user['id']; ?>
                        </div>
                    </div>
                    <div class="user-card-footer">
                        <form method="POST">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="current_status" value="<?php echo $user['status']; ?>">
                            <button type="submit"
                                class="toggle-btn <?php echo $user['status']==='active' ? 'toggle-active' : 'toggle-disabled'; ?>">
                                <?php if ($user['status'] === 'active'): ?>
                                    <i class="fas fa-circle-check"></i> Active — Click to Disable
                                <?php else: ?>
                                    <i class="fas fa-circle-xmark"></i> Disabled — Click to Enable
                                <?php endif; ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users-slash"></i>
                <div><?php echo $search ? 'No users match your search.' : 'No users registered yet.'; ?></div>
            </div>
        <?php endif; ?>
    </div>

</main>
</body>
</html>
