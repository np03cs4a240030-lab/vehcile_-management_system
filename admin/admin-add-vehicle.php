<?php
require_once '../config.php';

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
    $db_path = '';

    // IMAGE UPLOAD LOGIC
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = '../uploads/vehicles/';
        $db_dir = 'uploads/vehicles/';
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
            $db_path = $db_dir . $file_name;

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
            $db_path,
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

<body class="body-bg">

<div class="dashboard-layout">

    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/images/logo.png" alt="Logo">
            <h3>Admin Panel</h3>
        </div>

        <nav class="sidebar-nav">
            <a href="admin-dashboard.php" class="nav-item">📊 Dashboard</a>
            <a href="admin-vehicles.php" class="nav-item active">🚗 Vehicles</a>
            <a href="admin-bookings.php" class="nav-item">📅 Bookings</a>
            <a href="admin-users.php" class="nav-item">👥 Users</a>
            <a href="../logout.php" class="nav-item logout">🚪 Logout</a>
        </nav>
    </aside>

    <main class="main-content">

        <div class="content-header">
            <div>
                <h1>Add New Vehicle</h1>
                <p class="text-secondary">Expand your rental fleet</p>
            </div>
            <a href="admin-vehicles.php" class="btn btn-secondary">← Back</a>
        </div>

        <?php if ($error): ?>
        <div class="alert-error">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <div class="main-grid">

                <div class="card">
                    <h3 class="card-title">Vehicle Specifications</h3>

                    <div class="form-group">
                        <label class="form-label">Vehicle Name *</label>
                        <input type="text" name="name" class="form-input" required>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Vehicle Type *</label>
                            <select name="type" class="form-input">
                                <option>Car</option>
                                <option>Bike</option>
                                <option>Scooter</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Location *</label>
                            <input type="text" name="location" class="form-input">
                        </div>
                    </div>

                    <div class="grid-3">
                        <div class="form-group">
                            <label class="form-label">Price/Day (NPR)</label>
                            <input type="number" name="price_per_day" class="form-input">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Fuel</label>
                            <select name="fuel_type" class="form-input">
                                <option>Petrol</option>
                                <option>Diesel</option>
                                <option>Electric</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Transmission</label>
                            <select name="transmission" class="form-input">
                                <option>Manual</option>
                                <option>Automatic</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Seats</label>
                        <input type="number" name="seats" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Features</label>
                        <input type="text" name="features" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-input"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Vehicle Image *</label>
                        <input type="file" name="image" class="form-input">
                    </div>

                    <div class="availability-box">
                        <label class="availability-label">
                            <input type="checkbox" name="availability" checked>
                            <span>
                                <strong>Available for Booking</strong><br>
                                <small>Visible to customers immediately</small>
                            </span>
                        </label>
                    </div>

                    <button type="submit" class="btn-primary">
                        ✓ Add Vehicle to Fleet
                    </button>

                </div>

            </div>

        </form>

    </main>

</div>


<style>

.body-bg {
    background: var(--brand-light-gray);
}

.sidebar {
    background: var(--brand-dark-blue);
    width: 260px;
    min-height: 100vh;
}

.sidebar-header {
    padding: 2rem 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-header img {
    height: 3rem;
}

.sidebar-header h3 {
    color: white;
    margin-top: 1rem;
}

.sidebar-nav {
    padding: 1rem;
    display: flex;
    flex-direction: column;
}

.nav-item {
    padding: 1rem 1.5rem;
    margin-bottom: 0.5rem;
    border-radius: 0.5rem;
    color: white;
    text-decoration: none;
    transition: 0.3s;
}

.nav-item:hover {
    background: rgba(255,255,255,0.1);
}

.nav-item.active {
    background: var(--brand-orange);
}

.nav-item.logout {
    margin-top: auto;
    color: #fca5a5;
}

.main-content {
    padding: 2rem;
    flex: 1;
}

.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.text-secondary {
    color: var(--text-secondary);
}

.main-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
}

.card {
    background: white;
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
}

.card-title {
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
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

.grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.availability-box {
    background: #f0f9ff;
    padding: 1rem;
    border-radius: 0.5rem;
    border: 1px solid #bfdbfe;
    margin-bottom: 2rem;
}

.availability-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.alert-error {
    background: #fee2e2;
    color: #b91c1c;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}

.btn-primary {
    width: 100%;
    padding: 1rem;
    background: var(--brand-orange);
    color: white;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    font-weight: bold;
}

</style>

</body>
</html>