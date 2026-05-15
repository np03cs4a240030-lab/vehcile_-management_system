<?php
require_once '../config.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../superadmin/superadmin-login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = (int)$_POST['user_id'];

    switch ($_POST['action']) {
        case 'toggle_status':
            // FIX: read current_status correctly, toggle properly
            $cur = $_POST['current_status'];
            $new = ($cur === 'active') ? 'disabled' : 'active';
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role != 'super_admin'");
            $stmt->bind_param("si", $new, $user_id);
            $stmt->execute();
            break;

        case 'delete':
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            break;
    }
    redirect('superadmin-users.php');
}

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

if ($search) {
    $like = "%$search%";
    $stmt = $conn->prepare("SELECT * FROM users WHERE role IN ('user','admin') AND (name LIKE ? OR email LIKE ?) ORDER BY created_at DESC");
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $users = $stmt->get_result();
} else {
    $users = $conn->query("SELECT * FROM users WHERE role IN ('user','admin') ORDER BY created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Super Admin | Bhatbhatey Rental</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); min-height: 100vh; display: flex; }

        .sidebar { width: 240px; background: rgba(255,255,255,0.03); border-right: 1px solid rgba(255,255,255,0.07); display: flex; flex-direction: column; min-height: 100vh; position: fixed; top: 0; left: 0; }
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

        .main-content { margin-left: 240px; flex: 1; padding: 28px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-header h1 { font-size: 22px; font-weight: 700; color: white; }
        .page-header p { color: #94a3b8; font-size: 14px; margin-top: 3px; }

        .btn { padding: 10px 18px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 7px; transition: all 0.2s; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg,#9333ea,#7c3aed); color: white; }
        .btn-primary:hover { opacity: 0.9; }

        .search-bar { display: flex; gap: 10px; margin-bottom: 22px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); padding: 14px 18px; border-radius: 12px; }
        .search-bar input { flex: 1; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 9px 14px; font-size: 14px; outline: none; background: rgba(255,255,255,0.07); color: white; }
        .search-bar input::placeholder { color: #475569; }
        .search-bar input:focus { border-color: #a855f7; }
        .btn-search { padding: 9px 16px; background: rgba(168,85,247,0.2); color: #c084fc; border: 1px solid rgba(168,85,247,0.3); border-radius: 8px; cursor: pointer; font-size: 14px; display: flex; align-items: center; gap: 7px; transition: all 0.2s; }
        .btn-search:hover { background: rgba(168,85,247,0.35); }
        .btn-clear { padding: 9px 14px; background: rgba(255,255,255,0.05); color: #94a3b8; border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; cursor: pointer; font-size: 14px; text-decoration: none; display: flex; align-items: center; }
        .btn-clear:hover { background: rgba(255,255,255,0.1); }

        .table-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: rgba(255,255,255,0.03); padding: 12px 16px; text-align: left; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid rgba(255,255,255,0.06); }
        tbody tr { border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.15s; }
        tbody tr:hover { background: rgba(255,255,255,0.03); }
        tbody tr:last-child { border-bottom: none; }
        td { padding: 13px 16px; font-size: 14px; color: #e2e8f0; vertical-align: middle; }
        .td-secondary { font-size: 12px; color: #64748b; }

        .badge { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; border: none; cursor: pointer; }
        .badge-success { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.2); }
        .badge-danger  { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.2); }
        .badge-primary { background: rgba(59,130,246,0.15); color: #60a5fa; border: 1px solid rgba(59,130,246,0.2); }
        .badge-purple  { background: rgba(168,85,247,0.15); color: #c084fc; border: 1px solid rgba(168,85,247,0.2); }

        .action-btns { display: flex; gap: 8px; }
        .btn-edit { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 7px; background: rgba(59,130,246,0.1); color: #60a5fa; border: 1px solid rgba(59,130,246,0.2); text-decoration: none; font-size: 13px; transition: all 0.2s; }
        .btn-edit:hover { background: rgba(59,130,246,0.2); }
        .btn-delete { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 7px; background: rgba(239,68,68,0.1); color: #f87171; border: 1px solid rgba(239,68,68,0.2); font-size: 13px; cursor: pointer; transition: all 0.2s; }
        .btn-delete:hover { background: rgba(239,68,68,0.2); }

        .empty-state { text-align: center; padding: 60px; color: #94a3b8; }
        .empty-state i { font-size: 48px; margin-bottom: 12px; display: block; }
    </style>
</head>
<body>

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
        <a href="superadmin-users.php" class="active"><i class="fas fa-users"></i> Users</a>
        <a href="superadmin-admins.php"><i class="fas fa-user-shield"></i> Admins</a>
        <a href="superadmin-vehicles.php"><i class="fas fa-car"></i> Vehicles</a>
        <a href="superadmin-bookings.php"><i class="fas fa-calendar-days"></i> Bookings</a>
        <a href="superadmin-settings.php"><i class="fas fa-gear"></i> Settings</a>
        <div class="logout-link">
            <a href="../logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </div>
    </nav>
</aside>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1>User Management</h1>
            <p>View and manage all registered users</p>
        </div>
        <a href="superadmin-add-user.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add User
        </a>
    </div>

    <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Search by name or email..."
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn-search"><i class="fas fa-magnifying-glass"></i> Search</button>
        <?php if ($search): ?>
            <a href="superadmin-users.php" class="btn-clear"><i class="fas fa-xmark"></i></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users->num_rows > 0): ?>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td class="td-secondary">#<?php echo $user['id']; ?></td>
                            <td style="font-weight:600;"><?php echo htmlspecialchars($user['name']); ?></td>
                            <td class="td-secondary"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="td-secondary"><?php echo htmlspecialchars($user['phone_number']); ?></td>
                            <td>
                                <span class="badge <?php echo $user['role']==='admin'?'badge-purple':'badge-primary'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $user['status']; ?>">
                                    <button type="submit"
                                        class="badge <?php echo $user['status']==='active'?'badge-success':'badge-danger'; ?>">
                                        <?php echo $user['status']==='active' ? 'Active' : 'Disabled'; ?>
                                    </button>
                                </form>
                            </td>
                            <td class="td-secondary"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="action-btns">
                                    <a href="superadmin-edit-user.php?id=<?php echo $user['id']; ?>" class="btn-edit">
                                        <i class="fas fa-pen"></i> Edit
                                    </a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user permanently?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn-delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="fas fa-users-slash"></i>
                                <?php echo $search ? 'No users match your search.' : 'No users found.'; ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
