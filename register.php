<?php
session_start();
require_once 'includes/connection.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize inputs
    $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Terms checkbox validation
    $termsAccepted = isset($_POST['terms']);

    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirmPassword)) {

        $error = 'Please fill in all fields';

    } elseif (!$termsAccepted) {

        $error = 'You must accept the Terms and Conditions';

    } elseif ($password !== $confirmPassword) {

        $error = 'Passwords do not match';

    } elseif (strlen($password) < 6) {

        $error = 'Password must be at least 6 characters';

    } else {

        // Check if email already exists
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $error = 'Email already exists. Try logging in.';

        } else {

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $sql = "INSERT INTO users (name, email, phone_number, password, role, status) 
                    VALUES (?, ?, ?, ?, 'user', 'active')";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $name, $email, $phone, $hashedPassword);

            if ($stmt->execute()) {

                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = 'user';

                header("Location: user/user-dashboard.php");
                exit();

            } else {

                $error = 'System error. Please try again later.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Bhatbhatey | Premium Vehicle Rentals</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap"
        rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>

    <style>
        :root {
            --primary: #f97316;
            --primary-hover: #ea580c;
            --primary-glow: rgba(249, 115, 22, 0.3);
            --bg-dark: #0a0a0b;
            --glass: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-muted: #64748b;
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
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
        }

        .split-layout {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        .logo {
            height: 50px;
        }

        /* Left Side */
        .left-section {
            width: 45%;
            position: relative;
            display: flex;
            align-items: center;
            padding: 80px;
            overflow: hidden;
            background: #000;
        }

        .video-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .video-container video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.5;
            filter: saturate(1.2) brightness(0.7);
        }

        .left-content {
            position: relative;
            z-index: 5;
        }

        .left-content h1 {
            font-size: 3.8rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
        }

        .left-content span {
            color: var(--primary);
        }

        .left-content p {
            color: #cbd5e1;
            font-size: 1.2rem;
            max-width: 420px;
            line-height: 1.6;
        }

        /* Right Side */
        .right-section {
            width: 55%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 70% 30%, #1e293b 0%, #0a0a0b 100%);
            padding: 40px;
        }

        .form-card {
            width: 100%;
            max-width: 520px;
            background: var(--glass);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            padding: 50px;
            border-radius: 40px;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.5);
        }

        .reveal {
            opacity: 0;
            transform: translateY(30px);
        }

        .input-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .input-group {
            margin-bottom: 22px;
            position: relative;
        }

        .input-group label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .input-group input {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            padding: 15px 18px;
            border-radius: 14px;
            color: white;
            outline: none;
            transition: 0.4s;
        }

        .input-group input:focus {
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 20px var(--primary-glow);
        }

        .strength-meter {
            height: 4px;
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            margin-top: 8px;
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            transition: 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-register {
            width: 100%;
            padding: 18px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 14px;
            font-weight: 800;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .btn-register:hover {
            background: var(--primary-hover);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .terms-group {
            margin-top: 20px;
            margin-bottom: 15px;
        }

        .terms-group label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #cbd5e1;
            font-size: 14px;
            cursor: pointer;
            line-height: 1.5;
        }

        .terms-group input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--primary);
        }

        .terms-group a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .terms-group a:hover {
            text-decoration: underline;
        }

        @media (max-width: 1100px) {
            .left-section {
                display: none;
            }

            .right-section {
                width: 100%;
                padding: 20px;
            }

            body {
                overflow-y: auto;
            }
        }
    </style>
</head>

<body>

    <div class="split-layout">

        <!-- Left Section -->
        <div class="left-section">

            <div class="video-container">
                <video autoplay muted loop playsinline>
                    <source src="./carbg (1).mp4" type="video/mp4">
                </video>
            </div>

            <div class="left-content">
                <h1 class="reveal">
                    Your Next <span>Adventure</span> Starts Here.
                </h1>

                <p class="reveal">
                    Join Nepal's most trusted vehicle rental network.
                    Premium fleet, instant booking, and zero hidden costs.
                </p>
            </div>

        </div>

        <!-- Right Section -->
        <div class="right-section">

            <div class="form-card reveal">

                <h2 style="font-size: 30px; margin-bottom: 8px;">
                    Create Account
                </h2>

                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 35px;">
                    Enter your details to get started.
                </p>

                <?php if ($error): ?>
                    <div class="alert-error">
                        ⚠️ <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="registerForm">

                    <!-- Full Name -->
                    <div class="input-group">
                        <label>Full Name</label>

                        <input
                            type="text"
                            name="name"
                            placeholder="Full name"
                            required
                            value="<?php echo htmlspecialchars($name ?? ''); ?>">
                    </div>

                    <!-- Email + Phone -->
                    <div class="input-row">

                        <div class="input-group">
                            <label>Email Address</label>

                            <input
                                type="email"
                                name="email"
                                placeholder="Email"
                                required
                                value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        </div>

                        <div class="input-group">
                            <label>Phone Number</label>

                            <input
                                type="tel"
                                name="phone"
                                placeholder="9800000000"
                                required
                                value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                        </div>

                    </div>

                    <!-- Password -->
                    <div class="input-group">

                        <label>Create Password</label>

                        <input
                            type="password"
                            name="password"
                            id="mainPass"
                            placeholder="Min. 6 characters"
                            required>

                        <div class="strength-meter">
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>

                    </div>

                    <!-- Confirm Password -->
                    <div class="input-group">

                        <label>Confirm Password</label>

                        <input
                            type="password"
                            name="confirmPassword"
                            id="confirmPass"
                            placeholder="Repeat password"
                            required>

                    </div>

                    <!-- Terms & Conditions -->
                    <div class="terms-group">

                        <label>
                            <input type="checkbox" name="terms" required>

                            I agree to the
                            <a href="terms.php" target="_blank">
                                Terms & Conditions
                            </a>
                        </label>

                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-register" id="submitBtn">
                        Create Account
                    </button>

                </form>

                <!-- Login Link -->
                <p style="text-align: center; margin-top: 30px; font-size: 14px; color: var(--text-muted);">

                    Already have an account?

                    <a href="login.php"
                        style="color: var(--primary); text-decoration: none; font-weight: 700; margin-left: 5px;">

                        Sign In

                    </a>

                </p>

            </div>

        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // Reveal Animations
            gsap.to(".reveal", {
                opacity: 1,
                y: 0,
                duration: 1,
                stagger: 0.15,
                ease: "power4.out"
            });

            // Password Strength Logic
            const passInput = document.getElementById('mainPass');
            const bar = document.getElementById('strengthBar');

            passInput.addEventListener('input', () => {

                const val = passInput.value;

                if (val.length === 0) {

                    bar.style.width = '0%';

                } else if (val.length < 5) {

                    bar.style.width = '30%';
                    bar.style.background = '#ef4444';

                } else if (val.length < 10) {

                    bar.style.width = '60%';
                    bar.style.background = '#f97316';

                } else {

                    bar.style.width = '100%';
                    bar.style.background = '#22c55e';
                }
            });

            // Magnetic Button Animation
            const btn = document.getElementById('submitBtn');

            btn.addEventListener('mousemove', (e) => {

                const rect = btn.getBoundingClientRect();

                const x = e.clientX - rect.left - rect.width / 2;
                const y = e.clientY - rect.top - rect.height / 2;

                gsap.to(btn, {
                    duration: 0.3,
                    x: x * 0.2,
                    y: y * 0.2
                });
            });

            btn.addEventListener('mouseleave', () => {

                gsap.to(btn, {
                    duration: 0.5,
                    x: 0,
                    y: 0,
                    ease: "elastic.out(1, 0.3)"
                });

            });

        });
    </script>

</body>

</html>