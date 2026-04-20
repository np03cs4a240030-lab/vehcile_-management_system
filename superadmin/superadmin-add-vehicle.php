<?php
session_start();
require_once '../includes/connection.php';

// 1. SECURITY CHECK: Ensure user is a Super Admin
// Note: Using the session role check consistent with your login files
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: superadmin-login.php");
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic Sanitization (using mysqli_real_escape_string or similar via your connection)
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

    $file_path = '';

    // IMAGE UPLOAD LOGIC
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = 'uploads/vehicles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_tmp = $_FILES['image']['tmp_name'];
        $original_name = $_FILES['image']['name'];
        $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($file_ext, $allowed)) {
            $error = 'Only JPG, JPEG, PNG, WEBP files are allowed';
        } else {
            $file_name = uniqid('vehicle_', true) . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;

            if (!move_uploaded_file($file_tmp, $file_path)) {
                $error = 'Failed to upload image';
            }
        }
    } else {
        $error = 'Please upload a vehicle image';
    }

    if (empty($name) || empty($type) || empty($location) || empty($price_per_day)) {
        $error = 'Please fill all required fields';
    }

    if (empty($error)) {
        $sql = "INSERT INTO vehicles 
                (name, type, location, price_per_day, fuel_type, transmission, seats, features, description, image, availability) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssdssisssi", $name, $type, $location, $price_per_day, $fuel_type, $transmission, $seats, $features, $description, $file_path, $availability);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Vehicle added successfully!";
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
    <title>Add Vehicle - Super Admin</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); min-height: 100vh; color: white;">
    <div class="dashboard-layout">
        <aside class="sidebar super-admin-sidebar"
            style="background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); border-right: 1px solid rgba(148,163,184,0.1); width: 260px;">
            <div class="sidebar-header"
                style="padding: 2rem; text-align: center; border-bottom: 1px solid rgba(148, 163, 184, 0.1);">
                <img src="assets/images/logo.png" alt="Logo" style="height: 3rem;">
                <h3 style="color: white; margin-top: 1rem;">Super Admin</h3>
            </div>
            <nav class="sidebar-nav" style="padding: 1rem; display: flex; flex-direction: column;">
                <a href="superadmin-dashboard.php" class="nav-item">📊 Dashboard</a>
                <a href="superadmin-users.php" class="nav-item">👥 User Management</a>
                <a href="superadmin-vehicles.php" class="nav-item active">🚗 Vehicles</a>
                <a href="superadmin-bookings.php" class="nav-item">📅 Bookings</a>
                <a href="../logout.php" class="nav-item" style="margin-top: auto; color: #fca5a5;">🚪 Logout</a>
            </nav>
        </aside>

        <main class="main-content" style="flex: 1; padding: 2rem;">
            <div class="content-header"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 style="color: white;">Add New Vehicle</h1>
                    <p style="color: #94a3b8;">Add a new asset to the global rental fleet</p>
                </div>
                <a href="superadmin-vehicles.php" class="btn btn-secondary"
                    style="color: white; border: 1px solid #94a3b8; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none;">←
                    Back</a>
            </div>

            <?php if ($error): ?>
                <div
                    style="background: rgba(239, 68, 68, 0.2); color: #ef4444; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #ef4444;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">

                    <div class="data-card"
                        style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; padding: 2rem;">
                        <h3 style="margin-bottom: 1.5rem; color: white;">🚗 Vehicle Specifications</h3>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label"
                                style="color: white; display: block; margin-bottom: 0.5rem;">Vehicle Name *</label>
                            <input type="text" name="name" class="form-input" required
                                placeholder="e.g. Royal Enfield Classic 350"
                                style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;"
                                value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label class="form-label"
                                    style="color: white; display: block; margin-bottom: 0.5rem;">Vehicle Type *</label>
                                <select name="type"
                                    style="width: 100%; padding: 0.75rem; background: #1e293b; border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;">
                                    <option value="Car">Car</option>
                                    <option value="Bike">Bike</option>
                                    <option value="Scooter">Scooter</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label"
                                    style="color: white; display: block; margin-bottom: 0.5rem;">Location *</label>
                                <input type="text" name="location" placeholder="e.g. Kathmandu" required
                                    style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;"
                                    value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                            </div>
                        </div>

                        <div
                            style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label class="form-label"
                                    style="color: white; display: block; margin-bottom: 0.5rem;">Price/Day (NPR)</label>
                                <input type="number" name="price_per_day" required step="0.01"
                                    style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;"
                                    value="<?php echo isset($_POST['price_per_day']) ? htmlspecialchars($_POST['price_per_day']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"
                                    style="color: white; display: block; margin-bottom: 0.5rem;">Fuel</label>
                                <select name="fuel_type"
                                    style="width: 100%; padding: 0.75rem; background: #1e293b; border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;">
                                    <option value="Petrol">Petrol</option>
                                    <option value="Diesel">Diesel</option>
                                    <option value="Electric">Electric</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label"
                                    style="color: white; display: block; margin-bottom: 0.5rem;">Transmission</label>
                                <select name="transmission"
                                    style="width: 100%; padding: 0.75rem; background: #1e293b; border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;">
                                    <option value="Manual">Manual</option>
                                    <option value="Automatic">Automatic</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label"
                                style="color: white; display: block; margin-bottom: 0.5rem;">Features (comma
                                separated)</label>
                            <input type="text" name="features" placeholder="ABS, Helmet, Luggage Rack"
                                style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;"
                                value="<?php echo isset($_POST['features']) ? htmlspecialchars($_POST['features']) : ''; ?>">
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label"
                                style="color: white; display: block; margin-bottom: 0.5rem;">Description</label>
                            <textarea name="description" rows="3"
                                style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label"
                                style="color: white; display: block; margin-bottom: 0.5rem;">Vehicle Image *</label>
                            <input type="file" name="image" required accept="image/*"
                                style="width: 100%; padding: 0.5rem; color: #94a3b8;">
                        </div>

                        <button type="submit"
                            style="width: 100%; padding: 1rem; background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%); color: white; border: none; border-radius: 0.5rem; font-weight: bold; cursor: pointer; font-size: 1rem;">
                            🚀 Publish Vehicle
                        </button>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <div class="data-card"
                            style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255,255,255,0.1); padding: 1.5rem; border-radius: 1rem;">
                            <h4 style="color: white; margin-bottom: 1rem;">⚙️ Settings</h4>
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="availability" value="1" checked
                                    style="transform: scale(1.2);">
                                <span style="color: #94a3b8;">Active for Booking</span>
                            </label>
                        </div>

                        <div class="data-card"
                            style="background: rgba(147, 51, 234, 0.1); border: 1px solid rgba(147, 51, 234, 0.3); padding: 1.5rem; border-radius: 1rem;">
                            <h4 style="color: #e9d5ff; margin-bottom: 0.5rem;">System Note</h4>
                            <p style="color: #ddd6fe; font-size: 0.85rem;">Adding a vehicle here will make it visible to
                                all users across the platform immediately.</p>
                        </div>
                    </div>

                </div>
            </form>
        </main>
    </div>

    <style>
        .nav-item {
            padding: 1rem 1.5rem;
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
            color: #94a3b8;
            text-decoration: none;
            transition: 0.3s;
        }

        .nav-item:hover {
            background: rgba(148, 163, 184, 0.1);
            color: white;
        }

        .nav-item.active {
            background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);
            color: white;
        }
    </style>
</body>

</html>