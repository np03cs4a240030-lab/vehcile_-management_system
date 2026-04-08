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
    <title>Add Vehicle</title>
</head>

<body>

<h1>Add New Vehicle</h1>
<a href="admin-vehicles.php">Back</a>

<?php if ($error): ?>
    <div><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

    <h3>Vehicle Specifications</h3>

    <label>Vehicle Name *</label><br>
    <input type="text" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"><br><br>

    <label>Vehicle Type *</label><br>
    <select name="type" required>
        <option value="Car">Car</option>
        <option value="Bike">Bike</option>
        <option value="Scooter">Scooter</option>
    </select><br><br>

    <label>Location *</label><br>
    <input type="text" name="location" required value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>"><br><br>

    <label>Price/Day (NPR) *</label><br>
    <input type="number" name="price_per_day" step="0.01" required value="<?php echo isset($_POST['price_per_day']) ? htmlspecialchars($_POST['price_per_day']) : ''; ?>"><br><br>

    <label>Fuel</label><br>
    <select name="fuel_type">
        <option value="Petrol">Petrol</option>
        <option value="Diesel">Diesel</option>
        <option value="Electric">Electric</option>
    </select><br><br>

    <label>Transmission</label><br>
    <select name="transmission">
        <option value="Manual">Manual</option>
        <option value="Automatic">Automatic</option>
    </select><br><br>

    <label>Seats</label><br>
    <input type="number" name="seats" min="1" value="<?php echo isset($_POST['seats']) ? htmlspecialchars($_POST['seats']) : '2'; ?>"><br><br>

    <label>Features</label><br>
    <input type="text" name="features" value="<?php echo isset($_POST['features']) ? htmlspecialchars($_POST['features']) : ''; ?>"><br><br>

    <label>Description</label><br>
    <textarea name="description"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea><br><br>

    <label>Vehicle Image *</label><br>
    <input type="file" name="image" required><br><br>

    <label>
        <input type="checkbox" name="availability" value="1" checked>
        Available for Booking
    </label><br><br>

    <button type="submit">Add Vehicle</button>

</form>

</body>
</html>