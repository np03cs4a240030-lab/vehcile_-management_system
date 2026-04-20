<?php
require_once '../config.php';

if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin')) {
    $path = ($_SESSION['role'] === 'super_admin') ? '../superadmin/superadmin-dashboard.php' : '../admin/admin-dashboard.php';
    header("Location: $path");
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
            if ($user['role'] === 'admin' || $user['role'] === 'super_admin') {
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <style>
        :root {
            --accent: #38bdf8;
            --bg-dark: #020617;
            --glass: rgba(15, 23, 42, 0.75);
            --border: rgba(255, 255, 255, 0.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body {
            background: var(--bg-dark);
            color: white;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .video-bg {
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 1; overflow: hidden;
        }
        .video-bg video {
            width: 100%; height: 100%;
            object-fit: cover;
            /* FIXED: brightness raised from 0.2 to 0.4 so video is visible */
            filter: brightness(0.4) saturate(0.7);
        }
        .floating-car-ui {
            position: absolute; bottom: 10%; left: 5%;
            width: 450px; z-index: 15;
            pointer-events: none;
            filter: drop-shadow(0 20px 50px rgba(0,0,0,0.8));
            opacity: 0; transform: translateX(-50px);
        }
        .admin-card {
            width: 100%; max-width: 430px;
            background: var(--glass);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--border);
            padding: 50px 40px;
            border-radius: 28px;
            z-index: 10;
            box-shadow: 0 40px 80px rgba(0,0,0,0.7);
            position: relative;
        }
        .shield-box {
            width: 72px; height: 72px;
            background: rgba(56,189,248,0.12);
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 22px;
            border: 1px solid rgba(56,189,248,0.3);
        }
        .shield-box i { font-size: 32px; color: var(--accent); }
        .header-text { text-align: center; margin-bottom: 30px; }
        .header-text h2 { font-size: 24px; font-weight: 800; letter-spacing: -0.5px; }
        .header-text p { color: #64748b; font-size: 13px; margin-top: 5px; }
        .input-group { margin-bottom: 18px; position: relative; }
        .input-group label {
            display: block; font-size: 11px; font-weight: 700;
            color: #94a3b8; margin-bottom: 7px;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .input-group .input-wrap { position: relative; }
        .input-group .input-wrap i {
            position: absolute; left: 14px; top: 50%;
            transform: translateY(-50%);
            color: #475569; font-size: 14px;
        }
        .input-group input {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            padding: 14px 14px 14px 40px;
            border-radius: 12px;
            color: white; outline: none; transition: 0.3s;
            font-size: 14px;
        }
        .input-group input:focus {
            border-color: var(--accent);
            background: rgba(255,255,255,0.07);
            box-shadow: 0 0 20px rgba(56,189,248,0.15);
        }
        .input-group input::placeholder { color: #475569; }
        .btn-auth {
            width: 100%; padding: 15px;
            background: var(--accent);
            color: #000; border: none;
            border-radius: 12px;
            font-weight: 800; font-size: 14px;
            cursor: pointer; transition: 0.3s;
            margin-top: 8px; letter-spacing: 0.5px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-auth:hover { background: #7dd3fc; transform: translateY(-1px); }
        .footer-link { text-align: center; margin-top: 22px; }
        .footer-link a { color: #475569; text-decoration: none; font-size: 13px; transition: 0.3s; }
        .footer-link a:hover { color: var(--accent); }
        .error-box {
            background: rgba(244,63,94,0.1); color: #fda4af;
            padding: 11px 14px; border-radius: 10px;
            margin-bottom: 22px; font-size: 13px;
            text-align: center; border: 1px solid rgba(244,63,94,0.2);
            display: flex; align-items: center; justify-content: center; gap: 7px;
        }
        .reveal { opacity: 0; transform: translateY(20px); }
        @media (max-width: 768px) { .floating-car-ui { display: none; } }
    </style>
</head>
<body>

<div class="video-bg">
    <video autoplay muted loop playsinline>
        <source src="../carbg.mp4" type="video/mp4">
    </video>
</div>

<img src="../assets/images/admin-car-render.png" alt="" class="floating-car-ui" id="carImage">

<div class="admin-card reveal">

    <div class="shield-box reveal">
        <i class="fas fa-shield-halved"></i>
    </div>

    <div class="header-text">
        <h2 class="reveal">System Login</h2>
        <p class="reveal">Administrative Access Only</p>
    </div>

    <?php if ($error): ?>
        <div class="error-box reveal">
            <i class="fas fa-circle-exclamation"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group reveal">
            <label>Admin Email</label>
            <div class="input-wrap">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="admin@bhatbhatey.com" required autocomplete="off"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
        </div>

        <div class="input-group reveal">
            <label>Password</label>
            <div class="input-wrap">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
        </div>

        <button type="submit" class="btn-auth reveal">
            <i class="fas fa-right-to-bracket"></i>
            Authorize &amp; Enter
        </button>
    </form>

    <div class="footer-link reveal">
        <a href="../login.php"><i class="fas fa-arrow-left" style="font-size:11px;"></i> Return to User Portal</a>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tl = gsap.timeline();
        tl.to(".reveal", { opacity: 1, y: 0, duration: 0.8, stagger: 0.08, ease: "power4.out" });
        gsap.to("#carImage", { opacity: 1, x: 0, duration: 1.5, delay: 0.4, ease: "power3.out" });
        gsap.to("#carImage", { y: 12, duration: 3, repeat: -1, yoyo: true, ease: "sine.inOut" });
    });
</script>
</body>
</html>