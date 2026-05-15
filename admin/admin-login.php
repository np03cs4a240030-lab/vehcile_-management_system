<?php
require_once '../config.php';

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'super_admin') {
        header("Location: ../superadmin/superadmin-dashboard.php");
    } else {
        header("Location: ../admin/admin-dashboard.php");
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            if (in_array($user['role'], ['admin', 'super_admin'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role']    = $user['role'];
                $_SESSION['name']    = $user['name'];

                if ($user['role'] === 'super_admin') {
                    header('Location: ../superadmin/superadmin-dashboard.php');
                } else {
                    header('Location: ../admin/admin-dashboard.php');
                }
                exit();
            } else {
                $error = 'Access Denied: Administrative privileges required.';
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Bhatbhatey Rental</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --sky: #38bdf8;
            --sky-dim: rgba(56,189,248,0.15);
            --sky-border: rgba(56,189,248,0.3);
            --white: #ffffff;
            --muted: rgba(255,255,255,0.45);
            --faint: rgba(255,255,255,0.08);
            --card-bg: rgba(4,10,24,0.72);
            --error: #fb7185;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #020817;
            color: var(--white);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* ── VIDEO BACKGROUND ── */
        .scene {
            position: fixed;
            inset: 0;
            z-index: 0;
        }
        .scene video {
            width: 100%; height: 100%;
            object-fit: cover;
            filter: brightness(0.28) saturate(0.55);
        }
        .scene::after {
            content: '';
            position: absolute;
            inset: 0;
            background:
                linear-gradient(to right,
                    rgba(2,8,23,0.92) 0%,
                    rgba(2,8,23,0.55) 45%,
                    rgba(2,8,23,0.1)  100%),
                linear-gradient(to top,
                    rgba(2,8,23,0.6) 0%,
                    transparent 40%);
        }

        /* ── TOP BAR ── */
        .topbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 30;
            padding: 0 40px;
            height: 64px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            background: rgba(2,8,23,0.5);
            backdrop-filter: blur(16px);
        }
        /* CHANGED: logo only, no text, size bumped to 42px */
        .topbar-logo {
            display: flex; align-items: center;
            text-decoration: none;
        }
        .topbar-logo img { height: 42px; }

        /* ── LAYOUT ── */
        .layout {
            position: relative;
            z-index: 10;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 80px 80px 40px;
            min-height: 100vh;
        }

        /* ── CARD ── */
        .card {
            width: 420px;
            background: var(--card-bg);
            backdrop-filter: blur(28px) saturate(1.2);
            -webkit-backdrop-filter: blur(28px) saturate(1.2);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 24px;
            padding: 44px 40px;
            box-shadow:
                0 0 0 1px rgba(56,189,248,0.04) inset,
                0 32px 80px rgba(0,0,0,0.65),
                0 0 60px rgba(56,189,248,0.04);
            animation: riseIn .7s cubic-bezier(.22,1,.36,1) both;
        }
        @keyframes riseIn {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--sky-dim);
            border: 1px solid var(--sky-border);
            border-radius: 100px;
            padding: 6px 14px;
            margin-bottom: 22px;
            font-size: 11px;
            font-weight: 600;
            color: var(--sky);
            letter-spacing: .6px;
            text-transform: uppercase;
        }
        .badge i { font-size: 10px; }

        .card-title {
            font-family: 'Syne', sans-serif;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.6px;
            line-height: 1.15;
            margin-bottom: 6px;
        }
        .card-sub {
            font-size: 13.5px;
            color: var(--muted);
            margin-bottom: 32px;
        }

        .divider {
            height: 1px;
            background: linear-gradient(to right, rgba(255,255,255,0.1), transparent);
            margin-bottom: 28px;
        }

        .error-box {
            display: flex;
            align-items: center;
            gap: 9px;
            background: rgba(251,113,133,0.08);
            border: 1px solid rgba(251,113,133,0.2);
            border-radius: 10px;
            padding: 11px 14px;
            margin-bottom: 22px;
            font-size: 13px;
            color: var(--error);
        }

        .field { margin-bottom: 18px; }
        .field label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: rgba(255,255,255,0.35);
            letter-spacing: .9px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .field-wrap { position: relative; }
        .field-wrap .ico {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.22);
            font-size: 13px;
            pointer-events: none;
            transition: color .2s;
        }
        .field-wrap input {
            width: 100%;
            background: rgba(255,255,255,0.045);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 11px;
            padding: 13px 14px 13px 42px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: var(--white);
            outline: none;
            transition: border-color .2s, background .2s, box-shadow .2s;
        }
        .field-wrap input::placeholder { color: rgba(255,255,255,0.2); }
        .field-wrap input:focus {
            border-color: rgba(56,189,248,0.45);
            background: rgba(56,189,248,0.05);
            box-shadow: 0 0 0 3px rgba(56,189,248,0.08);
        }
        .field-wrap input:focus + .ico-after,
        .field-wrap input:focus ~ .ico { color: var(--sky); }

        .eye-btn {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255,255,255,0.25);
            cursor: pointer;
            font-size: 13px;
            padding: 4px;
            transition: color .2s;
        }
        .eye-btn:hover { color: rgba(255,255,255,0.6); }

        .btn-submit {
            width: 100%;
            margin-top: 6px;
            padding: 14px;
            background: var(--sky);
            border: none;
            border-radius: 11px;
            font-family: 'Syne', sans-serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .3px;
            color: #020817;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 4px 24px rgba(56,189,248,0.22);
        }
        .btn-submit:hover {
            background: #7dd3fc;
            transform: translateY(-1px);
            box-shadow: 0 8px 30px rgba(56,189,248,0.3);
        }
        .btn-submit:active { transform: translateY(0); }

        .card-footer {
            text-align: center;
            margin-top: 22px;
        }
        .card-footer a {
            color: rgba(255,255,255,0.28);
            text-decoration: none;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color .2s;
        }
        .card-footer a:hover { color: var(--sky); }

        .brand-col {
            flex: 1;
            padding-right: 60px;
            animation: riseIn .9s .1s cubic-bezier(.22,1,.36,1) both;
        }
        .brand-col .eyebrow {
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--sky);
            font-weight: 600;
            margin-bottom: 16px;
        }
        .brand-col h1 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(36px, 4vw, 52px);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -1.5px;
            max-width: 480px;
            margin-bottom: 18px;
        }
        .brand-col p {
            color: var(--muted);
            font-size: 15px;
            max-width: 360px;
            line-height: 1.65;
        }

        /* ADDED: footer styles */
        .footer {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            z-index: 30;
            padding: 14px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(2,8,23,0.5);
            backdrop-filter: blur(16px);
            border-top: 1px solid rgba(255,255,255,0.05);
            font-size: 12px;
            color: rgba(255,255,255,0.3);
        }

        @media (max-width: 900px) {
            .layout { justify-content: center; padding: 80px 20px 30px; }
            .brand-col { display: none; }
            .card { width: 100%; max-width: 420px; }
        }
    </style>
</head>
<body>

<!-- Video Background -->
<div class="scene">
    <video autoplay muted loop playsinline>
        <source src="../carbg (1).mp4" type="video/mp4">
    </video>
</div>

<!-- Top Bar: logo only, no text, no User Portal link -->
<nav class="topbar">
    <a href="../index.php" class="topbar-logo">
        <img src="../assets/images/logo.png" alt="Bhatbhatey Rental">
    </a>
</nav>

<!-- Main Layout -->
<div class="layout">

    <!-- Left Branding -->
    <div class="brand-col">
        <p class="eyebrow">Admin Access</p>
        <h1>Control the&nbsp;fleet.<br>Own the&nbsp;dashboard.</h1>
        <p>Manage vehicles, bookings, and users from a single, unified admin workspace built for speed and clarity.</p>
    </div>

    <!-- Login Card -->
    <div class="card">
        <div class="badge">
            <i class="fas fa-shield-halved"></i>
            Secure Portal
        </div>

        <h2 class="card-title">Admin Login</h2>
        <p class="card-sub">Administrative &amp; Super Admin access only.</p>

        <div class="divider"></div>

        <?php if ($error): ?>
        <div class="error-box">
            <i class="fas fa-circle-exclamation"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="field">
                <label>Email Address</label>
                <div class="field-wrap">
                    <i class="fas fa-envelope ico"></i>
                    <input type="email" name="email" placeholder="admin@bhatbhatey.com" required
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>

            <div class="field">
                <label>Password</label>
                <div class="field-wrap">
                    <i class="fas fa-lock ico"></i>
                    <input type="password" name="password" id="pwdInput" placeholder="••••••••" required>
                    <button type="button" class="eye-btn" onclick="togglePwd()" aria-label="Toggle password">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-right-to-bracket"></i>
                Authorize &amp; Enter
            </button>
        </form>

        <div class="card-footer">
            <a href="../login.php">
                <i class="fas fa-arrow-left" style="font-size:10px;"></i>
                Return to User Portal
            </a>
        </div>
    </div>

</div>

<!-- Footer -->
<footer class="footer">
    <div>© 2026 All rights reserved.</div>
    <div style="display: flex; gap: 20px;">
        <span>Developed by Bhatbhatey Development Team</span>
    </div>
</footer>

<script>
function togglePwd() {
    const input = document.getElementById('pwdInput');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>
</body>
</html>