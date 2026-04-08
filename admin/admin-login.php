<?php
session_start();
require_once '../config.php'; // Ensure this path is correct for your file structure

// Redirect if already logged in as admin
if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin')) {
    $path = ($_SESSION['role'] === 'super_admin') ? '../superadmin/superadmin-dashboard.php' : '../admin/admin-dashboard.php';
    header("Location: $path");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Using your existing sanitize function from config.php
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['role'] === 'admin' || $user['role'] === 'super_admin') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];

                if ($user['role'] === 'super_admin') {
                    header('Location: ../superadmin/superadmin-dashboard.php');
                } else {
                    header('Location: ../admin/admin-dashboard.php');
                }
                exit();
            } else {
                $error = 'Access Denied: Administrative privileges required';
            }
        } else {
            $error = 'Invalid administrative credentials';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Authentication | Bhatbhatey Rental</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>

    <style>
        :root {
            --accent: #38bdf8;
            --bg-dark: #020617;
            --glass: rgba(15, 23, 42, 0.7);
            --border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: var(--bg-dark);
            color: white;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Video Background */
        .video-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            overflow: hidden;
        }

        .video-bg video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.2) saturate(0.5);
        }

        /* Floating Car Image - 3D Effect */
        .floating-car-ui {
            position: absolute;
            bottom: 10%;
            left: 5%;
            width: 450px;
            z-index: 15;
            pointer-events: none;
            filter: drop-shadow(0 20px 50px rgba(0, 0, 0, 0.8));
            opacity: 0;
            transform: translateX(-50px);
        }

        /* Admin Login Card */
        .admin-card {
            width: 100%;
            max-width: 420px;
            background: var(--glass);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--border);
            padding: 50px 40px;
            border-radius: 35px;
            z-index: 10;
            box-shadow: 0 50px 100px rgba(0, 0, 0, 0.8);
            position: relative;
        }

        .shield-box {
            width: 80px;
            height: 80px;
            background: rgba(56, 189, 248, 0.1);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            border: 1px solid rgba(56, 189, 248, 0.3);
        }

        .header-text {
            text-align: center;
            margin-bottom: 35px;
        }

        .header-text h2 {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .header-text p {
            color: #64748b;
            font-size: 14px;
            margin-top: 5px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .input-group input {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            padding: 15px 18px;
            border-radius: 12px;
            color: white;
            outline: none;
            transition: 0.4s;
        }

        .input-group input:focus {
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.06);
            box-shadow: 0 0 25px rgba(56, 189, 248, 0.2);
        }

        .btn-auth {
            width: 100%;
            padding: 16px;
            background: var(--accent);
            color: #000;
            border: none;
            border-radius: 12px;
            font-weight: 800;
            font-size: 15px;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }

        .footer-link {
            text-align: center;
            margin-top: 25px;
        }

        .footer-link a {
            color: #64748b;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
        }

        .footer-link a:hover {
            color: var(--accent);
        }

        .reveal {
            opacity: 0;
            transform: translateY(20px);
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .floating-car-ui {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="video-bg">
        <video autoplay muted loop playsinline>
            <source src="../carbg.mp4" type="video/mp4">
        </video>
    </div>

    <img src="../assets/images/admin-car-render.png" alt="Admin Car" class="floating-car-ui" id="carImage">

    <div class="admin-card reveal">
        <div class="shield-box reveal">
            <span style="font-size: 40px; filter: drop-shadow(0 0 10px var(--accent));">🛡️</span>
        </div>

        <div class="header-text">
            <h2 class="reveal">System Login</h2>
            <p class="reveal">Administrative Access Required</p>
        </div>

        <?php if ($error): ?>
            <div style="background: rgba(244, 63, 94, 0.1); color: #fda4af; padding: 12px; border-radius: 12px; margin-bottom: 25px; font-size: 13px; text-align: center;"
                class="reveal">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group reveal">
                <label>Admin Email</label>
                <input type="email" name="email" placeholder="admin@bhatbhatey.com" required autocomplete="off">
            </div>

            <div class="input-group reveal">
                <label>Security Key</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-auth reveal">Authorize & Enter</button>
        </form>

        <div class="footer-link reveal">
            <a href="../login.php">← Return to User Portal</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tl = gsap.timeline();

            // Fade in card and elements
            tl.to(".reveal", {
                opacity: 1,
                y: 0,
                duration: 1,
                stagger: 0.1,
                ease: "power4.out"
            });

            // Animate the car sliding in from the left
            gsap.to("#carImage", {
                opacity: 1,
                x: 0,
                duration: 1.5,
                delay: 0.5,
                ease: "power3.out"
            });

            // Subtle floating motion for the car
            gsap.to("#carImage", {
                y: 15,
                duration: 3,
                repeat: -1,
                yoyo: true,
                ease: "sine.inOut"
            });
        });
    </script>
</body>

</html>