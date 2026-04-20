<?php
require_once '../includes/connection.php';

if (!isLoggedIn() || !hasRole('super_admin')) {
    redirect('superadmin-login.php');
}

// Handle vehicle actions
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
        redirect('superadmin-vehicles.php');
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
    <title>Vehicle Management - Super Admin</title>
    <link rel="stylesheet" href="../..assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar super-admin-sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/logo.png" alt="Bhatbhatey Rental" style="height: 3rem;">
                <h3 style="color: white; margin-top: 1rem;">Super Admin</h3>
            </div>

            <nav class="sidebar-nav">
                <a href="superadmin-dashboard.php" class="nav-item">📊 Dashboard</a>
                <a href="superadmin-users.php" class="nav-item">👥 User Management</a>
                <a href="superadmin-vehicles.php" class="nav-item active">🚗 Vehicles</a>
                <a href="superadmin-bookings.php" class="nav-item">📅 Bookings</a>
                <a href="superadmin-settings.php" class="nav-item">⚙️ Settings</a>
                <a href="../logout.php" class="nav-item" style="margin-top: auto; color: #fca5a5;">🚪 Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <div>
                    <h1 style="color: white;">Vehicle Management</h1>
                    <p style="color: #94a3b8;">Manage all vehicles in the system</p>
                </div>
                <a href="superadmin-add-vehicle.php" class="btn btn-primary">+ Add Vehicle</a>
            </div>

            <!-- Search and Filters -->
            <div class="filters">
                <form method="GET" style="display: flex; gap: 1rem; flex: 1;">
                    <input type="text" name="search" placeholder="Search vehicles..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           style="flex: 1; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.05); color: white;">
                    <select name="type" style="padding: 0.75rem; border-radius: 0.5rem; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.05); color: white;">
                        <option value="all">All Types</option>
                        <option value="Car" <?php echo $type_filter === 'Car' ? 'selected' : ''; ?>>Cars</option>
                        <option value="Bike" <?php echo $type_filter === 'Bike' ? 'selected' : ''; ?>>Bikes</option>
                        <option value="Scooter" <?php echo $type_filter === 'Scooter' ? 'selected' : ''; ?>>Scooters</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>

            <!-- Vehicles Table -->
            <div class="data-card">
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Price/Day</th>
                                <th>Fuel</th>
                                <th>Transmission</th>
                                <th>Available</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($vehicles->num_rows > 0): ?>
                                <?php while($vehicle = $vehicles->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($vehicle['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($vehicle['name']); ?>"
                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 0.5rem;">
                                    </td>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($vehicle['name']); ?></td>
                                    <td>
                                        <span class="badge badge-primary">
                                            <?php 
                                            $icons = ['Car' => '🚗', 'Bike' => '🏍️', 'Scooter' => '🛵'];
                                            echo $icons[$vehicle['type']] . ' ' . $vehicle['type']; 
                                            ?>
                                        </span>
                                    </td>
                                    <td style="color: #94a3b8;">📍 <?php echo htmlspecialchars($vehicle['location']); ?></td>
                                    <td style="color: var(--brand-orange); font-weight: 600;">NPR <?php echo number_format($vehicle['price_per_day'], 0); ?></td>
                                    <td style="color: #94a3b8;">⛽ <?php echo htmlspecialchars($vehicle['fuel_type'] ?? 'N/A'); ?></td>
                                    <td style="color: #94a3b8;">⚙️ <?php echo htmlspecialchars($vehicle['transmission'] ?? 'N/A'); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_availability">
                                            <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $vehicle['availability']; ?>">
                                            <button type="submit" class="badge badge-<?php echo $vehicle['availability'] ? 'success' : 'danger'; ?>" 
                                                    style="cursor: pointer; border: none;">
                                                <?php echo $vehicle['availability'] ? '✓ Available' : '✕ Unavailable'; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="superadmin-edit-vehicle.php?id=<?php echo $vehicle['id']; ?>" class="btn-icon btn-edit">✏️</a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this vehicle?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                                                <button type="submit" class="btn-icon btn-delete" style="border: none; background: transparent; cursor: pointer;">🗑️</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 3rem; color: #94a3b8;">
                                        No vehicles found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <style>
        .super-admin-sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            border-right: 1px solid rgba(148, 163, 184, 0.1);
        }
        .sidebar-header {
            padding: 2rem;
            text-align: center;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }
        .sidebar-nav {
            padding: 1rem;
            display: flex;
            flex-direction: column;
            height: calc(100vh - 200px);
        }
        .nav-item {
            padding: 1rem 1.5rem;
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.3s;
        }
        .nav-item:hover {
            background: rgba(148, 163, 184, 0.1);
            color: white;
        }
        .nav-item.active {
            background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);
            color: white;
        }
        .data-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 1.5rem;
        }
        .data-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th {
            color: #94a3b8;
            font-size: 0.875rem;
            text-align: left;
            padding: 0.75rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }
        .data-table td {
            color: white;
            padding: 0.75rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.05);
        }
    </style>
</body>
</html>
