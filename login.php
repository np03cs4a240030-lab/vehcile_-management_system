<?php
require_once 'config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $sql = "SELECT * FROM users WHERE email = ? AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                redirect('user/user-dashboard.php');
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bhatbhatey Rental | Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ff7a00;
            --primary-glow: rgba(255, 122, 0, 0.4);
            --bg-dark: #0a0a0b;
            --glass: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            background-color: var(--bg-dark);
            color: #fff;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Background Video */
        .video-bg {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            object-fit: cover; z-index: -2; filter: brightness(0.3) saturate(1.2);
        }

        .overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(90deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.4) 100%);
            z-index: -1;
        }

        /* Header */
        .header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 30px 60px; z-index: 10;
        }

        .logo img { height: 50px; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .logo img:hover { transform: scale(1.1); filter: drop-shadow(0 0 15px var(--primary)); }

        .nav-link { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; font-weight: 500; margin-left: 30px; transition: 0.3s; }
        .nav-link:hover { color: var(--primary); }

        /* Split Layout */
        .main-wrapper {
            flex: 1; display: flex; align-items: center; padding: 0 10%;
        }

        .hero-text { flex: 1.2; padding-right: 50px; }
        .hero-text h1 { font-size: 64px; line-height: 1.1; margin-bottom: 20px; font-weight: 700; }
        .hero-text span { color: var(--primary); text-shadow: 0 0 30px var(--primary-glow); }
        .hero-text p { font-size: 18px; color: #aaa; max-width: 450px; line-height: 1.6; }

        /* Login Card */
        .login-card {
            flex: 0.8;
            background: var(--glass);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            padding: 50px;
            border-radius: 30px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
            position: relative;
        }

        .login-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at top right, rgba(255,122,0,0.1), transparent);
            pointer-events: none; border-radius: 30px;
        }

        .input-group { position: relative; margin-bottom: 25px; }
        .input-group input {
            width: 100%;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--glass-border);
            padding: 16px 20px;
            border-radius: 12px;
            color: #fff;
            font-size: 15px;
            outline: none;
            transition: 0.3s;
        }

        .input-group input:focus {
            border-color: var(--primary);
            background: rgba(255,255,255,0.08);
            box-shadow: 0 0 20px rgba(255, 122, 0, 0.2);
        }

        .login-btn {
            width: 100%; padding: 16px;
            background: var(--primary);
            color: #fff; border: none; border-radius: 12px;
            font-weight: 700; font-size: 16px; cursor: pointer;
            transition: 0.3s; margin-top: 10px;
        }

        .login-btn:hover {
            background: #ff9a2a;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px var(--primary-glow);
        }

        .admin-link {
            display: block; text-align: center; margin-top: 25px;
            color: #888; font-size: 13px; text-decoration: none;
            padding: 10px; border: 1px dashed var(--glass-border);
            border-radius: 10px; transition: 0.3s;
        }

        .admin-link:hover { color: #fff; border-color: var(--primary); }

        .error-msg {
            background: rgba(255, 77, 77, 0.1);
            border-left: 3px solid #ff4d4d;
            padding: 12px; margin-bottom: 20px;
            font-size: 13px; color: #ffbaba;
            border-radius: 4px;
        }

        /* Footer */
        .footer {
            padding: 20px 60px; display: flex; justify-content: space-between;
            font-size: 12px; color: #555; border-top: 1px solid var(--glass-border);
        }

        @media (max-width: 1024px) {
            .hero-text { display: none; }
            .main-wrapper { justify-content: center; }
        }
    </style>
</head>
<body>
    <video autoplay muted loop class="video-bg">
        <source src="./carbg (1).mp4" type="video/mp4">
    </video>
    <div class="overlay"></div>

    <header class="header">
        <div class="logo">
            <a href="index.php"><img src="assets/images/logo.png" alt="Bhatbhatey Rental"></a>
        </div>
        <div class="nav">
            <a href="register.php" class="nav-link" style="color: var(--primary); border: 1px solid var(--primary); padding: 8px 20px; border-radius: 20px;">Join Us</a>
        </div>
    </header>

    <main class="main-wrapper">
        <div class="hero-text">
            <h1>Drive the <span>Future</span> of Rental.</h1>
            <p>Experience Nepal's most seamless vehicle rental platform. Log in to manage your bookings and explore new horizons.</p>
        </div>

        <div class="login-card">
            <h2 style="margin-bottom: 10px; font-weight: 700;">Welcome Back</h2>
            <p style="color: #888; font-size: 14px; margin-bottom: 30px;">Enter your credentials to continue</p>

            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-group">
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 25px; color: #888;">
                    <label style="cursor:pointer;"><input type="checkbox" style="accent-color: var(--primary);"> Remember me</label>
                </div>
                <button class="login-btn">Sign In</button>
            </form>

            <a href="./admin/admin-login.php" class="admin-link">Access Admin Terminal</a>
        </div>
    </main>

    <footer class="footer">
        <div>© 2026 All rights reserved.</div>
        <div style="display: flex; gap: 20px;">
            <span>Developed by Bhatbhatey Development Team</span>
        </div>
    </footer>
</body>
</html>