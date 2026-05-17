<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('admin-login.php');
}

$success = '';
$error = '';
$vehicle_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// 1. FETCH EXISTING VEHICLE DATA
if ($vehicle_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $vehicle = $result->fetch_assoc();

    if (!$vehicle) {
        $_SESSION['error'] = "Vehicle not found!";
        redirect('admin-vehicles.php');
        exit();
    }
} else {
    redirect('admin-vehicles.php');
    exit();
}

// 2. HANDLE FORM SUBMISSION
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

    $file_path = $vehicle['image']; // Default to old image
    $db_path = $vehicle['image'];

    // IMAGE UPLOAD LOGIC (Only if a new file is selected)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = '../uploads/vehicles/';
        $db_dir = 'uploads/vehicles/';
        $file_tmp = $_FILES['image']['tmp_name'];
        $original_name = $_FILES['image']['name'];
        $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($file_ext, $allowed)) {
            $error = 'Only JPG, JPEG, PNG, WEBP files are allowed';
        } else {
            $file_name = uniqid('vehicle_', true) . '.' . $file_ext;
            $new_file_path = $upload_dir . $file_name;

            if (move_uploaded_file($file_tmp, $new_file_path)) {
                // Delete old image if it exists
                if (file_exists($vehicle['image'])) {
                    unlink($vehicle['image']);
                }
                $file_path = $new_file_path;
                $db_path = $db_dir . $file_name;
            } else {
                $error = 'Failed to upload new image';
            }
        }
    }

    if (empty($name) || empty($location) || empty($price_per_day)) {
        $error = 'Please fill all required fields';
    }

    if (empty($error)) {
        $stmt = $conn->prepare("UPDATE vehicles SET 
            name=?, type=?, location=?, price_per_day=?, fuel_type=?, 
            transmission=?, seats=?, features=?, description=?, image=?, availability=? 
            WHERE id=?");

        $stmt->bind_param(
            "sssdssisssii",
            $name,
            $type,
            $location,
            $price_per_day,
            $fuel_type,
            $transmission,
            $seats,
            $features,
            $description,
            $db_path,
            $availability,
            $vehicle_id
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "Vehicle updated successfully!";
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
    <title>Edit Vehicle - Admin</title>
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
                <a href="../logout.php" class="nav-item"
                    style="margin-top: auto; color: #fca5a5; text-decoration: none;">🚪 Logout</a>
            </nav>
        </aside>

        <main class="main-content" style="padding: 2rem; flex: 1;">
            <div class="content-header"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1>Edit Vehicle</h1>
                    <p style="color: var(--text-secondary);">Updating: <?php echo htmlspecialchars($vehicle['name']); ?>
                    </p>
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

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label">Vehicle Name *</label>
                            <input type="text" name="name" class="form-input" required
                                value="<?php echo htmlspecialchars($vehicle['name']); ?>">
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label class="form-label">Vehicle Type *</label>
                                <select name="type" class="form-input" required>
                                    <option value="Car" <?php echo $vehicle['type'] == 'Car' ? 'selected' : ''; ?>>Car
                                    </option>
                                    <option value="Bike" <?php echo $vehicle['type'] == 'Bike' ? 'selected' : ''; ?>>Bike
                                    </option>
                                    <option value="Scooter" <?php echo $vehicle['type'] == 'Scooter' ? 'selected' : ''; ?>>Scooter</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Location *</label>
                                <input type="text" name="location" class="form-input" required
                                    value="<?php echo htmlspecialchars($vehicle['location']); ?>">
                            </div>
                        </div>

                        <div
                            style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label class="form-label">Price/Day (NPR) *</label>
                                <input type="number" name="price_per_day" class="form-input" required step="0.01"
                                    value="<?php echo htmlspecialchars($vehicle['price_per_day']); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Fuel</label>
                                <select name="fuel_type" class="form-input">
                                    <option value="Petrol" <?php echo $vehicle['fuel_type'] == 'Petrol' ? 'selected' : ''; ?>>Petrol</option>
                                    <option value="Diesel" <?php echo $vehicle['fuel_type'] == 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                                    <option value="Electric" <?php echo $vehicle['fuel_type'] == 'Electric' ? 'selected' : ''; ?>>Electric</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Transmission</label>
                                <select name="transmission" class="form-input">
                                    <option value="Manual" <?php echo $vehicle['transmission'] == 'Manual' ? 'selected' : ''; ?>>Manual</option>
                                    <option value="Automatic" <?php echo $vehicle['transmission'] == 'Automatic' ? 'selected' : ''; ?>>Automatic</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label">Seats</label>
                            <input type="number" name="seats" class="form-input" min="1"
                                value="<?php echo htmlspecialchars($vehicle['seats']); ?>">
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label">Features</label>
                            <input type="text" name="features" class="form-input"
                                value="<?php echo htmlspecialchars($vehicle['features']); ?>">
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-input"
                                rows="4"><?php echo htmlspecialchars($vehicle['description']); ?></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label class="form-label">Update Image (Leave blank to keep current)</label>
                            <input type="file" name="image" class="form-input" accept="image/*">
                        </div>

                        <div
                            style="background: #fff7ed; padding: 1rem; border-radius: 0.5rem; border: 1px solid #fed7aa; margin-bottom: 2rem;">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="availability" value="1" <?php echo $vehicle['availability'] ? 'checked' : ''; ?>>
                                <span><strong>Active Listing</strong><br><small>Is this vehicle currently available for
                                        rent?</small></span>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary"
                            style="width: 100%; padding: 1rem; background: var(--brand-orange); color: white; border: none; border-radius: 0.5rem; font-weight: bold; cursor: pointer;">
                            💾 Save Changes
                        </button>
                    </div>

                    <div>
                        <div class="card"
                            style="background: white; padding: 1.5rem; border-radius: 1rem; text-align: center;">
                            <h4 style="margin-bottom: 1rem;">Current Image</h4>
                            <img src="../<?php echo htmlspecialchars($vehicle['image']); ?>"
                                style="width: 100%; border-radius: 0.5rem; object-fit: cover;">
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
        }
    </style>
</body>

</html>