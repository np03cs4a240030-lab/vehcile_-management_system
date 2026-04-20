<?php
require_once 'includes/connection.php';
// session_start(); is typically in your connection or a header file

// Fetch featured vehicles with a "Popular" or "Available" priority
$sql = "SELECT * FROM vehicles WHERE availability = TRUE ORDER BY id DESC LIMIT 6";
$result = $conn->query($sql);
$vehicles = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $vehicles[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>भटभटे - Nepal's #1 Vehicle Rental Platform</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

    <style>
        :root {
            --primary: #f97316; /* Brand Orange */
            --dark: #0f172a;
            --slate: #64748b;
            --glass: rgba(255, 255, 255, 0.9);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #f8fafc; color: var(--dark); overflow-x: hidden; }
        .container { max-width: 1280px; margin: 0 auto; padding: 0 24px; }
        a { text-decoration: none; transition: 0.3s; }

        /* --- High-End Navbar --- */
        nav {
            position: fixed; top: 0; width: 100%; z-index: 1000; padding: 20px 0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        nav.sticky { background: var(--glass); backdrop-filter: blur(20px); padding: 12px 0; box-shadow: 0 4px 30px rgba(0,0,0,0.05); }
        
        .nav-content { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 26px; font-weight: 800; color: var(--primary); letter-spacing: -1px; }
        .nav-links { display: flex; gap: 32px; list-style: none; }
        .nav-links a { color: var(--dark); font-weight: 600; font-size: 15px; }
        .nav-links a:hover { color: var(--primary); }

        .nav-btns { display: flex; align-items: center; gap: 15px; }
        .btn-login { color: var(--dark); font-weight: 700; padding: 10px 20px; }
        .btn-signup { background: var(--primary); color: white; padding: 12px 24px; border-radius: 14px; font-weight: 700; box-shadow: 0 10px 20px rgba(249, 115, 22, 0.2); }

        /* --- Hero with Parallax Video --- */
        .hero {
            height: 95vh; position: relative; display: flex; align-items: center; 
            background: #000; color: white; overflow: hidden;
        }
        .hero-video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0.5; }
        .hero-overlay { position: absolute; inset: 0; background: linear-gradient(to bottom, transparent, #00000080); }

        .hero-text { position: relative; z-index: 10; max-width: 800px; }
        .hero-text h1 { font-size: clamp(48px, 8vw, 84px); font-weight: 800; line-height: 1; letter-spacing: -2px; margin-bottom: 24px; }
        .hero-text p { font-size: 20px; opacity: 0.9; margin-bottom: 40px; }

        /* Search Engine UI */
        .search-container {
            background: white; padding: 10px; border-radius: 24px; display: flex; gap: 10px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.3); max-width: 700px;
        }
        .search-group { flex: 1; display: flex; align-items: center; padding: 0 20px; border-right: 1px solid #f1f5f9; }
        .search-group input { border: none; padding: 15px 0; width: 100%; outline: none; font-size: 16px; color: var(--dark); }
        .btn-find { background: var(--primary); color: white; border: none; padding: 0 35px; border-radius: 18px; font-weight: 700; cursor: pointer; }

        /* --- Stats Bar --- */
        .stats-section { margin-top: -60px; position: relative; z-index: 50; }
        .stats-card { 
            background: white; display: grid; grid-template-columns: repeat(4, 1fr); 
            padding: 40px; border-radius: 32px; box-shadow: 0 20px 50px rgba(0,0,0,0.05);
        }
        .stat-box { text-align: center; border-right: 1px solid #f1f5f9; }
        .stat-box:last-child { border-right: none; }
        .stat-box h2 { font-size: 36px; font-weight: 800; color: var(--dark); }
        .stat-box p { color: var(--slate); font-size: 14px; font-weight: 600; margin-top: 4px; }

        /* --- Featured Vehicles --- */
        .section { padding: 100px 0; }
        .section-tag { color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 2px; font-size: 12px; display: block; margin-bottom: 12px; }
        .section-title { font-size: 42px; font-weight: 800; margin-bottom: 50px; }

        .fleet-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 32px; }
        .v-card { 
            background: white; border-radius: 28px; overflow: hidden; border: 1px solid #f1f5f9;
            transition: 0.4s; position: relative;
        }
        .v-card:hover { transform: translateY(-12px); box-shadow: 0 30px 60px rgba(0,0,0,0.08); }
        .v-img { width: 100%; height: 240px; object-fit: cover; }
        .v-badge { position: absolute; top: 20px; left: 20px; background: #22c55e; color: white; padding: 6px 14px; border-radius: 100px; font-size: 11px; font-weight: 700; }
        
        .v-body { padding: 24px; }
        .v-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .v-price { font-size: 22px; font-weight: 800; color: var(--primary); }
        .v-price span { font-size: 13px; color: var(--slate); font-weight: 400; }

        /* --- How it Works --- */
        .step-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; margin-top: 50px; }
        .step-item { background: #fff; padding: 40px; border-radius: 30px; transition: 0.3s; }
        .step-item:hover { background: var(--dark); color: white; }
        .step-num { font-size: 60px; font-weight: 900; opacity: 0.1; line-height: 1; }

        /* --- Trust Slider --- */
        .review-card { background: white; padding: 40px; border-radius: 30px; border: 1px solid #f1f5f9; }
        .quote { font-size: 18px; font-style: italic; color: var(--slate); margin-bottom: 25px; line-height: 1.6; }
        .user-profile { display: flex; align-items: center; gap: 15px; }
        .avatar { width: 50px; height: 50px; border-radius: 50%; background: #ddd; }

        /* --- Footer --- */
        footer { background: var(--dark); color: white; padding: 80px 0 40px; border-radius: 60px 60px 0 0; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 60px; margin-bottom: 60px; }
        .footer-logo { font-size: 32px; font-weight: 900; color: var(--primary); margin-bottom: 20px; display: block; }
        .footer-links h4 { margin-bottom: 25px; font-size: 18px; }
        .footer-links a { display: block; color: #94a3b8; margin-bottom: 12px; }
        .footer-links a:hover { color: var(--primary); padding-left: 5px; }

        /* Animations */
        .reveal { opacity: 0; transform: translateY(30px); }
    </style>
</head>
<body>

    <nav id="mainNav">
        <div class="container nav-content">
            <a href="#" class="logo">भटभटे.</a>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#fleet">Fleet</a></li>
                <li><a href="#process">How it Works</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <div class="nav-btns">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="user/user-dashboard.php" class="btn-signup">My Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="btn-login">Login</a>
                    <a href="register.php" class="btn-signup">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <section class="hero" id="home">
        <video class="hero-video" autoplay muted loop playsinline>
            <source src="./carbg (1).mp4" type="video/mp4">
        </video>
        <div class="hero-overlay"></div>
        <div class="container">
            <div class="hero-text">
                <h1 class="reveal">Find Your<br>Perfect Ride.</h1>
                <p class="reveal">Rent cars, bikes, and scooters across Nepal instantly. Verified owners, transparent pricing, zero hassle.</p>
                
                <form action="vehicles.php" method="GET" class="search-container reveal">
                    <div class="search-group">
                        <input type="text" name="location" placeholder="Search Location (Kathmandu, Pokhara...)">
                    </div>
                    <div class="search-group" style="border:none;">
                        <input type="text" placeholder="Vehicle Type?">
                    </div>
                    <button type="submit" class="btn-find">Find Now</button>
                </form>
            </div>
        </div>
    </section>

    <section class="stats-section">
        <div class="container">
            <div class="stats-card reveal">
                <div class="stat-box">
                    <h2 class="counter" data-target="500">0</h2>
                    <p>Vehicles Available</p>
                </div>
                <div class="stat-box">
                    <h2 class="counter" data-target="12">0</h2>
                    <p>Major Locations</p>
                </div>
                <div class="stat-box">
                    <h2 class="counter" data-target="1500">0</h2>
                    <p>Happy Customers</p>
                </div>
                <div class="stat-box">
                    <h2>24/7</h2>
                    <p>Customer Support</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="fleet">
        <div class="container">
            <span class="section-tag">Premium Collection</span>
            <h2 class="section-title">Featured Fleet</h2>

            <div class="fleet-grid">
                <?php foreach($vehicles as $vehicle): ?>
                <div class="v-card reveal">
                    <span class="v-badge">Available</span>
                    <img src="<?php echo htmlspecialchars($vehicle['image']); ?>" class="v-img" alt="Bike">
                    <div class="v-body">
                        <div class="v-meta">
                            <h3 style="font-weight: 800;"><?php echo htmlspecialchars($vehicle['name']); ?></h3>
                            <div style="color: #fbbf24;">★ 4.8</div>
                        </div>
                        <p style="color: var(--slate); font-size: 14px; margin-bottom: 20px;">📍 <?php echo htmlspecialchars($vehicle['location']); ?></p>
                        <div class="v-meta">
                            <div class="v-price">NPR <?php echo number_format($vehicle['price_per_day']); ?> <span>/day</span></div>
                            <a href="vehicle-details.php?id=<?php echo $vehicle['id']; ?>" class="btn-signup" style="padding: 10px 20px; font-size: 13px;">Book Now</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section" id="process" style="background: #f1f5f9;">
        <div class="container">
            <h2 class="section-title text-center" style="text-align: center;">Rental Made Easy</h2>
            <div class="step-grid">
                <div class="step-item reveal">
                    <div class="step-num">01</div>
                    <h3 style="margin: 20px 0;">Search & Select</h3>
                    <p>Pick from our massive fleet of bikes, scooters, and cars.</p>
                </div>
                <div class="step-item reveal">
                    <div class="step-num">02</div>
                    <h3 style="margin: 20px 0;">Digital Booking</h3>
                    <p>Choose dates and pay via eSewa, Khalti, or Cash.</p>
                </div>
                <div class="step-item reveal">
                    <div class="step-num">03</div>
                    <h3 style="margin: 20px 0;">Pick Up & Go</h3>
                    <p>Grab your keys and start your journey instantly.</p>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-grid">
                <div>
                    <a href="#" class="footer-logo">भटभटे.</a>
                    <p style="color: #94a3b8; line-height: 1.8;">Nepal's most trusted vehicle rental platform. Making transportation accessible, affordable, and easy for everyone.</p>
                </div>
                <div class="footer-links">
                    <h4>Explore</h4>
                    <a href="#">Our Fleet</a>
                    <a href="#">Pricing Plan</a>
                    <a href="#">Safety Rules</a>
                </div>
                <div class="footer-links">
                    <h4>Support</h4>
                    <a href="#">Help Center</a>
                    <a href="#">Contact Us</a>
                    <a href="#">Terms of Service</a>
                </div>
                <div class="footer-links">
                    <h4>Newsletter</h4>
                    <p style="color: #94a3b8; font-size: 14px; margin-bottom: 15px;">Get the latest ride offers.</p>
                    <input type="text" placeholder="Email Address" style="background: #1e293b; border: none; padding: 12px; border-radius: 8px; color: white; width: 100%;">
                </div>
            </div>
            <div style="text-align: center; color: #475569; padding-top: 40px; border-top: 1px solid #1e293b;">
                &copy; 2026 भटभटे Rental - KTM, Nepal.
            </div>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const nav = document.getElementById('mainNav');
            if (window.scrollY > 100) nav.classList.add('sticky');
            else nav.classList.remove('sticky');
        });

        // Animations
        gsap.to(".reveal", {
            opacity: 1,
            y: 0,
            duration: 1,
            stagger: 0.2,
            scrollTrigger: {
                trigger: ".reveal",
                start: "top 85%"
            }
        });

        // Counter Animation
        const counters = document.querySelectorAll('.counter');
        counters.forEach(counter => {
            const updateCount = () => {
                const target = +counter.getAttribute('data-target');
                const count = +counter.innerText;
                const speed = 200;
                const inc = target / speed;
                if (count < target) {
                    counter.innerText = Math.ceil(count + inc);
                    setTimeout(updateCount, 10);
                } else {
                    counter.innerText = target + "+";
                }
            };
            updateCount();
        });
    </script>
</body>
</html>