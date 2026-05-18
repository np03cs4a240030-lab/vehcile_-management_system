<?php
session_start();
require_once '../includes/connection.php';

// only admin can access this page
if (!isLoggedIn() || !hasRole('admin')) {
    redirect('admin-login.php');
}

// check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {

            // flip availability between 0 and 1
            case 'toggle_availability':
                $vehicle_id = (int)$_POST['vehicle_id'];
                $new_status = $_POST['current_status'] === '1' ? 0 : 1;
                $sql = "UPDATE vehicles SET availability = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $new_status, $vehicle_id);
                $stmt->execute();
                break;

            // remove the vehicle from db
            case 'delete':
                $vehicle_id = (int)$_POST['vehicle_id'];
                $sql = "DELETE FROM vehicles WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $vehicle_id);
                $stmt->execute();
                break;
        }

        // show success message and reload the page
        $_SESSION['success'] = 'Action completed successfully!';
        redirect('admin-vehicles.php');
    }
}

// grab search and filter values from url
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';

// build query based on filters
$sql = "SELECT * FROM vehicles WHERE 1=1";
if ($search) {
    // filter by name if search is given
    $sql .= " AND name LIKE '%$search%'";
}
if ($type_filter && $type_filter != 'all') {
    // filter by vehicle type
    $sql .= " AND type = '$type_filter'";
}
// newest first
$sql .= " ORDER BY created_at DESC";

$vehicles = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Management - Admin</title>
    <!-- main styles -->
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body style="background: var(--brand-light-gray);">
    <div class="dashboard-layout">

        <!-- icons library -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<!-- left sidebar -->
<aside class="sidebar">

    <div class="sidebar-logo">
        <img src="../assets/images/logo.png" alt="Logo">
        <span>Admin Panel</span>
    </div>

    <!-- nav links -->
    <nav class="sidebar-menu">

        <a href="admin-dashboard.php">
            <i class="fas fa-gauge-high"></i>
            Dashboard
        </a>

        <!-- active page -->
        <a href="admin-vehicles.php" class="active">
            <i class="fas fa-car"></i>
            Vehicles
        </a>

        <a href="admin-bookings.php">
            <i class="fas fa-calendar-days"></i>
            Bookings
        </a>

        <a href="admin-users.php">
            <i class="fas fa-users"></i>
            Users
        </a>

        <a href="admin-change-password.php">
            <i class="fas fa-key"></i>
            Change Password
        </a>

        <!-- sits at the bottom of sidebar -->
        <div class="logout-link">
            <a href="../logout.php">
                <i class="fas fa-right-from-bracket"></i>
                Logout
            </a>
        </div>

    </nav>

</aside>
        

        <!-- main content area -->
        <main class="main-content">
            <div class="content-header">
                <div>
                    <h1>Vehicle Management</h1>
                    <p style="color: var(--text-secondary);">Manage all vehicles in the system</p>
                </div>
                <a href="admin-add-vehicle.php" class="btn btn-primary">+ Add Vehicle</a>
            </div>

            <!-- search bar and type filter -->
            <div class="filters">
                <form method="GET" style="display: flex; gap: 1rem; flex: 1;">
                    <input type="text" name="search" placeholder="🔍 Search vehicles..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="form-input" style="flex: 1;">
                    <select name="type" class="form-input">
                        <option value="all">All Types</option>
                        <option value="Car" <?php echo $type_filter === 'Car' ? 'selected' : ''; ?>> Cars</option>
                        <option value="Bike" <?php echo $type_filter === 'Bike' ? 'selected' : ''; ?>Bikes</option>
                        <option value="Scooter" <?php echo $type_filter === 'Scooter' ? 'selected' : ''; ?>>Scooters</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>

            <!-- vehicle cards -->
            <div class="grid grid-cols-3">
                <?php if ($vehicles->num_rows > 0): ?>
                    <?php while($vehicle = $vehicles->fetch_assoc()): ?>
                    <div class="card vehicle-card hover-lift">

                        <!-- vehicle image with type badge -->
                        <div style="position: relative; overflow: hidden; height: 12rem;">
                            <img src="../<?php echo htmlspecialchars($vehicle['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($vehicle['name']); ?>"
                                 style="width: 100%; height: 100%; object-fit: cover;">
                                 <div class="badge-overlay">
    <?php echo htmlspecialchars($vehicle['type']); ?>
</div>
                            
                            <?php if (!$vehicle['availability']): ?>
                                <!-- dark overlay if vehicle is not available -->
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
                            
                            <!-- vehicle details grid -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; color: var(--text-secondary);">
                                <div> <?php echo htmlspecialchars($vehicle['location']); ?></div>
                                <div> <?php echo htmlspecialchars($vehicle['fuel_type'] ?? 'N/A'); ?></div>
                                <div> <?php echo htmlspecialchars($vehicle['transmission'] ?? 'N/A'); ?></div>
                                <?php if ($vehicle['seats']): ?>
                                <div>👥 <?php echo $vehicle['seats']; ?> Seats</div>
                                <?php endif; ?>
                            </div>

                            <!-- price and action buttons -->
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #f1f5f9;">
                                <div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary);">Price/Day</div>
                                    <div style="font-size: 1.25rem; font-weight: 700; color: var(--brand-orange);">
                                        NPR <?php echo number_format($vehicle['price_per_day'], 0); ?>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.5rem;">

                                    <!-- toggle available / unavailable -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_availability">
                                        <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $vehicle['availability']; ?>">
                                        <button type="submit" class="btn-icon" style="padding: 0.5rem;" title="Toggle Availability">
                                            <?php echo $vehicle['availability'] ? '✓' : '✕'; ?>
                                        </button>
                                    </form>

                                    <!-- edit button -->
                                    <a href="admin-edit-vehicle.php?id=<?php echo $vehicle['id']; ?>" class="btn-icon" style="padding: 0.5rem;" title="Edit">✏️</a>

                                    <!-- delete with confirmation -->
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
                    <!-- no vehicles found -->
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
        /* sidebar styles */
.sidebar{
    width:240px;
    background:#1e293b;
    color:white;
    display:flex;
    flex-direction:column;
    min-height:100vh;
    position:fixed;
    top:0;
    left:0;
}

/* logo area at top */
.sidebar-logo{
    padding:20px 24px;
    border-bottom:1px solid rgba(255,255,255,0.08);
    display:flex;
    align-items:center;
    gap:12px;
}

.sidebar-logo img{
    height:36px;
}

.sidebar-logo span{
    font-size:13px;
    color:#94a3b8;
    font-weight:600;
}

/* nav takes remaining height */
.sidebar-menu{
    padding:16px 12px;
    flex:1;
    display:flex;
    flex-direction:column;
}

.sidebar-menu a{
    display:flex;
    align-items:center;
    gap:12px;
    padding:11px 14px;
    border-radius:8px;
    color:#94a3b8;
    text-decoration:none;
    font-size:14px;
    font-weight:500;
    margin-bottom:4px;
    transition:all 0.2s;
}

.sidebar-menu a i{
    width:18px;
    text-align:center;
    font-size:15px;
}

.sidebar-menu a:hover{
    background:rgba(255,255,255,0.07);
    color:white;
}

/* orange highlight for current page */
.sidebar-menu a.active{
    background:#f97316;
    color:white;
}

/* push logout to the bottom */
.sidebar-menu .logout-link{
    margin-top:auto;
}

.sidebar-menu .logout-link a{
    color:#fca5a5;
}

.sidebar-menu .logout-link a:hover{
    background:rgba(239,68,68,0.15);
    color:#fca5a5;
}
    </style> 
</body>
</html>