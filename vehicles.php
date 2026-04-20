<?php
require_once 'config.php';

// Get filters
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? 'all';
$location_filter = $_GET['location'] ?? 'all';

// Base query
$sql = "SELECT * FROM vehicles WHERE availability = 1";
$params = [];
$types = "";

// Dynamic conditions
if (!empty($search)) {
    $sql .= " AND name LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if ($type_filter !== 'all') {
    $sql .= " AND type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

if ($location_filter !== 'all') {
    $sql .= " AND location LIKE ?";
    $params[] = "%$location_filter%";
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

// Prepare statement
$stmt = $conn->prepare($sql);

// Bind params if exist
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$vehicles = $stmt->get_result();

// FIX: Keep this as a result object so fetch_assoc() works in the loop below
$locations_result = $conn->query("SELECT DISTINCT location FROM vehicles ORDER BY location");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Vehicles - Bhatbhatey Rental</title>
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container container">
            <div>
                <a href="index.php">
                    <img src="assets/images/logo.png" alt="Bhatbhatey Rental" class="logo">
                </a>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="vehicles.php" style="color: var(--brand-orange);">Vehicles</a></li>
                <li><a href="index.php#pricing">Pricing</a></li>
                <li><a href="index.php#contact">Contact</a></li>
            </ul>
            <div class="nav-buttons">
                <?php if (isLoggedIn()): ?>
                    <span style="color: #cbd5e1;">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <a href="<?php echo $_SESSION['role'] === 'super_admin' ? './superadmin/superadmin-dashboard.php' : ($_SESSION['role'] === 'admin' ? './admin/admin-dashboard.php' : './user/user-dashboard.php'); ?>" class="btn btn-primary">Dashboard</a>
                <?php else: ?>
                    <a href="login.php" style="color: white; text-decoration: none;">Login</a>
                    <a href="register.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div style="background: linear-gradient(135deg, var(--brand-dark-blue) 0%, #1e293b 100%); color: white; padding: 3rem 0;">
        <div class="container">
            <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem;">Browse Vehicles</h1>
            <p style="color: #cbd5e1; font-size: 1.125rem;">Find the perfect ride for your journey</p>
        </div>
    </div>

    <div style="background: white; border-bottom: 1px solid #e2e8f0; padding: 1.5rem 0; position: sticky; top: 0; z-index: 90; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <div class="container">
            <form method="GET" class="filters" style="display: flex; gap: 1rem; align-items: center;">
                <div class="search-box" style="flex: 2;">
                    <input type="text" name="search" placeholder="🔍 Search vehicles..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="form-input"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.5rem;">
                </div>
                
                <div class="filter-select">
                    <select name="type" class="form-input" style="padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.5rem;">
                        <option value="all">🚗 All Types</option>
                        <option value="Car" <?php echo $type_filter === 'Car' ? 'selected' : ''; ?>>🚗 Cars</option>
                        <option value="Bike" <?php echo $type_filter === 'Bike' ? 'selected' : ''; ?>>🏍️ Bikes</option>
                        <option value="Scooter" <?php echo $type_filter === 'Scooter' ? 'selected' : ''; ?>>🛵 Scooters</option>
                    </select>
                </div>

                <div class="filter-select">
                    <select name="location" class="form-input" style="padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.5rem;">
                        <option value="all">📍 All Locations</option>
                        <?php 
                        // FIXED: Use the result object directly
                        if ($locations_result):
                            while($loc = $locations_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($loc['location']); ?>" 
                                        <?php echo $location_filter === $loc['location'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($loc['location']); ?>
                                </option>
                            <?php endwhile; 
                        endif; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="white-space: nowrap; padding: 0.75rem 1.5rem;">
                    Apply Filters
                </button>
            </form>
        </div>
    </div>

    <section class="section" style="padding: 4rem 0;">
        <div class="container">
            <?php if (!empty($search) || $type_filter !== 'all' || $location_filter !== 'all'): ?>
                <div style="margin-bottom: 2rem; padding: 1rem 1.5rem; background: #f0f9ff; border-left: 4px solid #3b82f6; border-radius: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>Filters Applied:</strong>
                            <?php if ($search): ?>
                                <span class="badge" style="background:#3b82f6; color:white; padding:2px 8px; border-radius:4px; margin-left: 0.5rem;">Search: "<?php echo htmlspecialchars($search); ?>"</span>
                            <?php endif; ?>
                            <?php if ($type_filter !== 'all'): ?>
                                <span class="badge" style="background:#3b82f6; color:white; padding:2px 8px; border-radius:4px; margin-left: 0.5rem;">Type: <?php echo $type_filter; ?></span>
                            <?php endif; ?>
                            <?php if ($location_filter !== 'all'): ?>
                                <span class="badge" style="background:#3b82f6; color:white; padding:2px 8px; border-radius:4px; margin-left: 0.5rem;">Location: <?php echo $location_filter; ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="vehicles.php" class="btn btn-secondary" style="font-size: 0.875rem; padding: 0.5rem 1rem; border: 1px solid #ccc; text-decoration: none; color: #333;">
                            Clear All
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <div style="margin-bottom: 2rem;">
                <h2 style="color: var(--brand-dark-blue); font-size: 1.5rem;">
                    Available Vehicles 
                    <span style="color: #64748b; font-size: 1rem; font-weight: normal;">
                        (<?php echo $vehicles->num_rows; ?> vehicles found)
                    </span>
                </h2>
            </div>

            <?php if ($vehicles->num_rows > 0): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem;">
                    <?php while($vehicle = $vehicles->fetch_assoc()): ?>
                    <div class="card vehicle-card hover-lift" style="border: 1px solid #e2e8f0; border-radius: 1rem; overflow: hidden; background: white;">
                        <div style="position: relative; overflow: hidden; height: 12rem;">
                            <img src="<?php echo htmlspecialchars($vehicle['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($vehicle['name']); ?>"
                                 style="width: 100%; height: 100%; object-fit: cover;">
                            <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.6); color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem;">
                                <?php 
                                $icons = ['Car' => '🚗', 'Bike' => '🏍️', 'Scooter' => '🛵'];
                                echo ($icons[$vehicle['type']] ?? '🚗') . ' ' . htmlspecialchars($vehicle['type']); 
                                ?>
                            </div>
                        </div>
                        <div class="card-body" style="padding: 1.5rem;">
                            <h3 style="font-size: 1.25rem; margin-bottom: 0.75rem; color: #1e293b;">
                                <?php echo htmlspecialchars($vehicle['name']); ?>
                            </h3>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem; padding: 1rem; background: #f8fafc; border-radius: 0.5rem;">
                                <div style="color: #64748b; font-size: 0.875rem;">
                                    📍 <?php echo htmlspecialchars($vehicle['location']); ?>
                                </div>
                                <div style="color: #64748b; font-size: 0.875rem;">
                                    ⛽ <?php echo htmlspecialchars($vehicle['fuel_type'] ?? 'Petrol'); ?>
                                </div>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #f1f5f9;">
                                <div>
                                    <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase;">Price per day</div>
                                    <div style="font-size: 1.25rem; font-weight: 700; color: #f97316;">
                                        NPR <?php echo number_format($vehicle['price_per_day'], 0); ?>
                                    </div>
                                </div>
                                <a href="vehicle-details.php?id=<?php echo $vehicle['id']; ?>" 
                                   class="btn btn-primary" 
                                   style="padding: 0.6rem 1.2rem; font-size: 0.875rem; background: #1e293b; color: white; text-decoration: none; border-radius: 0.4rem;">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 4rem 2rem;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">🔍</div>
                    <h3 style="color: #1e293b; margin-bottom: 0.5rem;">No Vehicles Found</h3>
                    <p style="color: #64748b; margin-bottom: 2rem;">Try adjusting your filters or search criteria</p>
                    <a href="vehicles.php" class="btn btn-primary" style="background: #3b82f6; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 0.5rem;">Clear Filters</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="footer" style="background: #0f172a; color: white; padding: 4rem 0 2rem 0;">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem;">
                <div>
                    <img src="assets/images/logo.png" alt="Bhatbhatey Rental" style="height: 40px; margin-bottom: 1rem;">
                    <p style="color: #94a3b8;">Your trusted vehicle rental partner across Nepal</p>
                </div>
                <div>
                    <h4 style="margin-bottom: 1.5rem;">Quick Links</h4>
                    <ul style="list-style: none; padding: 0; color: #94a3b8;">
                        <li style="margin-bottom: 0.5rem;"><a href="index.php" style="color: inherit; text-decoration: none;">Home</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="vehicles.php" style="color: inherit; text-decoration: none;">Browse Vehicles</a></li>
                    </ul>
                </div>
                <div>
                    <h4 style="margin-bottom: 1.5rem;">Contact</h4>
                    <ul style="list-style: none; padding: 0; color: #94a3b8;">
                        <li style="margin-bottom: 0.5rem;">📞 +977 9841234567</li>
                        <li style="margin-bottom: 0.5rem;">✉️ info@bhatbhatey.com</li>
                    </ul>
                </div>
            </div>
            <div style="border-top: 1px solid #1e293b; margin-top: 3rem; padding-top: 2rem; text-align: center; color: #64748b;">
                <p>&copy; 2026 Bhatbhatey Rental. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>