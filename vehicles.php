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

$locations_result = $conn->query("SELECT DISTINCT location FROM vehicles ORDER BY location");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Browse Vehicles - Bhatbhatey Rental</title>
<link rel="stylesheet" href="assets/css/main.css">
<style>
    .vehicle-card {
        transition: transform 0.22s ease, box-shadow 0.22s ease;
    }
    .vehicle-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 32px rgba(0,0,0,0.10);
    }
    .vehicle-card .card-img-wrap img {
        transition: transform 0.35s ease;
    }
    .vehicle-card:hover .card-img-wrap img {
        transform: scale(1.04);
    }
    .type-badge {
        backdrop-filter: blur(4px);
        background: rgba(0,0,0,0.55);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 600;
        letter-spacing: 0.03em;
    }
    .filter-bar input:focus,
    .filter-bar select:focus {
        outline: none;
        border-color: var(--brand-orange) !important;
        box-shadow: 0 0 0 3px rgba(249,115,22,0.12);
    }
    .spec-pill {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.8rem;
        color: #64748b;
        background: #f1f5f9;
        padding: 4px 10px;
        border-radius: 20px;
    }
    .price-tag {
        font-size: 1.3rem;
        font-weight: 800;
        color: #f97316;
        line-height: 1;
    }
    .price-tag span {
        font-size: 0.75rem;
        font-weight: 400;
        color: #94a3b8;
        display: block;
        margin-bottom: 2px;
    }
</style>
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
                <li><a href="./index.php#home">Home</a></li>
                <li><a href="./vehicles.php">List of Vehicles</a></li>
                <li><a href="./index.php#process">How it Works</a></li>
                <li><a href="./index.php#contact">Contact</a></li>
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

<!-- Hero banner -->
<div style="background: linear-gradient(135deg, var(--brand-dark-blue) 0%, #1e293b 100%); color: white; padding: 3.5rem 0;">
    <div class="container">
        <h1 style="font-size: 2.5rem; margin-bottom: 0.4rem;">Browse Vehicles</h1>
        <p style="color: #94a3b8; font-size: 1.05rem; margin: 0;">Find the perfect ride for your journey</p>
    </div>
</div>

<!-- Filter bar -->
<div class="filter-bar" style="background: white; border-bottom: 1px solid #e2e8f0; padding: 1.25rem 0; position: sticky; top: 0; z-index: 90; box-shadow: 0 4px 12px rgba(0,0,0,0.06);">
    <div class="container">
        <form method="GET" style="display: flex; gap: 0.875rem; align-items: center; flex-wrap: wrap;">

            <div style="flex: 2; min-width: 180px;">
                <input type="text" name="search" placeholder="Search vehicles..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    class="form-input"
                    style="width: 100%; padding: 0.7rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.9rem;">
            </div>

            <div>
                <select name="type" class="form-input" style="padding: 0.7rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.9rem; cursor: pointer;">
                    <option value="all">All Types</option>
                    <option value="Car"   <?php echo $type_filter === 'Car'    ? 'selected' : ''; ?>>Cars</option>
                    <option value="Bike"  <?php echo $type_filter === 'Bike'   ? 'selected' : ''; ?>>Bikes</option>
                    <option value="Scooter" <?php echo $type_filter === 'Scooter' ? 'selected' : ''; ?>>Scooters</option>
                </select>
            </div>

            <div>
                <select name="location" class="form-input" style="padding: 0.7rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.9rem; cursor: pointer;">
                    <option value="all">All Locations</option>
                    <?php if ($locations_result): ?>
                        <?php while($loc = $locations_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($loc['location']); ?>" <?php echo $location_filter === $loc['location'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($loc['location']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="white-space: nowrap; padding: 0.7rem 1.5rem;">
                Apply Filters
            </button>

            <?php if ($search || $type_filter !== 'all' || $location_filter !== 'all'): ?>
                <a href="vehicles.php" style="color: #64748b; font-size: 0.875rem; text-decoration: none; white-space: nowrap;">✕ Clear</a>
            <?php endif; ?>

        </form>
    </div>
</div>

<!-- Vehicle grid -->
<section style="padding: 3rem 0 5rem;">
    <div class="container">

        <?php
        // Reset result pointer
        $vehicles->data_seek(0);
        $count = $vehicles->num_rows;
        ?>

        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.75rem;">
            <h2 style="color: var(--brand-dark-blue); font-size: 1.25rem; margin: 0;">
                <?php if ($count > 0): ?>
                    <?php echo $count; ?> vehicle<?php echo $count !== 1 ? 's' : ''; ?> found
                    <?php if ($search): ?> for "<strong><?php echo htmlspecialchars($search); ?></strong>"<?php endif; ?>
                <?php else: ?>
                    No vehicles found
                <?php endif; ?>
            </h2>
            <?php if ($count > 0): ?>
                <span style="font-size: 0.8rem; color: #94a3b8;">Sorted by newest</span>
            <?php endif; ?>
        </div>

        <?php if ($count > 0): ?>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.75rem;">

                <?php while($vehicle = $vehicles->fetch_assoc()): ?>

                    <div class="card vehicle-card" style="border: 1px solid #e2e8f0; border-radius: 1rem; overflow: hidden; background: white;">

                        <!-- Image -->
                        <div class="card-img-wrap" style="position: relative; overflow: hidden; height: 13rem;">
                            <img src="<?php echo htmlspecialchars($vehicle['image']); ?>"
                                alt="<?php echo htmlspecialchars($vehicle['name']); ?>"
                                style="width: 100%; height: 100%; object-fit: cover;">

                            <!-- Type badge -->
                            <div class="type-badge" style="position: absolute; top: 10px; left: 12px;">
                                <?php
                                
                                echo htmlspecialchars($vehicle["type"]);
                                
                                ?>
                            </div>

                            <!-- Price ribbon on image -->
                            <div style="position: absolute; bottom: 0; right: 0; background: var(--brand-orange); color: white; padding: 6px 14px; border-top-left-radius: 10px; font-weight: 700; font-size: 0.9rem;">
                                NPR <?php echo number_format($vehicle['price_per_day'], 0); ?>/day
                            </div>
                        </div>

                        <!-- Body -->
                        <div style="padding: 1.25rem 1.25rem 1.4rem;">

                            <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0 0 0.9rem; color: #1e293b;">
                                <?php echo htmlspecialchars($vehicle['name']); ?>
                            </h3>

                            <!-- Spec pills -->
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 1.1rem;">
                                <span class="spec-pill"><?php echo htmlspecialchars($vehicle['location']); ?></span>
                                <span class="spec-pill"><?php echo htmlspecialchars($vehicle['fuel_type'] ?? 'Petrol'); ?></span>
                                <?php if (!empty($vehicle['transmission'])): ?>
                                    <span class="spec-pill"><?php echo htmlspecialchars($vehicle['transmission']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($vehicle['seats'])): ?>
                                    <span class="spec-pill"><?php echo $vehicle['seats']; ?> seats</span>
                                <?php endif; ?>
                            </div>

                            <!-- CTA -->
                            <a href="vehicle-details.php?id=<?php echo $vehicle['id']; ?>"
                               class="btn btn-primary"
                               style="display: block; text-align: center; padding: 0.65rem 1rem; font-size: 0.9rem; background: #1e293b; color: white; text-decoration: none; border-radius: 0.5rem; font-weight: 600; letter-spacing: 0.01em;">
                                View Details →
                            </a>

                        </div>
                    </div>

                <?php endwhile; ?>
            </div>

        <?php else: ?>

            <!-- Empty state -->
            <div style="text-align: center; padding: 5rem 2rem; background: white; border-radius: 1rem; border: 1px solid #e2e8f0;">
                
                <h3 style="font-size: 1.25rem; color: #1e293b; margin-bottom: 0.5rem;">No vehicles found</h3>
                <p style="color: #94a3b8; margin-bottom: 1.5rem;">Try adjusting your filters or search a different keyword.</p>
                <a href="vehicles.php" class="btn btn-primary" style="padding: 0.7rem 1.75rem;">Clear Filters</a>
            </div>

        <?php endif; ?>

    </div>
</section>

</body>
</html>