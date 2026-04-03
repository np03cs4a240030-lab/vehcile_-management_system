<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['admin']) || $_SESSION['role'] != 'super_admin') {
    die("Access Denied");
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
    $stmt->bind_param("sss", $name, $email, $password);

    if ($stmt->execute()) {
        $message = "Admin added successfully!";
    } else {
        $message = "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Admins</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(to right, #f1f5f9, #e2e8f0);
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* NAVBAR */
.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 50px;
    background: #1e293b;
    color: white;
}

.nav-logo {
    height: 50px;
    width: auto;
}

.navbar a {
    text-decoration: none;
    background: #f97316;
    color: white;
    padding: 8px 14px;
    border-radius: 6px;
    transition: 0.3s;
}

.navbar a:hover {
    background: #ea580c;
}

/* MAIN CONTAINER */
.container {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* CARD */
.card {
    background: white;
    padding: 35px 30px;
    width: 400px;
    border-radius: 14px;
    text-align: center;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

/* TITLE */
.card h2 {
    margin-bottom: 20px;
    color: #1e293b;
}

/* INPUT */
input {
    width: 100%;
    padding: 12px 14px;
    margin: 10px 0;
    border-radius: 8px;
    border: 1px solid #ccc;
    transition: 0.3s;
}

input:focus {
    border-color: #f97316;
    outline: none;
}

/* BUTTON */
button {
    width: 100%;
    padding: 12px;
    margin-top: 10px;
    border-radius: 8px;
    border: none;
    background: #f97316;
    color: white;
    font-size: 15px;
    cursor: pointer;
    transition: 0.3s;
}

button:hover {
    background: #ea580c;
}

/* MESSAGE */
.message {
    margin-top: 12px;
    color: green;
    font-size: 14px;
}

/* FOOTER */
.footer {
    width: 100%;
    text-align: center;
    padding: 15px;
    background: #1e293b;
    color: white;
}
</style>
</head>

<body>

<!-- NAVBAR -->
<div class="navbar">
    <img src="../nobglogo.png" class="nav-logo">
    <div>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<!-- CENTER FORM -->
<div class="container">
    <div class="card">

        <h2>Add New Admin</h2>

        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Add Admin</button>
        </form>

        <?php if ($message) { ?>
            <p class="message"><?php echo $message; ?></p>
        <?php } ?>

    </div>
</div>

<!-- FOOTER -->
<div class="footer">
    © <?php echo date("Y"); ?> All Rights Reserved | भटभटे Rentals
</div>

</body>
</html>