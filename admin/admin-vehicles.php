<?php
session_start();
require_once '../includes/connection.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('admin-login.php');
}

// Handle vehicle actions (same as super admin but with admin check)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_availability':
                $vehicle_id = (int)$_POST['vehicle_id'];
                $new_status = $_POST['current_status'] === '1' ? 0 : 1;
                $sql = "UPDATE vehicles SET availability = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $new_status, $vehicle_id);
                $stmt->execute();
                break;

            case 'delete':
                $vehicle_id = (int)$_POST['vehicle_id'];
                $sql = "DELETE FROM vehicles WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $vehicle_id);
                $stmt->execute();
                break;
        }

        // ✅ After any action, set a flash message
        $_SESSION['success'] = 'Action completed successfully!';
        redirect('admin-vehicles.php');
    }
}

// Get all vehicles
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';

$sql = "SELECT * FROM vehicles WHERE 1=1";
if ($search) {
    $sql .= " AND name LIKE '%$search%'";
}
if ($type_filter && $type_filter != 'all') {
    $sql .= " AND type = '$type_filter'";
}
$sql .= " ORDER BY created_at DESC";

$vehicles = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Management - Admin</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body style="background: var(--brand-light-gray);">
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar" style="background: var(--brand-dark-blue);">
            <div class="sidebar-header" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                <img src="../assets/images/logo.png" alt="Bhatbhatey Rental" style="height: 3rem;">
                <h3 style="color: white; margin-top: 1rem;">Admin Panel</h3>
            </div>

            <nav class="sidebar-nav">
                <a href="admin-dashboard.php" class="nav-item" style="color: white;">📊 Dashboard</a>
                <a href="admin-vehicles.php" class="nav-item active" style="color: white; background: var(--brand-orange);">🚗 Vehicles</a>
                <a href="admin-bookings.php" class="nav-item" style="color: white;">📅 Bookings</a>
                <a href="admin-users.php" class="nav-item" style="color: white;">👥 Users</a>
                <a href="../logout.php" class="nav-item" style="margin-top: auto; color: #fca5a5;">🚪 Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <div>
                    <h1>Vehicle Management</h1>
                    <p style="color: var(--text-secondary);">Manage all vehicles in the system</p>
                </div>
                <a href="admin-add-vehicle.php" class="btn btn-primary">+ Add Vehicle</a>
            </div>

            <!-- Search and Filters -->
            <div class="filters">
                <form method="GET" style="display: flex; gap: 1rem; flex: 1;">
                    <input type="text" name="search" placeholder="🔍 Search vehicles..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="form-input" style="flex: 1;">
                    <select name="type" class="form-input">
                        <option value="all">All Types</option>
                        <option value="Car" <?php echo $type_filter === 'Car' ? 'selected' : ''; ?>>🚗 Cars</option>
                        <option value="Bike" <?php echo $type_filter === 'Bike' ? 'selected' : ''; ?>>🏍️ Bikes</option>
                        <option value="Scooter" <?php echo $type_filter === 'Scooter' ? 'selected' : ''; ?>>🛵 Scooters</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>

            <!-- Vehicles Grid -->
            <div class="grid grid-cols-3">
                <?php if ($vehicles->num_rows > 0): ?>
                    <?php while($vehicle = $vehicles->fetch_assoc()): ?>
                    <div class="card vehicle-card hover-lift">
                        <div style="position: relative; overflow: hidden; height: 12rem;">
                            <img src="<?php echo htmlspecialchars($vehicle['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($vehicle['name']); ?>"
                                 style="width: 100%; height: 100%; object-fit: cover;">
                            <div class="badge-overlay">
                                <?php 
                                $icons = ['Car' => '🚗', 'Bike' => '🏍️', 'Scooter' => '🛵'];
                                echo $icons[$vehicle['type']] . ' ' . htmlspecialchars($vehicle['type']); 
                                ?>
                            </div>
                            <?php if (!$vehicle['availability']): ?>
                                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center;">
                                    <span class="badge badge-danger" style="font-size: 1rem; padding: 0.75rem 1.5rem;">
                                        ✕ Unavailable
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h3 style="font-size: 1.25rem; margin-bottom: 0.75rem;">
                                <?php echo htmlspecialchars($vehicle['name']); ?>
                            </h3>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; color: var(--text-secondary);">
                                <div>📍 <?php echo htmlspecialchars($vehicle['location']); ?></div>
                                <div>⛽ <?php echo htmlspecialchars($vehicle['fuel_type'] ?? 'N/A'); ?></div>
                                <div>⚙️ <?php echo htmlspecialchars($vehicle['transmission'] ?? 'N/A'); ?></div>
                                <?php if ($vehicle['seats']): ?>
                                <div>👥 <?php echo $vehicle['seats']; ?> Seats</div>
                                <?php endif; ?>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #f1f5f9;">
                                <div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary);">Price/Day</div>
                                    <div style="font-size: 1.25rem; font-weight: 700; color: var(--brand-orange);">
                                        NPR <?php echo number_format($vehicle['price_per_day'], 0); ?>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.5rem;">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_availability">
                                        <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $vehicle['availability']; ?>">
                                        <button type="submit" class="btn-icon" style="padding: 0.5rem;" title="Toggle Availability">
                                            <?php echo $vehicle['availability'] ? '✓' : '✕'; ?>
                                        </button>
                                    </form>
                                    <a href="admin-edit-vehicle.php?id=<?php echo $vehicle['id']; ?>" class="btn-icon" style="padding: 0.5rem;" title="Edit">✏️</a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this vehicle?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                                        <button type="submit" class="btn-icon" style="padding: 0.5rem; color: #ef4444;" title="Delete">🗑️</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 4rem;">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">🚗</div>
                        <h3>No Vehicles Found</h3>
                        <p style="color: var(--text-secondary); margin-bottom: 2rem;">Start by adding your first vehicle</p>
                        <a href="admin-add-vehicle.php" class="btn btn-primary">+ Add Vehicle</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <style>
        .sidebar-nav {
            padding: 1rem;
            display: flex;
            flex-direction: column;
            height: calc(100vh - 200px);
        }
        .sidebar-nav .nav-item {
            padding: 1rem 1.5rem;
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar-nav .nav-item:hover {
            background: rgba(255,255,255,0.1);
        }
    </style>
</body>
</html>
