<?php
session_start();
require_once '../includes/connection.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: ../admin/admin-login.php");
    exit();
}

$success = '';
$error = '';

// 2. GET VEHICLE ID
$vehicle_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($vehicle_id <= 0) {
    header("Location: superadmin-vehicles.php");
    exit();
}

// 3. FETCH DATA
$stmt = $conn->prepare("SELECT * FROM vehicles WHERE id = ?");
$stmt->bind_param("i", $vehicle_id);
$stmt->execute();
$result = $stmt->get_result();
$vehicle = $result->fetch_assoc();

if (!$vehicle) {
    header("Location: superadmin-vehicles.php");
    exit();
}

// 4. HANDLE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name           = mysqli_real_escape_string($conn, $_POST['name']);
    $type           = $_POST['type'];
    $location       = mysqli_real_escape_string($conn, $_POST['location']);
    $price_per_day  = (float) $_POST['price_per_day'];
    $fuel_type      = mysqli_real_escape_string($conn, $_POST['fuel_type']);
    $transmission   = mysqli_real_escape_string($conn, $_POST['transmission']);
    $seats          = (int) $_POST['seats'];
    $features       = mysqli_real_escape_string($conn, $_POST['features']);
    $description    = mysqli_real_escape_string($conn, $_POST['description']);
    $availability   = isset($_POST['availability']) ? 1 : 0;
    $file_path      = $vehicle['image'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = 'uploads/vehicles/';
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($file_ext, $allowed)) {
            $file_name = uniqid('vehicle_', true) . '.' . $file_ext;
            $new_path = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $new_path)) {
                if (file_exists($vehicle['image'])) { unlink($vehicle['image']); }
                $file_path = $new_path;
            }
        }
    }

    if (empty($error)) {
        $sql = "UPDATE vehicles SET name=?, type=?, location=?, price_per_day=?, fuel_type=?, transmission=?, seats=?, features=?, description=?, image=?, availability=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssdssisssii", $name, $type, $location, $price_per_day, $fuel_type, $transmission, $seats, $features, $description, $file_path, $availability, $vehicle_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Vehicle updated successfully!";
            header("Location: superadmin-vehicles.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Vehicle - Super Admin</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    
    <style>
        /* PAGE STYLES */
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            color: white;
            margin: 0;
            font-family: 'Inter', sans-serif;
        }
        
        .dashboard-layout { display: flex; }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            border-right: 1px solid rgba(148,163,184,0.1);
            min-height: 100vh;
        }
        .sidebar-header {
            padding: 2rem;
            text-align: center;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }
        .nav-item {
            padding: 1rem 1.5rem;
            margin: 0.2rem 1rem;
            border-radius: 0.5rem;
            color: #94a3b8;
            text-decoration: none;
            display: block;
            transition: 0.3s;
        }
        .nav-item:hover { background: rgba(148, 163, 184, 0.1); color: white; }
        .nav-item.active { background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%); color: white; }

        /* CONTENT AREA */
        .main-content { flex: 1; padding: 2rem; }
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        /* CARDS & FORMS */
        .data-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 2rem;
        }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-size: 0.9rem; }
        
        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            border-radius: 0.5rem;
            box-sizing: border-box;
        }
        select option { background: #1e293b; }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-submit:hover { opacity: 0.9; }

        /* GRID HELPERS */
        .grid-main { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; }
        .grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .grid-3col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/logo.png" alt="Logo" style="height: 40px;">
                <h3>Super Admin</h3>
            </div>
            <nav style="margin-top: 1rem;">
                <a href="superadmin-dashboard.php" class="nav-item">📊 Dashboard</a>
                <a href="superadmin-users.php" class="nav-item">👥 Users</a>
                <a href="superadmin-vehicles.php" class="nav-item active">🚗 Vehicles</a>
                <a href="../logout.php" class="nav-item" style="color: #fca5a5; margin-top: 5rem;">🚪 Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="content-header">
                <div>
                    <h1>Edit Vehicle</h1>
                    <p style="color: #94a3b8;">Updating: <?php echo htmlspecialchars($vehicle['name']); ?></p>
                </div>
                <a href="superadmin-vehicles.php" style="color: white; border: 1px solid #475569; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none;">← Back</a>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="grid-main">
                    
                    <div class="data-card">
                        <div class="form-group">
                            <label class="form-label">Vehicle Name *</label>
                            <input type="text" name="name" required value="<?php echo htmlspecialchars($vehicle['name']); ?>">
                        </div>

                        <div class="grid-2col form-group">
                            <div>
                                <label class="form-label">Type</label>
                                <select name="type">
                                    <option value="Car" <?php if($vehicle['type'] == 'Car') echo 'selected'; ?>>Car</option>
                                    <option value="Bike" <?php if($vehicle['type'] == 'Bike') echo 'selected'; ?>>Bike</option>
                                    <option value="Scooter" <?php if($vehicle['type'] == 'Scooter') echo 'selected'; ?>>Scooter</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Location</label>
                                <input type="text" name="location" required value="<?php echo htmlspecialchars($vehicle['location']); ?>">
                            </div>
                        </div>

                        <div class="grid-3col form-group">
                            <div>
                                <label class="form-label">Price/Day</label>
                                <input type="number" name="price_per_day" step="0.01" value="<?php echo htmlspecialchars($vehicle['price_per_day']); ?>">
                            </div>
                            <div>
                                <label class="form-label">Fuel</label>
                                <select name="fuel_type">
                                    <option value="Petrol" <?php if($vehicle['fuel_type'] == 'Petrol') echo 'selected'; ?>>Petrol</option>
                                    <option value="Diesel" <?php if($vehicle['fuel_type'] == 'Diesel') echo 'selected'; ?>>Diesel</option>
                                    <option value="Electric" <?php if($vehicle['fuel_type'] == 'Electric') echo 'selected'; ?>>Electric</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Seats</label>
                                <input type="number" name="seats" value="<?php echo htmlspecialchars($vehicle['seats']); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="4"><?php echo htmlspecialchars($vehicle['description']); ?></textarea>
                        </div>

                        <button type="submit" class="btn-submit">Save Changes</button>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <div class="data-card" style="text-align: center;">
                            <h4 style="margin-top: 0;">Current Image</h4>
                            <img src="../<?php echo htmlspecialchars($vehicle['image']); ?>" style="width: 100%; border-radius: 0.5rem; margin-bottom: 1rem;">
                            <label class="form-label">Change Photo:</label>
                            <input type="file" name="image" accept="image/*">
                        </div>

                        <div class="data-card">
                            <h4 style="margin-top: 0;">Visibility</h4>
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="availability" value="1" <?php if($vehicle['availability']) echo 'checked'; ?> style="width: auto;">
                                <span>Available for Booking</span>
                            </label>
                        </div>
                    </div>

                </div>
            </form>
        </main>
    </div>
</body>
</html>