<?php
// load config, db connection, and helper functions
require_once '../config.php';

// only logged-in admins can access this page
if (!isLoggedIn() || !isAdmin()) {
    redirect('admin-login.php');
}

// holds success/error messages shown to the user
$success = '';
$error = '';

// handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // sanitize and collect form fields
    $name          = sanitize($_POST['name']);
    $type          = $_POST['type'];
    $location      = sanitize($_POST['location']);
    $price_per_day = (float) $_POST['price_per_day'];
    $fuel_type     = sanitize($_POST['fuel_type']);
    $transmission  = sanitize($_POST['transmission']);
    $seats         = (int) $_POST['seats'];
    $features      = sanitize($_POST['features']);
    $description   = sanitize($_POST['description']);
    // checkbox: 1 if checked, 0 if not
    $availability  = isset($_POST['availability']) ? 1 : 0;

    // paths used for saving and storing the image
    $file_path = '';
    $db_path   = '';

    // handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {

        $upload_dir = '../uploads/vehicles/'; // where the file is saved on disk
        $db_dir     = 'uploads/vehicles/';    // relative path stored in the database

        // create the upload folder if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_tmp      = $_FILES['image']['tmp_name'];
        $original_name = $_FILES['image']['name'];
        $file_ext      = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $allowed       = ['jpg', 'jpeg', 'png', 'webp']; // accepted image types

        if (!in_array($file_ext, $allowed)) {
            $error = 'Only JPG, JPEG, PNG, WEBP files are allowed';
        } else {
            // generate a unique filename to avoid collisions
            $file_name = uniqid('vehicle_', true) . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;
            $db_path   = $db_dir . $file_name;

            // move file from temp location to the uploads folder
            if (!move_uploaded_file($file_tmp, $file_path)) {
                $error = 'Failed to upload image';
            }
        }
    } else {
        $error = 'Please upload an image';
    }

    // make sure required fields are not empty
    if (empty($name) || empty($type) || empty($location) || empty($price_per_day)) {
        $error = 'Please fill all required fields';
    }

    // insert into db only if no errors
    if (empty($error)) {
        $stmt = $conn->prepare("INSERT INTO vehicles 
            (name, type, location, price_per_day, fuel_type, transmission, seats, features, description, image, availability) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // bind all values: s=string, d=double, i=integer
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
            // redirect back to vehicles list on success
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

<!-- global styles and dashboard layout -->
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body class="body-bg">

<div class="dashboard-layout">

    <!-- font awesome icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- left sidebar -->
    <aside class="sidebar">

        <!-- logo and panel title -->
        <div class="sidebar-logo">
            <img src="../assets/images/logo.png" alt="Logo">
            <span>Admin Panel</span>
        </div>

        <!-- navigation links -->
        <nav class="sidebar-menu">

            <a href="admin-dashboard.php">
                <i class="fas fa-gauge-high"></i>
                Dashboard
            </a>

            <!-- vehicles is the active section -->
            <a href="admin-vehicles.php" class="active">
                <i class="fas fa-car"></i>
                Vehicles
            </a>

            <a href="admin-bookings.php">
                <i class="fas fa-calendar-days"></i>
                Bookings
            </a>

            
        <a href="admin-tickets.php"><i class="fas fa-ticket-alt"></i> Support Tickets</a>
        <a href="admin-users.php">
                <i class="fas fa-users"></i>
                Users
            </a>

            <a href="admin-change-password.php">
                <i class="fas fa-key"></i>
                Change Password
            </a>

            <!-- logout pinned to the bottom of the sidebar -->
            <div class="logout-link">
                <a href="../logout.php">
                    <i class="fas fa-right-from-bracket"></i>
                    Logout
                </a>
            </div>

        </nav>

    </aside>

    <!-- main page content -->
    <main class="main-content">

        <!-- page heading and back button -->
        <div class="content-header">
            <div>
                <h1>Add New Vehicle</h1>
                <p class="text-secondary">Expand your rental fleet</p>
            </div>
            <a href="admin-vehicles.php" class="btn btn-secondary">← Back</a>
        </div>

        <!-- show error message if something went wrong -->
        <?php if ($error): ?>
        <div class="alert-error">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <!-- vehicle add form (multipart needed for image upload) -->
        <form method="POST" enctype="multipart/form-data">

            <div class="main-grid">

                <div class="card">
                    <h3 class="card-title">Vehicle Specifications</h3>

                    <!-- vehicle name -->
                    <div class="form-group">
                        <label class="form-label">Vehicle Name *</label>
                        <input type="text" name="name" class="form-input" required>
                    </div>

                    <!-- type and location side by side -->
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

                    <!-- price, fuel, and transmission in a 3-column row -->
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

                    <!-- number of seats -->
                    <div class="form-group">
                        <label class="form-label">Seats</label>
                        <input type="number" name="seats" class="form-input">
                    </div>

                    <!-- comma-separated features list -->
                    <div class="form-group">
                        <label class="form-label">Features</label>
                        <input type="text" name="features" class="form-input">
                    </div>

                    <!-- longer description of the vehicle -->
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-input"></textarea>
                    </div>

                    <!-- vehicle photo upload -->
                    <div class="form-group">
                        <label class="form-label">Vehicle Image *</label>
                        <input type="file" name="image" class="form-input">
                    </div>

                    <!-- availability toggle: checked = visible to customers right away -->
                    <div class="availability-box">
                        <label class="availability-label">
                            <input type="checkbox" name="availability" checked>
                            <span>
                                <strong>Available for Booking</strong><br>
                                <small>Visible to customers immediately</small>
                            </span>
                        </label>
                    </div>

                    <!-- submit button -->
                    <button type="submit" class="btn-primary">
                        ✓ Add Vehicle to Fleet
                    </button>

                </div>

            </div>

        </form>

    </main>

</div>


<style>

/* light gray page background */
.body-bg {
    background: var(--brand-light-gray);
}

/* sidebar: fixed to the left, full height, dark navy */
.sidebar {
    width: 240px;
    background: #1e293b;
    color: white;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
}

/* logo row at the top of the sidebar */
.sidebar-logo {
    padding: 20px 24px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    display: flex;
    align-items: center;
    gap: 12px;
}

.sidebar-logo img {
    height: 36px;
}

.sidebar-logo span {
    font-size: 13px;
    color: #94a3b8;
    font-weight: 600;
}

/* nav area fills the remaining sidebar height */
.sidebar-menu {
    padding: 16px 12px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

/* individual nav link */
.sidebar-menu a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 14px;
    border-radius: 8px;
    color: #94a3b8;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 4px;
    transition: all 0.2s;
}

/* icon fixed width so labels stay aligned */
.sidebar-menu a i {
    width: 18px;
    text-align: center;
    font-size: 15px;
}

/* hover state */
.sidebar-menu a:hover {
    background: rgba(255,255,255,0.07);
    color: white;
}

/* orange highlight on the current page link */
.sidebar-menu a.active {
    background: #f97316;
    color: white;
}

/* logout wrapper pushes itself to the bottom */
.sidebar-menu .logout-link {
    margin-top: auto;
}

/* logout link in soft red */
.sidebar-menu .logout-link a {
    color: #fca5a5;
}

.sidebar-menu .logout-link a:hover {
    background: rgba(239,68,68,0.15);
    color: #fca5a5;
}

/* offset main content so it doesn't hide behind the fixed sidebar */
.main-content {
    padding: 2rem;
    flex: 1;
    margin-left: 240px;
}

/* header row: title on the left, back button on the right */
.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.text-secondary {
    color: var(--text-secondary);
}

/* two-column layout: form card is wider on the left */
.main-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
}

/* white card container */
.card {
    background: white;
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
}

.card-title {
    margin-bottom: 1.5rem;
}

/* spacing between each field */
.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--brand-dark-blue);
}

/* shared style for inputs, selects, and textareas */
.form-input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 1rem;
}

/* two equal columns */
.grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

/* three equal columns */
.grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

/* light blue box around the availability checkbox */
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

/* red error banner */
.alert-error {
    background: #fee2e2;
    color: #b91c1c;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}

/* full-width orange submit button */
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
