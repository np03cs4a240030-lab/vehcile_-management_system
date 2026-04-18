<?php
session_start();
require_once '../includes/connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: superadmin-login.php");
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $type = $_POST['type'];
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $price_per_day = (float) $_POST['price_per_day'];
    $fuel_type = mysqli_real_escape_string($conn, $_POST['fuel_type']);
    $transmission = mysqli_real_escape_string($conn, $_POST['transmission']);
    $seats = (int) $_POST['seats'] ?? 2;
    $features = mysqli_real_escape_string($conn, $_POST['features']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $availability = isset($_POST['availability']) ? 1 : 0;

    $file_path = '';

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
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --bg-dark: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.1);
        }

        body {
            background: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            margin: 0;
            min-height: 100vh;
        }

        .dashboard-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            background: #1e293b;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 2rem;
            text-align: center;
            border-bottom: 1px solid var(--border);
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
        }

        .nav-item.active {
            background: var(--primary);
            color: white;
        }

        /* Content */
        .main-content {
            padding: 2.5rem;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
        }

        .data-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .form-input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border);
            color: white;
            border-radius: 6px;
            box-sizing: border-box;
        }

        .form-input:focus {
            border-color: var(--primary);
            outline: none;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: 0.2s;
            border: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            width: 100%;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-muted);
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.05);
            color: white;
        }

        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .note-card {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            padding: 1.5rem;
            border-radius: 12px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/logo.png" alt="Logo" style="height: 40px;">
                <h3 style="font-size: 1rem; margin-top: 1rem;">Super Admin</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="superadmin-dashboard.php" class="nav-item">Dashboard</a>
                <a href="superadmin-users.php" class="nav-item">User Management</a>
                <a href="superadmin-vehicles.php" class="nav-item active">Vehicles</a>
                <a href="superadmin-bookings.php" class="nav-item">Bookings</a>
                <a href="../logout.php" class="nav-item" style="margin-top: auto; color: #fca5a5;">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="content-header">
                <div>
                    <h1>Add New Vehicle</h1>
                    <p style="color: var(--text-muted);">Register a new asset to the fleet</p>
                </div>
                <a href="superadmin-vehicles.php" class="btn btn-secondary">Back to List</a>
            </div>

            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="data-card">
                        <h3 style="margin-top:0; margin-bottom: 1.5rem;">Vehicle Specifications</h3>
                        
                        <div class="form-group">
                            <label>Vehicle Name *</label>
                            <input type="text" name="name" class="form-input" required placeholder="e.g. Royal Enfield Classic 350" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Vehicle Type *</label>
                                <select name="type">
                                    <option value="Car">Car</option>
                                    <option value="Bike">Bike</option>
                                    <option value="Scooter">Scooter</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Location *</label>
                                <input type="text" name="location" class="form-input" placeholder="e.g. Kathmandu" required value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Price Per Day (NPR)</label>
                                <input type="number" name="price_per_day" class="form-input" required step="0.01" value="<?php echo isset($_POST['price_per_day']) ? htmlspecialchars($_POST['price_per_day']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Fuel Type</label>
                                <select name="fuel_type">
                                    <option value="Petrol">Petrol</option>
                                    <option value="Diesel">Diesel</option>
                                    <option value="Electric">Electric</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Features (comma separated)</label>
                            <input type="text" name="features" class="form-input" placeholder="ABS, Helmet, Luggage Rack" value="<?php echo isset($_POST['features']) ? htmlspecialchars($_POST['features']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="4"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Vehicle Image *</label>
                            <input type="file" name="image" required accept="image/*" style="color: var(--text-muted);">
                        </div>

                        <button type="submit" class="btn btn-primary">Publish Vehicle</button>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <div class="data-card" style="padding: 1.5rem;">
                            <h4 style="margin-top:0;">Status</h4>
                            <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                                <input type="checkbox" name="availability" value="1" checked style="width: 18px; height: 18px;">
                                <span style="color: var(--text-main);">Active for Booking</span>
                            </label>
                        </div>

                        
                    </div>
                </div>
            </form>
        </main>
    </div>
</body>
</html>