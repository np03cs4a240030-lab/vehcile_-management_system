<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: adminlogin.php");
    exit();
}

$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>

<style>
/* RESET */
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: #f1f5f9;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* WRAPPER (SIDEBAR + MAIN) */
.wrapper {
    display: flex;
    flex: 1;
}

/* SIDEBAR */
.sidebar {
    width: 240px;
    background: #1e293b;
    color: white;
    padding: 20px;
}

.logo-container {
    text-align: center;
    margin-bottom: 25px;
}

.logo-container img {
    width: 180px;  
    max-width: 100%;
}

.sidebar a {
    display: block;
    color: #cbd5f5;
    text-decoration: none;
    padding: 12px;
    margin: 10px 0;
    border-radius: 8px;
    transition: 0.3s;
}

.sidebar a:hover {
    background: #f97316;
    color: white;
}

/* MAIN */
.main {
    flex: 1;
    padding: 30px;
    display: flex;
    flex-direction: column;
}

/* HEADER */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* CARDS */
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.05);
    transition: 0.3s;
}

.card:hover {
    transform: translateY(-5px);
}

.card h3 {
    margin: 0;
    color: #64748b;
}

.card p {
    font-size: 24px;
    font-weight: bold;
    margin-top: 10px;
    color: #1e293b;
}

/* SECTION */
.section {
    margin-top: 30px;
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}
</style>
</head>

<body>

<div class="wrapper">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo-container">
            <img src="../nobglogo.png" alt="Logo">
        </div>

        <a href="admin_dashboard.php">Dashboard</a>
        <a href="vehicles.php">Vehicles</a>
        <a href="bookings.php">Bookings</a>
        <a href="users.php">Users</a>

        <?php if ($role == 'super_admin') { ?>
            <a href="manage_admins.php">Manage Admins</a>
        <?php } ?>

        <hr>

        <a href="../index.php">Home</a>
        <a href="logout.php">Logout</a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">

        <div class="header">
            <h2>Welcome, <?php echo $_SESSION['admin']; ?> 👋</h2>
        </div>

        <div class="cards">
            <div class="card"><h3>Total Vehicles</h3><p>5</p></div>
            <div class="card"><h3>Total Bookings</h3><p>0</p></div>
            <div class="card"><h3>Total Revenue</h3><p>NPR 0</p></div>
            <div class="card"><h3>Total Users</h3><p>0</p></div>
        </div>

        <div class="section">
            <h3>Recent Bookings</h3>
            <p>Coming soon...</p>
        </div>

    </div>

</div>

<!-- FOOTER -->
<?php include("../footer.php"); ?>

</body>
</html>