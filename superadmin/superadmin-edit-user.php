<?php
require_once '../config.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../admin/admin-login.php');
}

$success = '';
$error   = '';

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id <= 0) redirect('superadmin-users.php');

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) redirect('superadmin-users.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $password = $_POST['password']      ?? '';
    $role     = $_POST['role']          ?? '';
    $status   = $_POST['status']        ?? '';

    if (empty($name) || empty($email) || empty($phone)) {
        $error = 'Name, Email, and Phone are required.';
    } else {
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $chk->bind_param("si", $email, $user_id);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = 'That email is already used by another account.';
        } else {
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $sql    = "UPDATE users SET name=?, email=?, phone_number=?, password=?, role=?, status=? WHERE id=?";
                $stmt   = $conn->prepare($sql);
                $stmt->bind_param("ssssssi", $name, $email, $phone, $hashed, $role, $status, $user_id);
            } else {
                $sql  = "UPDATE users SET name=?, email=?, phone_number=?, role=?, status=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssi", $name, $email, $phone, $role, $status, $user_id);
            }
            if ($stmt->execute()) {
                $success = 'User updated successfully!';
                $user['name'] = $name; $user['email'] = $email;
                $user['phone_number'] = $phone; $user['role'] = $role; $user['status'] = $status;
            } else {
                $error = 'Update failed. Please try again.';
            }
        }
    }
}
$initials = strtoupper(substr($user['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User – Super Admin | Bhatbhatey Rental</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
        body { background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%); min-height:100vh; display:flex; color:#e2e8f0; }

        .sidebar { width:240px; background:rgba(255,255,255,0.03); backdrop-filter:blur(20px); border-right:1px solid rgba(255,255,255,0.07); display:flex; flex-direction:column; min-height:100vh; position:fixed; top:0; left:0; z-index:100; }
        .sidebar-logo { padding:22px 24px; border-bottom:1px solid rgba(255,255,255,0.07); display:flex; align-items:center; gap:12px; text-decoration:none; }
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

        .main-content { margin-left:240px; flex:1; padding:32px; }
        .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:28px; }
        .top-bar-left h1 { font-size:22px; font-weight:700; color:white; }
        .top-bar-left p { color:#94a3b8; font-size:13px; margin-top:3px; display:flex; align-items:center; gap:5px; }
        .top-bar-left p a { color:#94a3b8; text-decoration:none; transition:0.2s; }
        .top-bar-left p a:hover { color:white; }
        .top-bar-left p span { color:#334155; }
        .btn-back { display:inline-flex; align-items:center; gap:8px; padding:9px 18px; border-radius:10px; font-size:14px; font-weight:600; text-decoration:none; background:rgba(255,255,255,0.07); color:#94a3b8; border:1px solid rgba(255,255,255,0.1); transition:0.2s; }
        .btn-back:hover { background:rgba(255,255,255,0.12); color:white; }

        .edit-layout { display:grid; grid-template-columns:1fr 300px; gap:24px; align-items:start; }
        .card { background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); border-radius:14px; overflow:hidden; }
        .card-header { padding:18px 22px; border-bottom:1px solid rgba(255,255,255,0.07); display:flex; align-items:center; gap:10px; }
        .card-header i { color:#a855f7; }
        .card-header h2 { font-size:15px; font-weight:700; color:white; }
        .card-body { padding:24px; }

        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .form-group { margin-bottom:16px; }
        .form-label { display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.6px; margin-bottom:7px; }
        .form-input, .form-select { width:100%; padding:11px 14px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.12); border-radius:9px; color:white; font-size:14px; outline:none; transition:0.2s; }
        .form-input::placeholder { color:#334155; }
        .form-input:focus, .form-select:focus { border-color:#9333ea; background:rgba(147,51,234,0.08); box-shadow:0 0 0 3px rgba(147,51,234,0.15); }
        .form-select { background-color:#1e293b; cursor:pointer; }
        .form-select option { background:#1e293b; }
        .hint { font-size:11px; color:#475569; margin-top:5px; }

        .divider { font-size:11px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:0.7px; margin:20px 0 14px; padding-bottom:8px; border-bottom:1px solid rgba(255,255,255,0.07); display:flex; align-items:center; gap:8px; }

        .btn-save { width:100%; padding:13px; background:linear-gradient(135deg,#9333ea,#7c3aed); color:white; border:none; border-radius:10px; font-size:15px; font-weight:700; cursor:pointer; transition:0.2s; display:flex; align-items:center; justify-content:center; gap:8px; margin-top:4px; }
        .btn-save:hover { opacity:0.88; transform:translateY(-1px); box-shadow:0 8px 24px rgba(147,51,234,0.4); }

        .alert { padding:13px 16px; border-radius:10px; font-size:14px; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .alert-success { background:rgba(16,185,129,0.12); border:1px solid rgba(16,185,129,0.3); color:#34d399; }
        .alert-error   { background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.3); color:#f87171; }

        .profile-card { background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); border-radius:14px; padding:24px; text-align:center; }
        .avatar { width:70px; height:70px; border-radius:50%; background:linear-gradient(135deg,#9333ea,#7c3aed); display:flex; align-items:center; justify-content:center; font-size:26px; font-weight:800; color:white; margin:0 auto 14px; }
        .p-name  { font-size:16px; font-weight:700; color:white; }
        .p-email { font-size:12px; color:#64748b; margin-top:4px; margin-bottom:14px; word-break:break-all; }
        .badges  { display:flex; flex-wrap:wrap; gap:6px; justify-content:center; }
        .badge { padding:3px 11px; border-radius:20px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.4px; }
        .badge-admin    { background:rgba(59,130,246,0.15); border:1px solid rgba(59,130,246,0.3); color:#60a5fa; }
        .badge-user     { background:rgba(16,185,129,0.15); border:1px solid rgba(16,185,129,0.3); color:#34d399; }
        .badge-super    { background:rgba(147,51,234,0.15); border:1px solid rgba(147,51,234,0.3); color:#c084fc; }
        .badge-active   { background:rgba(16,185,129,0.12); border:1px solid rgba(16,185,129,0.3); color:#34d399; }
        .badge-disabled { background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.3); color:#f87171; }
        .p-meta { margin-top:18px; padding-top:14px; border-top:1px solid rgba(255,255,255,0.07); font-size:12px; display:flex; flex-direction:column; gap:9px; text-align:left; }
        .meta-row { display:flex; justify-content:space-between; color:#64748b; }
        .meta-row span:last-child { color:#94a3b8; }
    </style>
</head>
<body>

<aside class="sidebar">
    <a href="superadmin-dashboard.php" class="sidebar-logo">
        <img src="../assets/images/logo.png" alt="Bhatbhatey Rental">
        <div class="sidebar-logo-text">
            <div class="title">Bhatbhatey</div>
            <div class="sub">Super Admin</div>
        </div>
    </a>
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
    <div class="top-bar">
        <div class="top-bar-left">
            <h1>Edit User</h1>
            <p>
                <a href="superadmin-dashboard.php">Dashboard</a>
                <span>/</span>
                <a href="superadmin-users.php">Users</a>
                <span>/</span>
                <?php echo htmlspecialchars($user['name']); ?>
            </p>
        </div>
        <a href="superadmin-users.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Users</a>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-circle-check"></i><?php echo $success; ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><i class="fas fa-circle-xmark"></i><?php echo $error; ?></div><?php endif; ?>

    <div class="edit-layout">
        <div class="card">
            <div class="card-header"><i class="fas fa-user-pen"></i><h2>User Information</h2></div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-input" required placeholder="Full name"
                                   value="<?php echo htmlspecialchars($user['name']); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-input" required placeholder="Phone"
                                   value="<?php echo htmlspecialchars($user['phone_number']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-input" required placeholder="Email"
                               value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>

                    <div class="divider"><i class="fas fa-lock"></i> Access &amp; Permissions</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select">
                                <option value="user"  <?php echo $user['role']==='user'  ?'selected':''; ?>>User</option>
                                <option value="admin" <?php echo $user['role']==='admin' ?'selected':''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active"   <?php echo $user['status']==='active'   ?'selected':''; ?>>Active</option>
                                <option value="disabled" <?php echo $user['status']==='disabled' ?'selected':''; ?>>Disabled</option>
                            </select>
                        </div>
                    </div>

                    <div class="divider"><i class="fas fa-key"></i> Change Password</div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-input" placeholder="Leave blank to keep current">
                        <div class="hint"><i class="fas fa-info-circle"></i> Minimum 8 characters. Leave blank to keep existing password.</div>
                    </div>

                    <button type="submit" class="btn-save"><i class="fas fa-floppy-disk"></i> Save Changes</button>
                </form>
            </div>
        </div>

        <div class="profile-card">
            <div class="avatar"><?php echo $initials; ?></div>
            <div class="p-name"><?php echo htmlspecialchars($user['name']); ?></div>
            <div class="p-email"><?php echo htmlspecialchars($user['email']); ?></div>
            <div class="badges">
                <?php
                $rClass = match($user['role']) { 'super_admin'=>'badge-super','admin'=>'badge-admin',default=>'badge-user' };
                $rLabel = match($user['role']) { 'super_admin'=>'Super Admin','admin'=>'Admin',default=>'User' };
                ?>
                <span class="badge <?php echo $rClass; ?>"><?php echo $rLabel; ?></span>
                <span class="badge <?php echo $user['status']==='active'?'badge-active':'badge-disabled'; ?>"><?php echo ucfirst($user['status']); ?></span>
            </div>
            <div class="p-meta">
                <div class="meta-row"><span><i class="fas fa-phone fa-fw"></i> Phone</span><span><?php echo htmlspecialchars($user['phone_number']); ?></span></div>
                <div class="meta-row"><span><i class="fas fa-calendar fa-fw"></i> Joined</span><span><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span></div>
                <div class="meta-row"><span><i class="fas fa-id-badge fa-fw"></i> ID</span><span>#<?php echo str_pad($user['id'],4,'0',STR_PAD_LEFT); ?></span></div>
            </div>
        </div>
    </div>
</main>
</body>
</html>
