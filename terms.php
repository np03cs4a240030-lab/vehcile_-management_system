<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions | Bhatbhatey</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --primary: #f97316;
            --primary-dark: #ea580c;
            --bg-dark: #0a0a0b;
            --card-bg: rgba(255, 255, 255, 0.03);
            --border: rgba(255, 255, 255, 0.08);
            --text-muted: #94a3b8;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: linear-gradient(to bottom right, #0a0a0b, #111827);
            color: var(--white);
            min-height: 100vh;
            padding: 50px 20px;
        }

        .container {
            max-width: 950px;
            margin: auto;
        }

        .terms-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 50px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
        }

        .logo {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 10px;
        }

        h1 {
            font-size: 42px;
            margin-bottom: 15px;
            font-weight: 800;
        }

        .updated-date {
            color: var(--text-muted);
            margin-bottom: 40px;
            font-size: 14px;
        }

        h2 {
            font-size: 24px;
            margin-top: 35px;
            margin-bottom: 15px;
            color: var(--primary);
            font-weight: 700;
        }

        p {
            color: #d1d5db;
            line-height: 1.9;
            margin-bottom: 16px;
            font-size: 15px;
        }

        ul {
            margin-left: 20px;
            margin-bottom: 20px;
        }

        ul li {
            margin-bottom: 12px;
            color: #d1d5db;
            line-height: 1.8;
        }

        .highlight {
            color: var(--primary);
            font-weight: 600;
        }

        .btn-back {
            display: inline-block;
            margin-top: 40px;
            padding: 14px 26px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 14px;
            font-weight: 700;
            transition: 0.3s ease;
        }

        .btn-back:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .footer-note {
            margin-top: 50px;
            padding-top: 25px;
            border-top: 1px solid var(--border);
            color: var(--text-muted);
            font-size: 14px;
            text-align: center;
        }

        @media (max-width: 768px) {

            .terms-card {
                padding: 30px 22px;
            }

            h1 {
                font-size: 30px;
            }

            h2 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="terms-card">

            <div class="logo">Bhatbhatey</div>

            <h1>Terms & Conditions</h1>

            <p class="updated-date">
                Last Updated: May 13, 2026
            </p>

            <p>
                Welcome to <span class="highlight">Bhatbhatey</span>. By creating an account,
                accessing our platform, or using our vehicle rental services,
                you agree to comply with and be bound by the following Terms and Conditions.
            </p>

            <h2>1. User Eligibility</h2>

            <p>
                Users must be at least 18 years old and legally capable of entering into binding agreements.
                By registering, you confirm that the information provided is accurate and complete.
            </p>

            <h2>2. Account Responsibility</h2>

            <ul>
                <li>You are responsible for maintaining the confidentiality of your account credentials.</li>
                <li>You agree not to share your account with others.</li>
                <li>You are responsible for all activities conducted under your account.</li>
            </ul>

            <h2>3. Vehicle Booking & Usage</h2>

            <ul>
                <li>Vehicles must only be used for lawful purposes.</li>
                <li>Users must possess a valid driving license.</li>
                <li>Any damage caused due to negligence may result in additional charges.</li>
                <li>Late returns may incur penalties.</li>
            </ul>

            <h2>4. Payments & Refunds</h2>

            <p>
                All booking payments must be completed before vehicle delivery or pickup.
                Refund eligibility depends on cancellation timing and company policy.
            </p>

            <h2>5. Prohibited Activities</h2>

            <ul>
                <li>Providing false registration information.</li>
                <li>Using the platform for fraudulent or illegal activities.</li>
                <li>Attempting unauthorized access to system resources.</li>
                <li>Violating Nepal traffic or transportation laws.</li>
            </ul>

            <h2>6. Privacy Policy</h2>

            <p>
                We collect limited personal information necessary for booking,
                authentication, and customer support purposes.
                Your data will not be sold to third parties without consent,
                except where legally required.
            </p>

            <h2>7. Limitation of Liability</h2>

            <p>
                Bhatbhatey shall not be held responsible for indirect losses,
                accidents, delays, or damages arising from misuse of rented vehicles.
            </p>

            <h2>8. Account Suspension</h2>

            <p>
                We reserve the right to suspend or permanently terminate accounts
                involved in suspicious, abusive, or fraudulent activities.
            </p>

            <h2>9. Changes to Terms</h2>

            <p>
                These Terms & Conditions may be updated at any time without prior notice.
                Continued use of the platform after updates constitutes acceptance of revised terms.
            </p>

            <h2>10. Contact Information</h2>

            <p>
                If you have questions regarding these Terms & Conditions,
                please contact our support team.
            </p>

            <p>
                Email: sushantmainali123@gmail.com<br>
                Phone: +977-9744368091
            </p>

            <a href="register.php" class="btn-back">
                ← Back to Registration
            </a>

            <div class="footer-note">
                © <?php echo date('Y'); ?> Bhatbhatey. All Rights Reserved.
            </div>

        </div>

    </div>

</body>

</html>