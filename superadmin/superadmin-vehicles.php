<?php


require_once '../includes/connection.php';

// Access Control: Ensure the user is authorized to view this page
if (!isLoggedIn() || !hasRole('super_admin')) {
    redirect('../admin/admin-login.php');
}

/* 
   POST REQUEST HANDLER
   Handles database updates for toggling status and deleting records
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            
            // Toggle vehicle availability (Active/Inactive)
            case 'toggle_availability':
                $vehicle_id = (int)$_POST['vehicle_id'];
                // If current status is 1 (Active), set to 0 (Inactive), and vice versa
                $new_status = $_POST['current_status'] === '1' ? 0 : 1;
                
                $sql = "UPDATE vehicles SET availability = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $new_status, $vehicle_id);
                $stmt->execute();
                break;
            
            // Remove vehicle from database
            case 'delete':
                $vehicle_id = (int)$_POST['vehicle_id'];
                $sql = "DELETE FROM vehicles WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $vehicle_id);
                $stmt->execute();
                break;
        }
        // Redirect to prevent form resubmission on page refresh
        redirect('superadmin-vehicles.php');
    }
}

/* 
   GET REQUEST HANDLER (SEARCH & FILTER)
   Fetches the list of vehicles based on user input
 */
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';


$sql = "SELECT * FROM vehicles WHERE 1=1";

// Append search condition if user provided a name
if ($search) {
    $sql .= " AND name LIKE '%$search%'";
}

// Append type filter if a specific category is selected
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
    
    <!-- CSS Dependencies -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
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
            display: flex;
        }

        .dashboard-layout { display: flex; min-height: 100vh; }

        /* Sidebar Navigation Styling */
        .sidebar {
            width: 240px; background: rgba(255,255,255,0.03);
            border-right: 1px solid rgba(255,255,255,0.07);
            display: flex; flex-direction: column; min-height: 100vh;
            position: fixed; top: 0; left: 0;
        }
        .sidebar-logo { padding: 22px 24px; border-bottom: 1px solid rgba(255,255,255,0.07); display: flex; align-items: center; gap: 12px; }
        .sidebar-logo img { height: 36px; }
        .sidebar-logo-text .title { font-size: 14px; font-weight: 700; color: white; }
        .sidebar-logo-text .sub { font-size: 11px; color: #a855f7; background: rgba(168,85,247,0.15); border: 1px solid rgba(168,85,247,0.3); border-radius: 20px; padding: 1px 8px; display: inline-block; margin-top: 2px; }
        
        .sidebar-menu { padding: 16px 12px; flex: 1; display: flex; flex-direction: column; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; padding: 11px 14px; border-radius: 10px; color: #94a3b8; text-decoration: none; font-size: 14px; font-weight: 500; margin-bottom: 4px; transition: all 0.2s; }
        .sidebar-menu a i { width: 18px; text-align: center; }
        .sidebar-menu a:hover { background: rgba(255,255,255,0.07); color: white; }
        .sidebar-menu a.active { background: linear-gradient(135deg, #9333ea, #7c3aed); color: white; }
        
        .logout-link { margin-top: auto; }
        .logout-link a { color: #fca5a5 !important; }
        .logout-link a:hover { background: rgba(239,68,68,0.1) !important; }

        /* Main Content Layout */
        .main-content { margin-left: 240px; flex: 1; padding: 28px; }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-header h1 { font-size: 22px; font-weight: 700; color: white; }
        .page-header p { color: #94a3b8; font-size: 14px; margin-top: 3px; }

        /* Filter Form Controls */
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
        .btn { padding: 0.75rem 1.5rem; border-radius: 6px; border: none; cursor: pointer; font-weight: 500; transition: 0.2s; text-decoration: none; display: inline-block; }
        .btn-primary { background: var(--primary); color: white; }

        /* Table Design */
        .data-card { background: var(--card-bg); border-radius: 12px; border: 1px solid var(--border); overflow: hidden; backdrop-filter: blur(10px); }
        table { width: 100%; border-collapse: collapse; }
        th { background: rgba(0, 0, 0, 0.2); text-align: left; padding: 1rem; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; }
        td { padding: 1rem; border-bottom: 1px solid var(--border); }
        
        /* Status Badges */
        .badge { padding: 0.4rem 0.8rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; border: none; }
        .badge-success { background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid var(--success); }
        .badge-danger { background: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid var(--danger); }

        .action-buttons { display: flex; gap: 10px; }
        .btn-icon { padding: 6px; border-radius: 4px; filter: grayscale(1); transition: 0.2s; text-decoration: none; font-size: 13px; }
        .btn-icon:hover { filter: grayscale(0); transform: translateY(-2px); }
    </style>
</head>
<body>

    <div class="dashboard-layout">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="../assets/images/logo.png" alt="Logo">
                <div class="sidebar-logo-text">
                    <div class="title">Bhatbhatey</div>
                    <div class="sub">Super Admin</div>
                </div>
            </div>
            <nav class="sidebar-menu">
                <a href="superadmin-dashboard.php"><i class="fas fa-gauge-high"></i> Dashboard</a>
                <a href="superadmin-users.php"><i class="fas fa-users"></i> Users</a>
                <a href="superadmin-admins.php"><i class="fas fa-user-shield"></i> Admins</a>
                <a href="superadmin-vehicles.php" class="active"><i class="fas fa-car"></i> Vehicles</a>
                <a href="superadmin-bookings.php"><i class="fas fa-calendar-days"></i> Bookings</a>
                <div class="logout-link">
                    <a href="../logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
                </div>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Top Header -->
            <div class="page-header">
                <div>
                    <h1>Vehicle Management</h1>
                    <p>Manage your rental fleet</p>
                </div>
                <a href="superadmin-add-vehicle.php" class="btn btn-primary">Add New Vehicle</a>
            </div>

            <!-- Search and Filter Bar -->
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

            <!-- Vehicle Table -->
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
                                <!-- Image Preview -->
                                <td>
                                    <img src="../admin/<?php echo htmlspecialchars($vehicle['image']); ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                                </td>
                                
                                <td style="font-weight: 500;"><?php echo htmlspecialchars($vehicle['name']); ?></td>
                                <td><span style="color: var(--text-muted);"><?php echo $vehicle['type']; ?></span></td>
                                <td style="color: var(--primary); font-weight: 600;">NPR <?php echo number_format($vehicle['price_per_day']); ?></td>
                                
                                <!-- Status Toggle Button -->
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
                                
                                <!-- Action Buttons (Edit/Delete) -->
                                <td>
                                    <div class="action-buttons">
                                        <a href="superadmin-edit-vehicle.php?id=<?php echo $vehicle['id']; ?>" class="btn-icon" style="color: var(--primary);">Edit</a>
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
                            <!-- Empty State -->
                            <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 20px;">No records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

</body>
</html>