<?php
require_once 'includes/connection.php';
include 'chatbot/chatbox.php';

$sql = "SELECT * FROM vehicles WHERE availability = TRUE ORDER BY id DESC LIMIT 6";
$result = $conn->query($sql);
$vehicles = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $vehicles[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>भटभटे — Nepal's Premier Vehicle Rental</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

    <style>
        :root {
            --orange: #f97316;
            --orange-deep: #ea580c;
            --orange-glow: rgba(249, 115, 22, 0.18);
            --dark: #0a0e1a;
            --dark-2: #121828;
            --dark-3: #1a2236;
            --slate: #8b9ab4;
            --slate-light: #c4cedf;
            --white: #ffffff;
            --surface: #f4f6fb;
            --border: rgba(255, 255, 255, 0.06);
            --radius-xl: 28px;
            --radius-2xl: 40px;
        }

        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--surface);
            color: var(--dark);
            overflow-x: hidden;
        }

        h1,
        h2,
        h3,
        .logo {
            font-family: 'Syne', sans-serif;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            max-width: 1260px;
            margin: 0 auto;
            padding: 0 28px;
        }

        /* ─── NAVBAR ─── */
        nav {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            padding: 24px 0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        nav.scrolled {
            background: rgba(10, 14, 26, 0.6);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .nav-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            color: var(--white);
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--orange);
            display: inline-block;
            box-shadow: 0 0 12px var(--orange);
        }

        .nav-links {
            display: flex;
            gap: 36px;
            list-style: none;
        }

        .nav-links a {
            color: rgba(255, 255, 255, 0.7);
            font-weight: 500;
            font-size: 14px;
            letter-spacing: 0.3px;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: var(--white);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-ghost {
            color: rgba(255, 255, 255, 0.8);
            font-weight: 600;
            font-size: 14px;
            padding: 10px 20px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            transition: all 0.2s;
        }

        .btn-ghost:hover {
            border-color: rgba(255, 255, 255, 0.3);
            color: var(--white);
        }

        .btn-primary {
            background: var(--orange);
            color: var(--white);
            font-weight: 700;
            font-size: 14px;
            padding: 11px 24px;
            border-radius: 12px;
            transition: all 0.25s;
            box-shadow: 0 8px 24px rgba(249, 115, 22, 0.35);
        }

        .btn-primary:hover {
            background: var(--orange-deep);
            transform: translateY(-1px);
            box-shadow: 0 12px 32px rgba(249, 115, 22, 0.45);
        }

        /* ─── HERO ─── */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: var(--dark);
            position: relative;
            overflow: hidden;
        }

        .hero-video {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.35;
            filter: saturate(0.6);
        }

        /* layered gradient overlay */
        .hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 50% at 15% 60%, rgba(249, 115, 22, 0.12), transparent),
                linear-gradient(180deg, rgba(10, 14, 26, 0.4) 0%, rgba(10, 14, 26, 0.85) 100%);
        }

        /* grain texture */
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 1;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events: none;
            opacity: 0.5;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            padding: 140px 0 80px;
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(249, 115, 22, 0.12);
            border: 1px solid rgba(249, 115, 22, 0.25);
            padding: 6px 16px;
            border-radius: 100px;
            margin-bottom: 28px;
        }

        .hero-eyebrow span {
            color: var(--orange);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .hero h1 {
            font-size: clamp(52px, 7.5vw, 92px);
            font-weight: 800;
            color: var(--white);
            line-height: 0.95;
            letter-spacing: -3px;
            margin-bottom: 24px;
        }

        .hero h1 em {
            font-style: normal;
            color: var(--orange);
            position: relative;
        }

        .hero-sub {
            font-size: 18px;
            color: var(--slate-light);
            max-width: 520px;
            line-height: 1.7;
            margin-bottom: 48px;
            font-weight: 400;
        }

        /* Search Bar */
        .search-bar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            display: flex;
            align-items: stretch;
            max-width: 680px;
            box-shadow: 0 32px 80px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }

        .search-field {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 22px;
            border-right: 1px solid #e8ecf5;
        }

        .search-field svg {
            flex-shrink: 0;
            color: var(--slate);
        }

        .search-field input {
            border: none;
            outline: none;
            background: transparent;
            font-size: 15px;
            color: var(--dark);
            font-family: 'DM Sans', sans-serif;
            padding: 20px 0;
            width: 100%;
        }

        .search-field input::placeholder {
            color: #aab4c4;
        }

        .search-btn {
            background: var(--orange);
            color: var(--white);
            border: none;
            cursor: pointer;
            padding: 0 32px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 15px;
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
            flex-shrink: 0;
        }

        .search-btn:hover {
            background: var(--orange-deep);
        }

        .hero-trust {
            display: flex;
            align-items: center;
            gap: 24px;
            margin-top: 32px;
        }

        .trust-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 13px;
            font-weight: 500;
        }

        .trust-item svg {
            color: var(--orange);
        }

        /* ─── STATS ─── */
        .stats-wrap {
            margin-top: -56px;
            position: relative;
            z-index: 10;
        }

        .stats-card {
            background: var(--white);
            border-radius: var(--radius-2xl);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.07);
            display: grid;
            grid-template-columns: repeat(4, 1fr);
        }

        .stat-cell {
            padding: 44px 36px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            border-right: 1px solid #edf1f8;
            position: relative;
        }

        .stat-cell:last-child {
            border-right: none;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: var(--orange-glow);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            color: var(--orange);
        }

        .stat-num {
            font-family: 'Syne', sans-serif;
            font-size: 38px;
            font-weight: 800;
            color: var(--dark);
            line-height: 1;
            margin-bottom: 6px;
        }

        .stat-label {
            color: var(--slate);
            font-size: 13px;
            font-weight: 500;
        }

        /* ─── SECTION COMMONS ─── */
        .section {
            padding: 110px 0;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--orange);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 11px;
            margin-bottom: 14px;
        }

        .eyebrow::before {
            content: '';
            width: 24px;
            height: 2px;
            background: var(--orange);
            display: block;
            border-radius: 2px;
        }

        .section-title {
            font-size: clamp(32px, 4vw, 48px);
            font-weight: 800;
            letter-spacing: -1.5px;
            color: var(--dark);
            line-height: 1.05;
        }

        /* ─── FLEET ─── */
        .fleet-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 48px;
        }

        .see-all {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--orange);
            font-weight: 700;
            font-size: 14px;
            transition: gap 0.2s;
        }

        .see-all:hover {
            gap: 12px;
        }

        .fleet-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 28px;
        }

        .v-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            overflow: hidden;
            border: 1px solid #edf0f8;
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.35s;
            position: relative;
        }

        .v-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 32px 64px rgba(0, 0, 0, 0.1);
        }

        .v-img-wrap {
            position: relative;
            overflow: hidden;
        }

        .v-img {
            width: 100%;
            height: 230px;
            object-fit: cover;
            display: block;
            transition: transform 0.5s ease;
        }

        .v-card:hover .v-img {
            transform: scale(1.05);
        }

        .v-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            background: #16a34a;
            color: white;
            padding: 5px 13px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .badge-dot {
            width: 6px;
            height: 6px;
            background: #86efac;
            border-radius: 50%;
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.3;
            }
        }

        .v-body {
            padding: 22px;
        }

        .v-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .v-name {
            font-family: 'Syne', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
        }

        .v-rating {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 13px;
            font-weight: 600;
            color: #f59e0b;
        }

        .v-rating span {
            color: var(--slate);
        }

        .v-location {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--slate);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 18px;
        }

        .v-divider {
            border: none;
            border-top: 1px solid #f1f4fa;
            margin-bottom: 18px;
        }

        .v-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .v-price {
            font-family: 'Syne', sans-serif;
        }

        .v-price strong {
            font-size: 22px;
            font-weight: 800;
            color: var(--dark);
        }

        .v-price sub {
            font-size: 12px;
            color: var(--slate);
            font-weight: 400;
            vertical-align: baseline;
        }

        .btn-book {
            background: var(--dark);
            color: var(--white);
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: -0.2px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-book:hover {
            background: var(--orange);
            transform: scale(1.03);
        }

        /* ─── HOW IT WORKS ─── */
        .process-section {
            background: var(--dark);
            border-radius: 60px;
            margin: 0 20px;
        }

        .process-inner {
            padding: 100px 80px;
        }

        .process-inner .section-title {
            color: var(--white);
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2px;
            margin-top: 60px;
        }

        .step {
            padding: 48px 44px;
            border-radius: 0;
            background: var(--dark-3);
            position: relative;
            transition: background 0.3s;
        }

        .step:first-child {
            border-radius: 28px 0 0 28px;
        }

        .step:last-child {
            border-radius: 0 28px 28px 0;
        }

        .step:hover {
            background: var(--dark-2);
        }

        .step-line {
            position: absolute;
            top: 0;
            left: 0;
            width: 3px;
            height: 100%;
            background: transparent;
            border-radius: 4px;
            transition: background 0.3s;
        }

        .step:hover .step-line {
            background: var(--orange);
        }

        .step-num {
            font-family: 'Syne', sans-serif;
            font-size: 72px;
            font-weight: 900;
            color: rgba(255, 255, 255, 0.05);
            line-height: 1;
            margin-bottom: 24px;
            letter-spacing: -4px;
        }

        .step-icon {
            width: 52px;
            height: 52px;
            border-radius: 18px;
            background: var(--orange-glow);
            border: 1px solid rgba(249, 115, 22, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--orange);
            margin-bottom: 20px;
        }

        .step h3 {
            font-family: 'Syne', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 12px;
        }

        .step p {
            color: var(--slate);
            font-size: 14px;
            line-height: 1.7;
        }

        /* ─── PAYMENT METHODS ─── */
        .payments {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .pay-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 8px 18px;
            border-radius: 100px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 13px;
            font-weight: 600;
        }

        /* ─── FOOTER ─── */
        footer {
            background: var(--dark);
            color: var(--white);
            padding: 90px 0 40px;
            border-radius: 56px 56px 0 0;
            margin-top: 80px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2.2fr 1fr 1fr 1.4fr;
            gap: 64px;
            margin-bottom: 64px;
        }

        .footer-brand .logo {
            font-size: 28px;
            margin-bottom: 18px;
            display: block;
        }

        .footer-brand p {
            color: var(--slate);
            font-size: 14px;
            line-height: 1.75;
            max-width: 280px;
        }

        .footer-social {
            display: flex;
            gap: 12px;
            margin-top: 28px;
        }

        .social-btn {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--dark-3);
            border: 1px solid rgba(255, 255, 255, 0.07);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--slate);
            transition: all 0.2s;
        }

        .social-btn:hover {
            background: var(--orange);
            color: var(--white);
            border-color: var(--orange);
        }

        .footer-col h4 {
            font-family: 'Syne', sans-serif;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 24px;
        }

        .footer-col a {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--slate);
            font-size: 14px;
            margin-bottom: 14px;
            transition: all 0.2s;
        }

        .footer-col a:hover {
            color: var(--white);
            padding-left: 4px;
        }

        .newsletter-input-wrap {
            display: flex;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 8px;
        }

        .newsletter-input-wrap input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            padding: 13px 16px;
            color: var(--white);
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
        }

        .newsletter-input-wrap input::placeholder {
            color: var(--slate);
        }

        .newsletter-submit {
            background: var(--orange);
            border: none;
            cursor: pointer;
            padding: 0 16px;
            color: var(--white);
            transition: background 0.2s;
        }

        .newsletter-submit:hover {
            background: var(--orange-deep);
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 40px;
            border-top: 1px solid rgba(255, 255, 255, 0.07);
        }

        .footer-bottom p {
            color: var(--slate);
            font-size: 13px;
        }

        .footer-bottom-links {
            display: flex;
            gap: 24px;
        }

        .footer-bottom-links a {
            color: var(--slate);
            font-size: 13px;
        }

        .footer-bottom-links a:hover {
            color: var(--white);
        }

        /* ─── CONTACT ─── */
        .contact-wrap {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: start;
        }

        .contact-sub {
            color: var(--slate);
            font-size: 16px;
            line-height: 1.75;
            margin-bottom: 40px;
            max-width: 400px;
        }

        .contact-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .contact-card {
            display: flex;
            align-items: center;
            gap: 14px;
            background: var(--white);
            border: 1px solid #edf0f8;
            border-radius: 18px;
            padding: 20px;
            transition: all 0.25s;
        }

        .contact-card:hover {
            border-color: rgba(249, 115, 22, 0.3);
            box-shadow: 0 8px 24px rgba(249, 115, 22, 0.08);
            transform: translateY(-2px);
        }

        .contact-card-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: var(--orange-glow);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--orange);
            flex-shrink: 0;
        }

        .contact-card-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--slate);
            margin-bottom: 4px;
        }

        .contact-card-value {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
        }

        /* Form */
        .contact-form-wrap {
            background: var(--white);
            border-radius: 32px;
            padding: 44px;
            border: 1px solid #edf0f8;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.05);
        }

        .contact-form-header {
            margin-bottom: 32px;
        }

        .contact-form-header h3 {
            font-family: 'Syne', sans-serif;
            font-size: 24px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 6px;
        }

        .contact-form-header p {
            color: var(--slate);
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: var(--slate);
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            border: 1px solid #e8ecf5;
            border-radius: 14px;
            padding: 14px 18px;
            font-size: 15px;
            font-family: 'DM Sans', sans-serif;
            color: var(--dark);
            outline: none;
            background: #fafbfd;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
            background: var(--white);
        }

        .form-input::placeholder {
            color: #b0bdd0;
        }

        .form-textarea {
            resize: none;
            min-height: 120px;
        }

        .form-submit {
            width: 100%;
            background: var(--dark);
            color: var(--white);
            border: none;
            cursor: pointer;
            border-radius: 14px;
            padding: 16px 24px;
            font-family: 'Syne', sans-serif;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.25s;
            margin-top: 8px;
        }

        .form-submit:hover {
            background: var(--orange);
            box-shadow: 0 12px 32px rgba(249, 115, 22, 0.35);
            transform: translateY(-1px);
        }

        @media (max-width: 900px) {
            .contact-wrap {
                grid-template-columns: 1fr;
                gap: 48px;
            }

            .contact-cards {
                grid-template-columns: 1fr;
            }
        }

        /* ─── REVEAL ANIMATIONS ─── */
        .reveal {
            opacity: 0;
            transform: translateY(24px);
        }

        /* ─── SCROLLBAR ─── */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f4f6fb;
        }

        ::-webkit-scrollbar-thumb {
            background: #d1d9e8;
            border-radius: 4px;
        }

        /* ─── RESPONSIVE ─── */
        @media (max-width: 900px) {
            .stats-card {
                grid-template-columns: repeat(2, 1fr);
            }

            .stat-cell:nth-child(2) {
                border-right: none;
            }

            .stat-cell:nth-child(3) {
                border-top: 1px solid #edf1f8;
            }

            .steps-grid {
                grid-template-columns: 1fr;
                gap: 4px;
            }

            .step:first-child {
                border-radius: 28px 28px 0 0;
            }

            .step:last-child {
                border-radius: 0 0 28px 28px;
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr;
                gap: 40px;
            }

            .process-inner {
                padding: 70px 40px;
            }

            .nav-links {
                display: none;
            }
        }

        .whatsapp-float {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #25D366;
            color: white;
            padding: 14px 16px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            text-decoration: none;
            z-index: 9;
        }

        .wa-tooltip {
            font-size: 14px;
            font-weight: 500;
        }


        @media (max-width: 600px) {
            .hero h1 {
                letter-spacing: -2px;
            }

            .search-bar {
                flex-direction: column;
                border-radius: 20px;
            }

            .search-field {
                border-right: none;
                border-bottom: 1px solid #e8ecf5;
            }

            .search-btn {
                padding: 16px;
                justify-content: center;
            }

            .stats-card {
                grid-template-columns: 1fr 1fr;
            }

            .footer-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- ─── NAVBAR ─── -->
    <nav id="mainNav">
        <div class="container nav-inner">
            <a href="#" class="logo">
                <span class="logo-dot"></span>
                भटभटे
            </a>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="./vehicles.php">List of Vehicles</a></li>
                <li><a href="#process">How it Works</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <div class="nav-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="user/user-dashboard.php" class="btn-primary">My Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="btn-ghost">Log in</a>
                    <a href="register.php" class="btn-primary">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>



    <!-- ─── HERO ─── -->
    <section class="hero" id="home">
        <video class="hero-video" autoplay muted loop playsinline>
            <source src="./carbg (1).mp4" type="video/mp4">
        </video>
        <div class="container">
            <div class="hero-content">
                <div class="hero-eyebrow reveal">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                        style="color:var(--orange)">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 6v6l4 2" />
                    </svg>
                    <span>Nepal's #1 Vehicle Rental</span>
                </div>

                <h1 class="reveal">Find Your<br><em>Perfect</em> Ride.</h1>

                <p class="hero-sub reveal">Rent cars, bikes, and scooters across Nepal. Verified owners, transparent
                    pricing, zero hassle.</p>

                <form action="vehicles.php" method="GET" class="search-bar reveal">
                    <div class="search-field">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <input type="text" name="location" placeholder="Location — Kathmandu, Pokhara...">
                    </div>
                    <div class="search-field">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                            <path d="M13 17V9l-3-3H6l-3 3v5h2m10-8h2l3 3v5h-2M6 9h8" />
                        </svg>
                        <input type="text" name="type" placeholder="Vehicle type — Car, Bike, Scooter...">
                    </div>
                    <button type="submit" class="search-btn">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2.5">
                            <circle cx="11" cy="11" r="8" />
                            <path d="M21 21l-4.35-4.35" />
                        </svg>
                        Search
                    </button>
                </form>

                <div class="hero-trust reveal">
                    <div class="trust-item">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2.5">
                            <path
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Verified Owners
                    </div>
                    <div class="trust-item">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2.5">
                            <path
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Transparent Pricing
                    </div>
                    <div class="trust-item">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2.5">
                            <path
                                d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        24/7 Support
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── STATS ─── -->
    <div class="stats-wrap">
        <div class="container">
            <div class="stats-card reveal">
                <div class="stat-cell">
                    <div class="stat-icon">
                        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.8">
                            <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                            <path d="M13 17V9l-3-3H6l-3 3v5h2m10-8h2l3 3v5h-2M6 9h8" />
                        </svg>
                    </div>
                    <div class="stat-num"><span class="counter" data-target="500">0</span>+</div>
                    <div class="stat-label">Vehicles Available</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-icon">
                        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.8">
                            <path
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div class="stat-num"><span class="counter" data-target="12">0</span>+</div>
                    <div class="stat-label">Major Locations</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-icon">
                        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.8">
                            <path
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div class="stat-num"><span class="counter" data-target="1500">0</span>+</div>
                    <div class="stat-label">Happy Customers</div>
                </div>
                <div class="stat-cell">
                    <div class="stat-icon">
                        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.8">
                            <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="stat-num">24/7</div>
                    <div class="stat-label">Customer Support</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── FEATURED FLEET ─── -->
    <section class="section" id="fleet">
        <div class="container">
            <div class="fleet-header">
                <div>
                    <div class="eyebrow">Premium Collection</div>
                    <h2 class="section-title">Featured Fleet</h2>
                </div>
                <a href="vehicles.php" class="see-all">
                    Browse all vehicles
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2.5">
                        <path d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>

            <div class="fleet-grid">
                <?php foreach ($vehicles as $vehicle): ?>
                    <div class="v-card reveal">
                        <div class="v-img-wrap">
                            <img src="<?php echo htmlspecialchars($vehicle['image']); ?>" class="v-img"
                                alt="<?php echo htmlspecialchars($vehicle['name']); ?>">
                            <div class="v-badge">
                                <span class="badge-dot"></span>
                                Available
                            </div>
                        </div>
                        <div class="v-body">
                            <div class="v-top">
                                <div class="v-name"><?php echo htmlspecialchars($vehicle['name']); ?></div>
                                <div class="v-rating">
                                    <svg width="13" height="13" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                    </svg>
                                    4.8 <span>(24)</span>
                                </div>
                            </div>
                            <div class="v-location">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <?php echo htmlspecialchars($vehicle['location']); ?>
                            </div>
                            <hr class="v-divider">
                            <div class="v-footer">
                                <div class="v-price">
                                    <strong>NPR <?php echo number_format($vehicle['price_per_day']); ?></strong>
                                    <sub> / day</sub>
                                </div>
                                <a href="vehicle-details.php?id=<?php echo $vehicle['id']; ?>" class="btn-book">
                                    Book Now
                                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2.5">
                                        <path d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ─── HOW IT WORKS ─── -->
    <section class="section" id="process" style="padding-top: 0;">
        <div class="container">
            <div class="process-section">
                <div class="process-inner">
                    <div class="eyebrow" style="color:var(--orange)">Simple Process</div>
                    <h2 class="section-title">Rental Made<br>Effortless.</h2>

                    <div class="steps-grid">
                        <div class="step reveal">
                            <div class="step-line"></div>
                            <div class="step-num">01</div>
                            <div class="step-icon">
                                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.8">
                                    <circle cx="11" cy="11" r="8" />
                                    <path d="M21 21l-4.35-4.35" />
                                </svg>
                            </div>
                            <h3>Search &amp; Select</h3>
                            <p>Browse our curated fleet of bikes, scooters, and cars. Filter by location, type, or
                                price.</p>
                        </div>
                        <div class="step reveal">
                            <div class="step-line"></div>
                            <div class="step-num">02</div>
                            <div class="step-icon">
                                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.8">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                    <line x1="16" y1="2" x2="16" y2="6" />
                                    <line x1="8" y1="2" x2="8" y2="6" />
                                    <line x1="3" y1="10" x2="21" y2="10" />
                                </svg>
                            </div>
                            <h3>Book &amp; Pay</h3>
                            <p>Choose your dates and pay securely via eSewa, Khalti, or Cash on arrival.</p>
                            <div class="payments">
                                <div class="pay-chip">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <rect x="2" y="5" width="20" height="14" rx="2" />
                                        <path d="M2 10h20" />
                                    </svg>
                                    eSewa
                                </div>
                                <div class="pay-chip">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <rect x="2" y="5" width="20" height="14" rx="2" />
                                        <path d="M2 10h20" />
                                    </svg>
                                    Khalti
                                </div>
                                <div class="pay-chip">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" />
                                    </svg>
                                    Cash
                                </div>
                            </div>
                        </div>
                        <div class="step reveal">
                            <div class="step-line"></div>
                            <div class="step-num">03</div>
                            <div class="step-icon">
                                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.8">
                                    <circle cx="12" cy="12" r="10" />
                                    <polyline points="12 6 12 12 16 14" />
                                </svg>
                            </div>
                            <h3>Pick Up &amp; Go</h3>
                            <p>Head to the pickup point, show your booking confirmation, and hit the road instantly.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── CONTACT ─── -->
    <section class="section" id="contact" style="padding-bottom: 0;">
        <div class="container">
            <div class="contact-wrap">
                <div class="contact-left">
                    <div class="eyebrow">Get In Touch</div>
                    <h2 class="section-title">We're Here<br>to Help You.</h2>
                    <p class="contact-sub">Have questions about a booking, a vehicle, or anything else? Reach out — our
                        team is available around the clock.</p>

                    <div class="contact-cards">
                        <div class="contact-card">
                            <div class="contact-card-icon">
                                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <div>
                                <div class="contact-card-label">Call Us</div>
                                <div class="contact-card-value">+977 9744368091</div>
                            </div>
                        </div>
                        <div class="contact-card">
                            <div class="contact-card-icon">
                                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <div class="contact-card-label">Email Us</div>
                                <div class="contact-card-value">shusan@bhatbhate.com.np</div>
                            </div>
                        </div>
                        <div class="contact-card">
                            <div class="contact-card-icon">
                                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div>
                                <div class="contact-card-label">Visit Us</div>
                                <div class="contact-card-value">Thamel, Kathmandu, Nepal</div>
                            </div>
                        </div>
                        <div class="contact-card">
                            <div class="contact-card-icon">
                                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <div class="contact-card-label">Working Hours</div>
                                <div class="contact-card-value">24 / 7 — Always Open</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="contact-form-wrap">
                    <div class="contact-form-header">
                        <h3>Send a Message</h3>
                        <p>We'll get back to you within a few hours.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                            Full Name
                        </label>
                        <input type="text" class="form-input" placeholder="Ram Sharma">
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Email Address
                        </label>
                        <input type="email" class="form-input" placeholder="ram@example.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path
                                    d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                            Message
                        </label>
                        <textarea class="form-input form-textarea" placeholder="Tell us how we can help..."></textarea>
                    </div>
                    <button type="button" class="form-submit">
                        Send Message
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2.5">
                            <path d="M22 2L11 13M22 2L15 22l-4-9-9-4 19-7z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <a href="https://wa.me/9779744368091?text=Hi%20I%20need%20help" class="whatsapp-float" target="_blank">

        <span class="wa-icon">💬</span>
    </a>

    <!-- ─── FOOTER ─── -->
    <footer id="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="#" class="logo">
                        <span class="logo-dot"></span> भटभटे
                    </a>
                    <p>Nepal's most trusted vehicle rental platform. Making transportation accessible, affordable, and
                        effortless for everyone.</p>
                    <div class="footer-social">
                        <!-- Facebook -->
                        <a href="#" class="social-btn" aria-label="Facebook">
                            <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z" />
                            </svg>
                        </a>
                        <!-- Instagram -->
                        <a href="#" class="social-btn" aria-label="Instagram">
                            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
                                <path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z" />
                                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5" />
                            </svg>
                        </a>
                        <!-- Twitter/X -->
                        <a href="#" class="social-btn" aria-label="Twitter">
                            <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                            </svg>
                        </a>
                    </div>
                </div>

                <div class="footer-col">
                    <h4>Explore</h4>
                    <a href="#">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                            <path d="M13 17V9l-3-3H6l-3 3v5h2m10-8h2l3 3v5h-2M6 9h8" />
                        </svg>
                        Our Fleet
                    </a>
                    <a href="#">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Pricing Plan
                    </a>
                    <a href="#">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Safety Rules
                    </a>
                </div>

                <div class="footer-col">
                    <h4>Support</h4>
                    <a href="#">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3" />
                            <line x1="12" y1="17" x2="12.01" y2="17" />
                        </svg>
                        Help Center
                    </a>
                    <a href="#">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Contact Us
                    </a>
                    <a href="#">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Terms of Service
                    </a>
                </div>

                <div class="footer-col">
                    <h4>Newsletter</h4>
                    <p style="color:var(--slate); font-size: 13px; line-height: 1.6; margin-bottom: 16px;">Get the
                        latest ride offers and deals delivered to your inbox.</p>
                    <div class="newsletter-input-wrap">
                        <input type="email" placeholder="your@email.com">
                        <button class="newsletter-submit" aria-label="Subscribe">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2.5">
                                <path d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2026 भटभटे Rental &mdash; Kathmandu, Nepal.</p>
                <div class="footer-bottom-links">
                    <a href="privacy.php">Privacy Policy</a>
                    <a href="cookie.php">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Navbar scroll
        const nav = document.getElementById('mainNav');
        window.addEventListener('scroll', () => {
            nav.classList.toggle('scrolled', window.scrollY > 60);
        }, { passive: true });

        // GSAP reveal animations
        gsap.registerPlugin(ScrollTrigger);
        gsap.utils.toArray('.reveal').forEach(el => {
            gsap.fromTo(el,
                { opacity: 0, y: 28 },
                {
                    opacity: 1, y: 0, duration: 0.8,
                    ease: 'power3.out',
                    scrollTrigger: {
                        trigger: el,
                        start: 'top 88%',
                        toggleActions: 'play none none none'
                    }
                }
            );
        });

        // Counter animation (fires once on enter)
        const counters = document.querySelectorAll('.counter');
        const observed = new Set();

        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !observed.has(entry.target)) {
                    observed.add(entry.target);
                    const el = entry.target;
                    const target = +el.getAttribute('data-target');
                    const duration = 1800;
                    const step = 16;
                    const increment = target / (duration / step);
                    let current = 0;
                    const timer = setInterval(() => {
                        current = Math.min(current + increment, target);
                        el.textContent = Math.floor(current).toLocaleString();
                        if (current >= target) clearInterval(timer);
                    }, step);
                }
            });
        }, { threshold: 0.4 });

        counters.forEach(c => observer.observe(c));
    </script>
</body>

</html>