<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('admin-login.php');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $type = $_POST['type'];
    $location = sanitize($_POST['location']);
    $price_per_day = (float) $_POST['price_per_day'];
    $fuel_type = sanitize($_POST['fuel_type']);
    $transmission = sanitize($_POST['transmission']);
    $seats = (int) $_POST['seats'];
    $features = sanitize($_POST['features']);
    $description = sanitize($_POST['description']);
    $availability = isset($_POST['availability']) ? 1 : 0;

    $file_path = '';

    // IMAGE UPLOAD LOGIC
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = 'uploads/';
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
        $error = 'Please upload an image';
    }

    if (empty($name) || empty($type) || empty($location) || empty($price_per_day)) {
        $error = 'Please fill all required fields';
    }

    if (empty($error)) {
        $stmt = $conn->prepare("INSERT INTO vehicles 
            (name, type, location, price_per_day, fuel_type, transmission, seats, features, description, image, availability) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "sssdssisssi",
            $name,
            $type,
            $location,
            $price_per_day,
            $fuel_type,
            $transmission,
            $seats,
            $features,
            $description,
            $file_path,
            $availability
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "Vehicle added successfully!";
            redirect('admin-vehicles.php');
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
    <title>Add Vehicle - Admin</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body style="background: var(--brand-light-gray);">
    <div class="dashboard-layout">
        <aside class="sidebar" style="background: var(--brand-dark-blue); width: 260px; min-height: 100vh;">
            <div class="sidebar-header" style="padding: 2rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1);">
                <img src="../assets/images/logo.png" alt="Logo" style="height: 3rem;">
                <h3 style="color: white; margin-top: 1rem;">Admin Panel</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="admin-dashboard.php" class="nav-item" style="color: white; text-decoration: none;">📊
                    Dashboard</a>
                <a href="admin-vehicles.php" class="nav-item active"
                    style="color: white; background: var(--brand-orange); text-decoration: none;">🚗 Vehicles</a>
                <a href="admin-bookings.php" class="nav-item" style="color: white; text-decoration: none;">📅
                    Bookings</a>
                <a href="admin-users.php" class="nav-item" style="color: white; text-decoration: none;">👥 Users</a>
                <a href="../logout.php" class="nav-item"
                    style="margin-top: auto; color: #fca5a5; text-decoration: none;">🚪 Logout</a>
            </nav>
        </aside>

        <main class="main-content" style="padding: 2rem; flex: 1;">
            <div class="content-header"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1>Add New Vehicle</h1>
                    <p style="color: var(--text-secondary);">Expand your rental fleet</p>
                </div>
                <a href="admin-vehicles.php" class="btn btn-secondary">← Back</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"
                    style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                    <div class="card"
                        style="background: white; padding: 2rem; border-radius: 1rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                        <h3 style="margin-bottom: 1.5rem;">🚗 Vehicle Specifications</h3>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label">Vehicle Name *</label>
                            <input type="text" name="name" class="form-input" required placeholder="e.g. Honda Civic"
                                value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label class="form-label">Vehicle Type *</label>
                                <select name="type" class="form-input" required>
                                    <option value="Car">Car</option>
                                    <option value="Bike">Bike</option>
                                    <option value="Scooter">Scooter</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Location *</label>
                                <input type="text" name="location" class="form-input" required placeholder="City name"
                                    value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                            </div>
                        </div>

                        <div
                            style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label class="form-label">Price/Day (NPR) *</label>
                                <input type="number" name="price_per_day" class="form-input" required step="0.01"
                                    value="<?php echo isset($_POST['price_per_day']) ? htmlspecialchars($_POST['price_per_day']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Fuel</label>
                                <select name="fuel_type" class="form-input">
                                    <option value="Petrol">Petrol</option>
                                    <option value="Diesel">Diesel</option>
                                    <option value="Electric">Electric</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Transmission</label>
                                <select name="transmission" class="form-input">
                                    <option value="Manual">Manual</option>
                                    <option value="Automatic">Automatic</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label">Seats</label>
                            <input type="number" name="seats" class="form-input" min="1"
                                value="<?php echo isset($_POST['seats']) ? htmlspecialchars($_POST['seats']) : '2'; ?>">
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label">Features (comma separated)</label>
                            <input type="text" name="features" class="form-input" placeholder="AC, GPS, Bluetooth"
                                value="<?php echo isset($_POST['features']) ? htmlspecialchars($_POST['features']) : ''; ?>">
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-input"
                                rows="4"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label">Vehicle Image *</label>
                            <input type="file" name="image" class="form-input" required accept="image/*">
                        </div>

                        <div
                            style="background: #f0f9ff; padding: 1rem; border-radius: 0.5rem; border: 1px solid #bfdbfe; margin-bottom: 2rem;">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="availability" value="1" checked
                                    style="transform: scale(1.2);">
                                <span><strong>Available for Booking</strong><br><small>Visible to customers
                                        immediately</small></span>
                            </label>
                        </div>

                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" class="btn btn-primary"
                                style="flex: 1; padding: 1rem; background: var(--brand-orange); color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: bold;">
                                ✓ Add Vehicle to Fleet
                            </button>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <div class="card" style="background: white; padding: 1.5rem; border-radius: 1rem;">
                            <h4 style="margin-bottom: 1rem;">💡 Photo Tips</h4>
                            <ul style="font-size: 0.875rem; color: var(--text-secondary); padding-left: 1.2rem;">
                                <li>Use landscape orientation</li>
                                <li>Ensure the background is clean</li>
                                <li>Show both exterior and interior</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <style>
        .sidebar-nav {
            padding: 1rem;
            display: flex;
            flex-direction: column;
        }

        .nav-item {
            padding: 1rem 1.5rem;
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
            transition: 0.3s;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--brand-dark-blue);
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 1rem;
        }
    </style>
</body>

</html>