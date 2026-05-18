<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../admin/admin-login.php');
}

$currentUser = getCurrentUser();
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($current) || empty($new) || empty($confirm)) {
        $error = 'All fields are required.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $currentUser['id']);
        $stmt->execute();
        $stmt->bind_result($storedHash);
        $stmt->fetch();
        $stmt->close();

        if (!$storedHash || !password_verify($current, $storedHash)) {
            $error = 'Current password is incorrect.';
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upd->bind_param("si", $hashed, $currentUser['id']);
            $upd->execute();
            $upd->close();
            $success = 'Password changed successfully!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password – Admin | Bhatbhatey Rental</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
        body { background:#f1f5f9; display:flex; min-height:100vh; }

        /* SIDEBAR — matches admin-dashboard exactly */
        .sidebar { width:240px; background:#1e293b; color:white; display:flex; flex-direction:column; min-height:100vh; position:fixed; top:0; left:0; z-index:100; }
        .sidebar-logo { padding:20px 24px; border-bottom:1px solid rgba(255,255,255,0.08); display:flex; align-items:center; gap:12px; text-decoration:none; }
        .sidebar-logo img { height:36px; }
        .sidebar-logo span { font-size:13px; color:#94a3b8; font-weight:600; }
        .sidebar-menu { padding:16px 12px; flex:1; display:flex; flex-direction:column; }
        .sidebar-menu a { display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:8px; color:#94a3b8; text-decoration:none; font-size:14px; font-weight:500; margin-bottom:4px; transition:all 0.2s; }
        .sidebar-menu a i { width:18px; text-align:center; font-size:15px; }
        .sidebar-menu a:hover { background:rgba(255,255,255,0.07); color:white; }
        .sidebar-menu a.active { background:#f97316; color:white; }
        .logout-link { margin-top:auto; }
        .logout-link a { color:#fca5a5 !important; }
        .logout-link a:hover { background:rgba(239,68,68,0.15) !important; }

        /* MAIN */
        .main-content { margin-left:240px; flex:1; padding:32px; }
        .page-header { margin-bottom:28px; display:flex; justify-content:space-between; align-items:flex-start; }
        .page-header h1 { font-size:22px; font-weight:700; color:#1e293b; }
        .page-header p  { color:#64748b; font-size:14px; margin-top:3px; }
        .btn-back { display:inline-flex; align-items:center; gap:8px; padding:9px 18px; border-radius:8px; font-size:14px; font-weight:600; text-decoration:none; background:white; color:#64748b; border:1px solid #e2e8f0; transition:0.2s; box-shadow:0 1px 3px rgba(0,0,0,0.06); }
        .btn-back:hover { background:#f8fafc; color:#1e293b; }

        /* CARD */
        .card-wrap { display:grid; grid-template-columns:480px 1fr; gap:24px; align-items:start; max-width:860px; }
        .card { background:white; border-radius:14px; box-shadow:0 2px 8px rgba(0,0,0,0.06); border:1px solid #e2e8f0; overflow:hidden; }
        .card-header { padding:18px 22px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:10px; }
        .card-header i { color:#f97316; }
        .card-header h2 { font-size:15px; font-weight:700; color:#1e293b; }
        .card-body { padding:24px; }

        .form-group { margin-bottom:18px; }
        .form-label { display:block; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:7px; }
        .input-wrap { position:relative; }
        .form-input { width:100%; padding:11px 44px 11px 14px; background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:9px; color:#1e293b; font-size:14px; outline:none; transition:0.2s; }
        .form-input:focus { border-color:#f97316; background:white; box-shadow:0 0 0 3px rgba(249,115,22,0.1); }
        .toggle-pw { position:absolute; right:14px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#94a3b8; font-size:14px; padding:0; transition:0.2s; }
        .toggle-pw:hover { color:#f97316; }
        .hint { font-size:11px; color:#94a3b8; margin-top:5px; }

        .btn-save { width:100%; padding:13px; background:#f97316; color:white; border:none; border-radius:9px; font-size:15px; font-weight:700; cursor:pointer; transition:0.2s; display:flex; align-items:center; justify-content:center; gap:8px; margin-top:6px; }
        .btn-save:hover { background:#ea6c09; box-shadow:0 4px 14px rgba(249,115,22,0.35); transform:translateY(-1px); }

        .alert { padding:13px 16px; border-radius:9px; font-size:14px; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .alert-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; }
        .alert-error   { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; }

        /* TIPS CARD */
        .tips-card { background:white; border-radius:14px; box-shadow:0 2px 8px rgba(0,0,0,0.06); border:1px solid #e2e8f0; padding:22px; }
        .tips-card h3 { font-size:14px; font-weight:700; color:#1e293b; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
        .tips-card h3 i { color:#f97316; }
        .tip { display:flex; gap:10px; margin-bottom:12px; font-size:13px; color:#64748b; }
        .tip i { color:#f97316; flex-shrink:0; margin-top:1px; }
        .strength-bar { height:5px; border-radius:10px; background:#e2e8f0; margin-top:7px; overflow:hidden; transition:0.3s; }
        .strength-fill { height:100%; border-radius:10px; width:0; transition:width 0.4s, background 0.4s; }
        .strength-label { font-size:11px; margin-top:4px; font-weight:600; }
    </style>
</head>
<body>

<aside class="sidebar">
    <a href="admin-dashboard.php" class="sidebar-logo">
        <img src="../assets/images/logo.png" alt="Bhatbhatey Rental">
        <span>Admin Panel</span>
    </a>
    <nav class="sidebar-menu">
        <a href="admin-dashboard.php"><i class="fas fa-gauge-high"></i> Dashboard</a>
        <a href="admin-vehicles.php"><i class="fas fa-car"></i> Vehicles</a>
        <a href="admin-bookings.php"><i class="fas fa-calendar-days"></i> Bookings</a>
        <a href="admin-users.php"><i class="fas fa-users"></i> Users</a>
        <a href="admin-change-password.php" class="active"><i class="fas fa-key"></i> Change Password</a>
        <div class="logout-link">
            <a href="../logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </div>
    </nav>
</aside>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1>Change Password</h1>
            <p>Update your admin account password</p>
        </div>
        <a href="admin-dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-circle-check"></i> <?php echo $success; ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><i class="fas fa-circle-xmark"></i> <?php echo $error; ?></div><?php endif; ?>

    <div class="card-wrap">
        <div class="card">
            <div class="card-header"><i class="fas fa-lock"></i><h2>Update Password</h2></div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <div class="input-wrap">
                            <input type="password" name="current_password" id="currentPw" class="form-input" required placeholder="Enter current password">
                            <button type="button" class="toggle-pw" onclick="togglePw('currentPw', this)"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <div class="input-wrap">
                            <input type="password" name="new_password" id="newPw" class="form-input" required placeholder="Enter new password" oninput="checkStrength(this.value)">
                            <button type="button" class="toggle-pw" onclick="togglePw('newPw', this)"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                        <div class="strength-label" id="strengthLabel" style="color:#94a3b8;"></div>
                        <div class="hint">Minimum 8 characters</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <div class="input-wrap">
                            <input type="password" name="confirm_password" id="confirmPw" class="form-input" required placeholder="Re-enter new password">
                            <button type="button" class="toggle-pw" onclick="togglePw('confirmPw', this)"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="btn-save"><i class="fas fa-key"></i> Update Password</button>
                </form>
            </div>
        </div>

        <div class="tips-card">
            <h3><i class="fas fa-shield-halved"></i> Password Tips</h3>
            <div class="tip"><i class="fas fa-check-circle"></i><span>Use at least 8 characters</span></div>
            <div class="tip"><i class="fas fa-check-circle"></i><span>Mix uppercase and lowercase letters</span></div>
            <div class="tip"><i class="fas fa-check-circle"></i><span>Include numbers (0–9)</span></div>
            <div class="tip"><i class="fas fa-check-circle"></i><span>Add special characters (!@#$%)</span></div>
            <div class="tip"><i class="fas fa-times-circle" style="color:#ef4444;"></i><span>Avoid using your name or email</span></div>
            <div class="tip"><i class="fas fa-times-circle" style="color:#ef4444;"></i><span>Don't reuse recent passwords</span></div>
            <hr style="margin:16px 0;border:none;border-top:1px solid #f1f5f9;">
            <div style="font-size:12px;color:#94a3b8;">Logged in as <strong style="color:#1e293b;"><?php echo htmlspecialchars($currentUser['name']); ?></strong></div>
        </div>
    </div>
</main>

<script>
function togglePw(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

function checkStrength(val) {
    const fill  = document.getElementById('strengthFill');
    const label = document.getElementById('strengthLabel');
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { w:'0%',   bg:'#e2e8f0', text:'' },
        { w:'25%',  bg:'#ef4444', text:'Weak' },
        { w:'50%',  bg:'#f97316', text:'Fair' },
        { w:'75%',  bg:'#eab308', text:'Good' },
        { w:'100%', bg:'#22c55e', text:'Strong' },
    ];
    const l = levels[val.length === 0 ? 0 : score] || levels[0];
    fill.style.width      = l.w;
    fill.style.background = l.bg;
    label.textContent     = l.text;
    label.style.color     = l.bg;
}
</script>
</body>
</html>
