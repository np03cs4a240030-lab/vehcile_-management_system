<?php
session_start();
include("../config/db.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {

            if (in_array($row['role'], ['admin', 'super_admin'])) {

                session_regenerate_id(true); 

                $_SESSION['admin'] = $row['email'];
                $_SESSION['role'] = $row['role'];

                header("Location: admin_dashboard.php");
                exit();

            } else {
                $error = "Access denied! Not an admin.";
            }

        } else {
            $error = "Invalid email or password!";
        }

    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Login</title>

<style>
* { box-sizing: border-box; }

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: linear-gradient(to right, #1e293b, #334155);
}

.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 60px;
    background: rgba(0,0,0,0.3);
    color: white;
}

.logo {
    height: 60px;  
    width: auto;
}

.container {
    height: calc(100vh - 70px);
    display: flex;
    justify-content: center;
    align-items: center;
}

.login-box {
    background: #ffffff;
    padding: 40px 35px;
    width: 380px;
    border-radius: 14px;
    text-align: center;
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
}

.login-box h2 { margin-bottom: 20px; color: #1e293b; }

input {
    width: 100%;
    padding: 12px 14px;
    margin: 12px 0;
    border-radius: 8px;
    border: 1px solid #ccc;
}

button {
    width: 100%;
    padding: 12px;
    background: #f97316;
    color: white;
    border: none;
    border-radius: 8px;
}

.error { color: red; }
</style>
</head>

<body>

<div class="navbar">
    <img src="../nobglogo.png" class="logo">
</div>

<div class="container">
    <div class="login-box">
        <h2>Admin Login</h2>

        <form method="POST">
            <input type="email" name="email" placeholder="Enter email" required>
            <input type="password" name="password" placeholder="Enter password" required>
            <button type="submit">Login</button>
        </form>

        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    </div>
</div>

<?php include("../footer.php"); ?>

</body>
</html>