<?php
session_start();
require_once '../includes/connection.php';

// 1. SECURITY CHECK: Ensure user is a Super Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: superadmin-login.php");
    exit();
}

$success = '';
$error = '';

// 2. GET VEHICLE ID FROM URL
$vehicle_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($vehicle_id <= 0) {
    header("Location: superadmin-vehicles.php");
    exit();
}

// 3. FETCH EXISTING VEHICLE DATA
$stmt = $conn->prepare("SELECT * FROM vehicles WHERE id = ?");
$stmt->bind_param("i", $vehicle_id);
$stmt->execute();
$result = $stmt->get_result();
$vehicle = $result->fetch_assoc();

if (!$vehicle) {
    header("Location: superadmin-vehicles.php");
    exit();
}

// 4. HANDLE UPDATE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $type = $_POST['type'];
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $price_per_day = (float) $_POST['price_per_day'];
    $fuel_type = mysqli_real_escape_string($conn, $_POST['fuel_type']);
    $transmission = mysqli_real_escape_string($conn, $_POST['transmission']);
    $seats = (int) $_POST['seats'];
    $features = mysqli_real_escape_string($conn, $_POST['features']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $availability = isset($_POST['availability']) ? 1 : 0;

    $file_path = $vehicle['image']; // Keep existing image by default

    // IMAGE UPLOAD LOGIC (Only if a new file is chosen)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = 'uploads/vehicles/';
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($file_ext, $allowed)) {
            $file_name = uniqid('vehicle_', true) . '.' . $file_ext;
            $new_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $new_path)) {
                // Delete old image file from server if it exists
                if (file_exists($vehicle['image'])) {
                    unlink($vehicle['image']);
                }
                $file_path = $new_path;
            } else {
                $error = 'Failed to upload new image';
            }
        } else {
            $error = 'Invalid file type for image';
        }
    }

    if (empty($name) || empty($location) || empty($price_per_day)) {
        $error = 'Please fill all required fields';
    }

    if (empty($error)) {
        $sql = "UPDATE vehicles SET 
                name=?, type=?, location=?, price_per_day=?, fuel_type=?, 
                transmission=?, seats=?, features=?, description=?, image=?, availability=? 
                WHERE id=?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssdssisssii", 
            $name, $type, $location, $price_per_day, $fuel_type, 
            $transmission, $seats, $features, $description, $file_path, $availability, $vehicle_id
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "Vehicle updated successfully!";
            header("Location: superadmin-vehicles.php");
            exit();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Vehicle - Super Admin</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); min-height: 100vh; color: white;">
    <div class="dashboard-layout">
        <aside class="sidebar super-admin-sidebar" style="background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); border-right: 1px solid rgba(148,163,184,0.1); width: 260px;">
            <div class="sidebar-header" style="padding: 2rem; text-align: center; border-bottom: 1px solid rgba(148, 163, 184, 0.1);">
                <img src="assets/images/logo.png" alt="Logo" style="height: 3rem;">
                <h3 style="color: white; margin-top: 1rem;">Super Admin</h3>
            </div>
            <nav class="sidebar-nav" style="padding: 1rem; display: flex; flex-direction: column;">
                <a href="superadmin-dashboard.php" class="nav-item">📊 Dashboard</a>
                <a href="superadmin-users.php" class="nav-item">👥 User Management</a>
                <a href="superadmin-vehicles.php" class="nav-item active">🚗 Vehicles</a>
                <a href="../logout.php" class="nav-item" style="margin-top: auto; color: #fca5a5;">🚪 Logout</a>
            </nav>
        </aside>

        <main class="main-content" style="flex: 1; padding: 2rem;">
            <div class="content-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 style="color: white;">Edit Vehicle</h1>
                    <p style="color: #94a3b8;">Updating: <?php echo htmlspecialchars($vehicle['name']); ?></p>
                </div>
                <a href="superadmin-vehicles.php" class="btn btn-secondary" style="color: white; border: 1px solid #94a3b8; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none;">← Back</a>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                    
                    <div class="data-card" style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; padding: 2rem;">
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label" style="color: white;">Vehicle Name *</label>
                            <input type="text" name="name" required style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;"
                                   value="<?php echo htmlspecialchars($vehicle['name']); ?>">
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label class="form-label" style="color: white;">Type</label>
                                <select name="type" style="width: 100%; padding: 0.75rem; background: #1e293b; border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;">
                                    <option value="Car" <?php if($vehicle['type'] == 'Car') echo 'selected'; ?>>Car</option>
                                    <option value="Bike" <?php if($vehicle['type'] == 'Bike') echo 'selected'; ?>>Bike</option>
                                    <option value="Scooter" <?php if($vehicle['type'] == 'Scooter') echo 'selected'; ?>>Scooter</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" style="color: white;">Location</label>
                                <input type="text" name="location" required style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;"
                                       value="<?php echo htmlspecialchars($vehicle['location']); ?>">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label class="form-label" style="color: white;">Price/Day</label>
                                <input type="number" name="price_per_day" step="0.01" style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;"
                                       value="<?php echo htmlspecialchars($vehicle['price_per_day']); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label" style="color: white;">Fuel</label>
                                <select name="fuel_type" style="width: 100%; padding: 0.75rem; background: #1e293b; border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;">
                                    <option value="Petrol" <?php if($vehicle['fuel_type'] == 'Petrol') echo 'selected'; ?>>Petrol</option>
                                    <option value="Diesel" <?php if($vehicle['fuel_type'] == 'Diesel') echo 'selected'; ?>>Diesel</option>
                                    <option value="Electric" <?php if($vehicle['fuel_type'] == 'Electric') echo 'selected'; ?>>Electric</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" style="color: white;">Seats</label>
                                <input type="number" name="seats" style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;"
                                       value="<?php echo htmlspecialchars($vehicle['seats']); ?>">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label" style="color: white;">Description</label>
                            <textarea name="description" rows="3" style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;"><?php echo htmlspecialchars($vehicle['description']); ?></textarea>
                        </div>

                        <button type="submit" style="width: 100%; padding: 1rem; background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%); color: white; border: none; border-radius: 0.5rem; font-weight: bold; cursor: pointer;">
                            💾 Save Changes
                        </button>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <div class="data-card" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255,255,255,0.1); padding: 1.5rem; border-radius: 1rem; text-align: center;">
                            <h4 style="color: white; margin-bottom: 1rem;">Current Image</h4>
                            <img src="<?php echo htmlspecialchars($vehicle['image']); ?>" alt="Vehicle" style="width: 100%; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid rgba(255,255,255,0.1);">
                            <label class="form-label" style="color: white; font-size: 0.8rem;">Change Photo:</label>
                            <input type="file" name="image" accept="image/*" style="width: 100%; font-size: 0.8rem; color: #94a3b8;">
                        </div>

                        <div class="data-card" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255,255,255,0.1); padding: 1.5rem; border-radius: 1rem;">
                            <h4 style="color: white; margin-bottom: 1rem;">Visibility</h4>
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="availability" value="1" <?php if($vehicle['availability']) echo 'checked'; ?> style="transform: scale(1.2);">
                                <span style="color: #94a3b8;">Available for Booking</span>
                            </label>
                        </div>
                    </div>

                </div>
            </form>
        </main>
    </div>

    <style>
        .nav-item { padding: 1rem 1.5rem; margin-bottom: 0.5rem; border-radius: 0.5rem; color: #94a3b8; text-decoration: none; transition: 0.3s; }
        .nav-item:hover { background: rgba(148, 163, 184, 0.1); color: white; }
        .nav-item.active { background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%); color: white; }
    </style>
</body>
</html>