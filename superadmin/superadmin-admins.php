<?php
require_once '../config.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../superadmin/superadmin-login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = (int)$_POST['user_id'];

    switch ($_POST['action']) {
        case 'toggle_status':
            $cur = $_POST['current_status'];
            $new = ($cur === 'active') ? 'disabled' : 'active';
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'admin'");
            $stmt->bind_param("si", $new, $user_id);
            $stmt->execute();
            break;

        case 'delete':
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            break;
    }
    redirect('superadmin-admins.php');
}

// FIX: only fetch admins, not users
$admins = $conn->query("SELECT * FROM users WHERE role = 'admin' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - Super Admin | Bhatbhatey Rental</title>
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
        .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }

        .table-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: rgba(255,255,255,0.03); padding: 12px 16px; text-align: left; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid rgba(255,255,255,0.06); }
        tbody tr { border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.15s; }
        tbody tr:hover { background: rgba(255,255,255,0.03); }
        tbody tr:last-child { border-bottom: none; }
        td { padding: 13px 16px; font-size: 14px; color: #e2e8f0; vertical-align: middle; }
        .td-secondary { font-size: 12px; color: #64748b; }

        .badge { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; cursor: pointer; border: none; }
        .badge-success { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.2); }
        .badge-danger  { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.2); }

        .action-btns { display: flex; gap: 8px; align-items: center; }
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
        <a href="superadmin-users.php"><i class="fas fa-users"></i> Users</a>
        <a href="superadmin-admins.php" class="active"><i class="fas fa-user-shield"></i> Admins</a>
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
            <h1>Admin Management</h1>
            <p>Manage administrator accounts</p>
        </div>
        <a href="superadmin-add-user.php?role=admin" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Admin
        </a>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($admins->num_rows > 0): ?>
                    <?php while ($admin = $admins->fetch_assoc()): ?>
                        <tr>
                            <td class="td-secondary">#<?php echo $admin['id']; ?></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px; font-weight:600;">
                                    <i class="fas fa-user-shield" style="color:#a855f7;"></i>
                                    <?php echo htmlspecialchars($admin['name']); ?>
                                </div>
                            </td>
                            <td class="td-secondary"><?php echo htmlspecialchars($admin['email']); ?></td>
                            <td class="td-secondary"><?php echo htmlspecialchars($admin['phone_number']); ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?php echo $admin['id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $admin['status']; ?>">
                                    <button type="submit"
                                        class="badge <?php echo $admin['status']==='active'?'badge-success':'badge-danger'; ?>">
                                        <?php echo $admin['status']==='active' ? 'Active' : 'Disabled'; ?>
                                    </button>
                                </form>
                            </td>
                            <td class="td-secondary"><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                            <td>
                                <div class="action-btns">
                                    <a href="superadmin-edit-user.php?id=<?php echo $admin['id']; ?>" class="btn-edit">
                                        <i class="fas fa-pen"></i> Edit
                                    </a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this admin permanently?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo $admin['id']; ?>">
                                        <button type="submit" class="btn-delete">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="fas fa-user-shield"></i>
                                No admins found. Add one above.
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
