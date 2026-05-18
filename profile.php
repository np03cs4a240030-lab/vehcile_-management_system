<?php
require_once 'config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();

$passwordMsg   = '';
$passwordError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($current) || empty($new) || empty($confirm)) {
        $passwordError = 'All fields are required.';
    } elseif ($new !== $confirm) {
        $passwordError = 'New passwords do not match.';
    } elseif (strlen($new) < 8) {
        $passwordError = 'Password must be at least 8 characters.';
    } else {
        global $conn;
        $user_id = (int)$currentUser['id'];

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($storedHash);
        $stmt->fetch();
        $stmt->close();

        if (!$storedHash || !password_verify($current, $storedHash)) {
            $passwordError = 'Current password is incorrect.';
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upd->bind_param("si", $hashed, $user_id);
            $upd->execute();
            $upd->close();
            $passwordMsg = 'Password updated successfully!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Bhatbhatey Rental</title>
    <!-- Font Awesome — same as dashboard -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ── CRITICAL: prevent CSS reset from breaking icon fonts ── */
        i, .fas, .far, .fab, .fa, [class^="fa-"], [class*=" fa-"] {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands" !important;
        }

        :root {
            --primary:       #f97316;
            --primary-dark:  #ea6c09;
            --primary-light: #fff7ed;
            --primary-glow:  rgba(249,115,22,0.18);
            --dark:          #0f172a;
            --dark-blue:     #1e293b;
            --slate:         #64748b;
            --border:        #e2e8f0;
            --bg:            #f1f5f9;
        }

        /* Same reset as dashboard */
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Plus Jakarta Sans',sans-serif; }
        body { background:var(--bg); display:flex; min-height:100vh; }
        a    { text-decoration:none; transition:.2s; }

        /* ── SIDEBAR — identical to dashboard ── */
        .sidebar {
            width:250px; background:var(--dark-blue);
            position:fixed; height:100%;
            display:flex; flex-direction:column;
            box-shadow:4px 0 20px rgba(0,0,0,0.15); z-index:100;
        }
        .sidebar-logo {
            padding:16px 18px; border-bottom:1px solid rgba(255,255,255,0.08);
            display:flex; align-items:center; gap:10px;
        }
        .sidebar-logo img { height:38px; width:auto; object-fit:contain; filter:brightness(0) invert(1); }
        .logo-fallback {
            display:none; width:36px; height:36px; background:var(--primary);
            border-radius:9px; align-items:center; justify-content:center;
            color:white; font-size:15px; flex-shrink:0;
        }
        .sidebar-logo-text   { display:flex; flex-direction:column; line-height:1.2; }
        .sidebar-logo-text .lt-name { color:white; font-size:15px; font-weight:800; }
        .sidebar-logo-text .lt-sub  { color:#64748b; font-size:9px; text-transform:uppercase; letter-spacing:1px; }

        .sidebar-nav { padding:16px 12px; flex:1; overflow-y:auto; }
        .nav-section-label {
            font-size:10px; text-transform:uppercase; letter-spacing:1.5px;
            color:#475569; font-weight:700; padding:0 8px; margin:16px 0 6px;
        }
        .sidebar-nav a {
            display:flex; align-items:center; gap:11px; padding:11px 12px;
            border-radius:10px; color:#94a3b8; font-size:14px; font-weight:600; margin-bottom:3px;
        }
        .sidebar-nav a i    { width:18px; text-align:center; font-size:14px; }
        .sidebar-nav a:hover { background:#334155; color:white; }
        .sidebar-nav a.active {
            background:var(--primary); color:white;
            box-shadow:0 4px 12px rgba(249,115,22,0.3);
        }
        .sidebar-nav a.danger:hover { background:#7f1d1d; color:#fca5a5; }

        .sidebar-footer { padding:12px 14px; border-top:1px solid rgba(255,255,255,0.07); }
        .user-card {
            display:flex; align-items:center; gap:10px; padding:10px 12px;
            border-radius:12px; background:rgba(255,255,255,0.05);
            border:1px solid rgba(255,255,255,0.07);
        }
        .user-initials {
            width:36px; height:36px; background:var(--primary); border-radius:9px;
            display:flex; align-items:center; justify-content:center;
            color:white; font-size:12px; font-weight:800; flex-shrink:0;
            letter-spacing:.5px; text-transform:uppercase;
        }
        .user-info-inner { flex:1; min-width:0; }
        .u-name  { color:white; font-size:13px; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .u-badge { display:inline-flex; align-items:center; gap:4px; font-size:10px; color:#94a3b8; margin-top:2px; }
        .u-badge i { font-size:7px; color:#22c55e; }

        /* ── MAIN ── */
        .main { margin-left:250px; padding:32px; width:100%; min-height:100vh; }

        .page-header { margin-bottom:28px; }
        .page-header h1 { font-size:24px; font-weight:800; color:var(--dark); }
        .page-header p  { color:var(--slate); margin-top:4px; font-size:14px; }

        /* ── PROFILE CARD ── */
        .profile-card {
            background:white; border-radius:20px;
            border:1px solid var(--border);
            box-shadow:0 4px 20px rgba(0,0,0,0.06);
        }
        .profile-banner {
            height:140px; border-radius:20px 20px 0 0;
            background:linear-gradient(135deg,#0f172a 0%,#1e293b 55%,#334155 100%);
            position:relative; overflow:hidden;
        }
        .profile-banner::before {
            content:''; position:absolute; inset:0;
            background:
                radial-gradient(circle at 10% 70%, rgba(249,115,22,.22) 0%, transparent 50%),
                radial-gradient(circle at 90% 10%, rgba(249,115,22,.13) 0%, transparent 45%);
        }

        .profile-avatar-row {
            display:flex; align-items:flex-end; gap:20px;
            padding:0 32px; margin-top:-46px; margin-bottom:24px;
            position:relative; z-index:2;
        }
        .profile-avatar {
            width:90px; height:90px;
            background:linear-gradient(135deg,#f97316,#fb923c);
            border-radius:20px; display:flex; align-items:center; justify-content:center;
            font-size:36px; color:white;
            border:4px solid white;
            box-shadow:0 8px 28px rgba(249,115,22,.30); flex-shrink:0;
        }
        .profile-meta      { padding-bottom:6px; }
        .profile-name      { font-size:22px; font-weight:800; color:var(--dark); line-height:1.2; margin-bottom:5px; }
        .profile-since     { display:inline-flex; align-items:center; gap:6px; font-size:12px; color:var(--slate); }
        .profile-since i   { color:var(--primary); font-size:11px; }

        .profile-body { padding:0 32px 32px; }

        /* ── INFO GRID — same card style as dashboard stat-card ── */
        .info-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:14px; margin-bottom:28px; }
        .info-item {
            background:#f8fafc; border:1px solid var(--border);
            border-radius:14px; padding:18px 20px;
            transition:.2s; display:flex; align-items:center; gap:14px;
        }
        .info-item:hover { border-color:var(--primary); background:var(--primary-light); }
        .info-icon {
            width:42px; height:42px; border-radius:12px; flex-shrink:0;
            display:flex; align-items:center; justify-content:center; font-size:17px;
        }
        .info-text {}
        .info-label {
            font-size:10.5px; color:var(--slate); font-weight:700;
            text-transform:uppercase; letter-spacing:.6px; margin-bottom:5px;
        }
        .info-value    { font-size:15px; font-weight:700; color:var(--dark); }
        .role-badge {
            display:inline-flex; align-items:center; gap:6px;
            background:#dbeafe; color:#1d4ed8;
            padding:5px 13px; border-radius:100px; font-size:12px; font-weight:700;
        }

        hr.divider { border:none; border-top:1px solid var(--border); margin:24px 0; }

        /* ── ACTION BUTTONS — same as dashboard btn-book-now ── */
        .profile-actions { display:flex; gap:12px; flex-wrap:wrap; }

        .btn-act-primary {
            background:var(--primary); color:white;
            padding:11px 22px; border-radius:10px; font-size:14px; font-weight:700;
            display:flex; align-items:center; gap:7px; border:none; cursor:pointer;
            box-shadow:0 4px 12px rgba(249,115,22,0.25); transition:.2s;
        }
        .btn-act-primary:hover { background:var(--primary-dark); color:white; }

        .btn-act-secondary {
            background:white; color:var(--slate);
            padding:11px 22px; border-radius:10px; font-size:14px; font-weight:700;
            border:1px solid var(--border); display:flex; align-items:center; gap:7px;
            cursor:pointer; transition:.2s;
        }
        .btn-act-secondary:hover { border-color:var(--primary); color:var(--primary); }

        /* ── MODAL OVERLAY ── */
        .modal-overlay {
            display:none; position:fixed; inset:0;
            background:rgba(15,23,42,0.55);
            backdrop-filter:blur(4px);
            z-index:999; align-items:center; justify-content:center; padding:20px;
        }
        .modal-overlay.open { display:flex; }

        .modal-box {
            background:white; border-radius:20px;
            padding:32px; width:100%; max-width:460px;
            border:1px solid var(--border);
            box-shadow:0 24px 60px rgba(15,23,42,0.18);
            animation:slideUp .22s ease;
        }
        @keyframes slideUp {
            from { transform:translateY(16px); opacity:0; }
            to   { transform:translateY(0);    opacity:1; }
        }

        .modal-header {
            display:flex; justify-content:space-between; align-items:flex-start;
            margin-bottom:24px;
        }
        .modal-header-left h2 { font-size:20px; font-weight:800; color:var(--dark); margin:0 0 4px; }
        .modal-header-left p  { font-size:13px; color:var(--slate); margin:0; }

        .modal-close {
            width:34px; height:34px; border-radius:9px;
            background:#f1f5f9; border:none; cursor:pointer;
            display:flex; align-items:center; justify-content:center;
            color:var(--slate); font-size:15px; transition:.15s; flex-shrink:0;
        }
        .modal-close:hover { background:#e2e8f0; color:var(--dark); }

        .modal-icon {
            width:46px; height:46px; border-radius:12px;
            background:var(--primary-light); border:1px solid #fed7aa;
            display:flex; align-items:center; justify-content:center;
            color:var(--primary); font-size:18px; margin-bottom:14px;
        }

        .alert {
            border-radius:10px; padding:12px 16px; font-size:13.5px; font-weight:600;
            margin-bottom:20px; display:flex; align-items:center; gap:9px;
        }
        .alert-error   { background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; }
        .alert-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; }

        .field-group { margin-bottom:16px; }
        .field-group label {
            display:block; font-size:11px; font-weight:700;
            text-transform:uppercase; letter-spacing:.6px;
            color:var(--slate); margin-bottom:7px;
        }
        .field-wrap { position:relative; display:flex; align-items:center; }
        .field-wrap input {
            width:100%; padding:11px 42px 11px 14px;
            border:1.5px solid var(--border); border-radius:10px;
            font-size:14px; font-family:'Plus Jakarta Sans',sans-serif; color:var(--dark);
            background:#fafafa; outline:none; transition:.2s;
        }
        .field-wrap input:focus {
            border-color:var(--primary);
            background:white;
            box-shadow:0 0 0 3px var(--primary-glow);
        }
        /* Toggle button — explicitly set font so FA renders correctly */
        .toggle-pw {
            position:absolute; right:13px;
            background:none; border:none; cursor:pointer;
            color:#94a3b8; font-size:15px;
            display:flex; align-items:center; padding:0;
            transition:.15s;
            font-family:"Font Awesome 6 Free", sans-serif;
        }
        .toggle-pw:hover { color:var(--slate); }

        .strength-bar   { display:flex; gap:5px; margin-top:8px; }
        .strength-bar span {
            flex:1; height:3px; border-radius:2px;
            background:var(--border); transition:.3s;
        }
        .strength-bar span.weak   { background:#ef4444; }
        .strength-bar span.fair   { background:#f59e0b; }
        .strength-bar span.strong { background:#22c55e; }
        .strength-hint { font-size:11.5px; margin-top:5px; font-weight:600; font-family:'Plus Jakarta Sans',sans-serif; }
        .strength-hint.weak   { color:#ef4444; }
        .strength-hint.fair   { color:#f59e0b; }
        .strength-hint.strong { color:#22c55e; }

        .modal-footer { display:flex; gap:10px; margin-top:24px; }
        .btn-modal-primary {
            flex:1; background:var(--primary); color:white; border:none;
            padding:12px 22px; border-radius:10px;
            font-size:14px; font-weight:700; cursor:pointer;
            display:flex; align-items:center; justify-content:center; gap:8px;
            transition:.2s;
        }
        .btn-modal-primary:hover { background:var(--primary-dark); }
        .btn-modal-cancel {
            background:white; color:var(--slate); border:1.5px solid var(--border);
            padding:12px 22px; border-radius:10px;
            font-size:14px; font-weight:700; cursor:pointer; transition:.2s;
            font-family:'Plus Jakarta Sans',sans-serif;
        }
        .btn-modal-cancel:hover { border-color:var(--primary); color:var(--primary); }

        @media(max-width:700px) {
            .info-grid           { grid-template-columns:1fr; }
            .profile-avatar-row  { padding:0 20px; }
            .profile-body        { padding:0 20px 24px; }
        }
    </style>
</head>
<body>

<!-- ═══════════════════════════════ SIDEBAR ══════════════════════════════ -->
<div class="sidebar">
    <a href="index.php" class="sidebar-logo">
        <img src="assets/images/logo.png" alt="Bhatbhatey Rental"
             onerror="this.style.display='none'; document.querySelector('.logo-fallback').style.display='flex';">
        <div class="logo-fallback"><i class="fas fa-car"></i></div>
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

<!-- ═══════════════════════════════ MAIN ═════════════════════════════════ -->
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

            <!-- Info grid — now uses icon+text layout matching stat-card style -->
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-icon" style="background:#eff6ff; color:#3b82f6;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="info-text">
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon" style="background:#f0fdf4; color:#10b981;">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="info-text">
                        <div class="info-label">Email Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon" style="background:#fff7ed; color:#f97316;">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="info-text">
                        <div class="info-label">Phone Number</div>
                        <div class="info-value"><?php echo htmlspecialchars($currentUser['phone_number']); ?></div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon" style="background:#fdf4ff; color:#a855f7;">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <div class="info-text">
                        <div class="info-label">Account Type</div>
                        <div class="info-value">
                            <span class="role-badge">
                                <i class="fas fa-circle-check"></i>
                                <?php echo ucfirst(htmlspecialchars($currentUser['role'])); ?>
                            </span>
                        </div>
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
                <button class="btn-act-secondary" onclick="openModal()">
                    <i class="fas fa-lock"></i> Change Password
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════ CHANGE PASSWORD MODAL ════════════════════ -->
<div class="modal-overlay <?php echo ($passwordError || $passwordMsg) ? 'open' : ''; ?>" id="pwModal">
    <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="modal-title">

        <div class="modal-header">
            <div class="modal-header-left">
                <div class="modal-icon"><i class="fas fa-lock"></i></div>
                <h2 id="modal-title">Change Password</h2>
                <p>Keep your account safe with a strong password.</p>
            </div>
            <button class="modal-close" onclick="closeModal()" aria-label="Close modal">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <?php if ($passwordError): ?>
        <div class="alert alert-error">
            <i class="fas fa-circle-exclamation"></i>
            <?php echo htmlspecialchars($passwordError); ?>
        </div>
        <?php endif; ?>

        <?php if ($passwordMsg): ?>
        <div class="alert alert-success">
            <i class="fas fa-circle-check"></i>
            <?php echo htmlspecialchars($passwordMsg); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="profile.php">

            <div class="field-group">
                <label for="current_password">Current Password</label>
                <div class="field-wrap">
                    <input type="password" id="current_password" name="current_password"
                           placeholder="Enter your current password" autocomplete="current-password">
                    <button type="button" class="toggle-pw" onclick="togglePw('current_password', this)" aria-label="Toggle visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="field-group">
                <label for="new_password">New Password</label>
                <div class="field-wrap">
                    <input type="password" id="new_password" name="new_password"
                           placeholder="At least 8 characters" autocomplete="new-password"
                           oninput="checkStrength(this.value)">
                    <button type="button" class="toggle-pw" onclick="togglePw('new_password', this)" aria-label="Toggle visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="strength-bar">
                    <span id="sb1"></span>
                    <span id="sb2"></span>
                    <span id="sb3"></span>
                </div>
                <div class="strength-hint" id="shint"></div>
            </div>

            <div class="field-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="field-wrap">
                    <input type="password" id="confirm_password" name="confirm_password"
                           placeholder="Re-enter new password" autocomplete="new-password">
                    <button type="button" class="toggle-pw" onclick="togglePw('confirm_password', this)" aria-label="Toggle visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" name="change_password" class="btn-modal-primary">
                    <i class="fas fa-floppy-disk"></i> Update Password
                </button>
                <button type="button" class="btn-modal-cancel" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════ SCRIPTS ═══════════════════════════════ -->
<script>
    function openModal()  { document.getElementById('pwModal').classList.add('open');    }
    function closeModal() { document.getElementById('pwModal').classList.remove('open'); }

    document.getElementById('pwModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
    });

    function togglePw(fieldId, btn) {
        const inp  = document.getElementById(fieldId);
        const icon = btn.querySelector('i');
        if (inp.type === 'password') {
            inp.type       = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            inp.type       = 'password';
            icon.className = 'fas fa-eye';
        }
    }

    function checkStrength(val) {
        const bars = [document.getElementById('sb1'), document.getElementById('sb2'), document.getElementById('sb3')];
        const hint = document.getElementById('shint');

        bars.forEach(b => { b.className = ''; });
        hint.className   = 'strength-hint';
        hint.textContent = '';

        if (!val) return;

        let score = 0;
        if (val.length >= 8)                               score++;
        if (/[A-Z]/.test(val) && /[0-9]/.test(val))       score++;
        if (/[^A-Za-z0-9]/.test(val) && val.length >= 10) score++;

        const map = {
            1: { cls:'weak',   label:'Weak — add numbers or uppercase' },
            2: { cls:'fair',   label:'Fair — try adding a symbol'      },
            3: { cls:'strong', label:'Strong password!'                },
        };

        const info = map[score] || map[1];
        for (let i = 0; i < score; i++) bars[i].className = info.cls;
        hint.classList.add(info.cls);
        hint.textContent = info.label;
    }
</script>
</body>
</html>