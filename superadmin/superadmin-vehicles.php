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
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --bg-dark: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.1);
            --success: #10b981;
            --danger: #ef4444;
        }

        body {
            background: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            margin: 0;
        }

        .dashboard-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }

        
        .sidebar {
            background: #1e293b;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 2.5rem 1.5rem;
            text-align: center;
        }

        .sidebar-header h3 {
            font-size: 1.1rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-top: 1rem;
        }

        .sidebar-nav {
            padding: 1rem;
            flex: 1;
        }

        .nav-item {
            display: block;
            padding: 0.85rem 1.25rem;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.2s ease;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
            padding-left: 1.5rem;
        }

        .nav-item.active {
            background: var(--primary);
            color: white;
        }

        /* Main Content */
        .main-content {
            padding: 2.5rem;
            max-width: 1400px;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        /* Forms & Filters */
        .filters {
            background: var(--card-bg);
            padding: 1.25rem;
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-bottom: 2rem;
        }

        .search-input, .select-input {
            padding: 0.75rem 1rem;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: rgba(15, 23, 42, 0.5);
            color: white;
            outline: none;
        }

        .search-input:focus {
            border-color: var(--primary);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: opacity 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-hover); }

        /* Table Design */
        .data-card {
            background: var(--card-bg);
            border-radius: 12px;
            border: 1px solid var(--border);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: rgba(0, 0, 0, 0.2);
            text-align: left;
            padding: 1rem;
            color: var(--text-muted);
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-success { background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid var(--success); }
        .badge-danger { background: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid var(--danger); }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-icon {
            padding: 6px;
            border-radius: 4px;
            filter: grayscale(1);
            transition: 0.2s;
        }

        .btn-icon:hover { filter: grayscale(0); transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/logo.png" alt="Logo" style="height: 40px;">
                <h3>Super Admin</h3>
            </div>

            <nav class="sidebar-nav">
                <a href="superadmin-dashboard.php" class="nav-item">Dashboard</a>
                <a href="superadmin-users.php" class="nav-item">Users</a>
                <a href="superadmin-vehicles.php" class="nav-item active">Vehicles</a>
                <a href="superadmin-bookings.php" class="nav-item">Bookings</a>
                <a href="superadmin-settings.php" class="nav-item">Settings</a>
                <a href="../logout.php" class="nav-item" style="margin-top: auto; color: #fca5a5;">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="content-header">
                <div>
                    <h1>Vehicle Management</h1>
                    <p style="color: var(--text-muted);">Manage your rental fleet</p>
                </div>
                <a href="superadmin-add-vehicle.php" class="btn btn-primary">Add New Vehicle</a>
            </div>

            <div class="filters">
                <form method="GET" style="display: flex; gap: 1rem;">
                    <input type="text" name="search" class="search-input" placeholder="Search by name..." style="flex: 1;" value="<?php echo htmlspecialchars($search); ?>">
                    <select name="type" class="select-input">
                        <option value="all">All Types</option>
                        <option value="Car" <?php echo $type_filter === 'Car' ? 'selected' : ''; ?>>Cars</option>
                        <option value="Bike" <?php echo $type_filter === 'Bike' ? 'selected' : ''; ?>>Bikes</option>
                        <option value="Scooter" <?php echo $type_filter === 'Scooter' ? 'selected' : ''; ?>>Scooters</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
            </div>

            <div class="data-card">
                <table>
                    <thead>
                        <tr>
                            <th>Preview</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Price/Day</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($vehicles->num_rows > 0): ?>
                            <?php while($vehicle = $vehicles->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <img src="../admin/<?php echo htmlspecialchars($vehicle['image']); ?>" 

                                    
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                                </td>
                                <td style="font-weight: 500;"><?php echo htmlspecialchars($vehicle['name']); ?></td>
                                <td><span style="color: var(--text-muted);"><?php echo $vehicle['type']; ?></span></td>
                                <td style="color: var(--primary); font-weight: 600;">NPR <?php echo number_format($vehicle['price_per_day']); ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="toggle_availability">
                                        <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $vehicle['availability']; ?>">
                                        <button type="submit" class="badge <?php echo $vehicle['availability'] ? 'badge-success' : 'badge-danger'; ?>" style="cursor:pointer; background:none;">
                                            <?php echo $vehicle['availability'] ? 'Active' : 'Inactive'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="superadmin-edit-vehicle.php?id=<?php echo $vehicle['id']; ?>" class="btn-icon">Edit</a>
                                        <form method="POST" onsubmit="return confirm('Delete this vehicle?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                                            <button type="submit" class="btn-icon" style="border:none; background:none; color:var(--danger); cursor:pointer;">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; color: var(--text-muted);">No records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>