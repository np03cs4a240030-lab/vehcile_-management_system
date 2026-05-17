<?php
session_start();
require_once '../includes/connection.php';

// Helper for redirection
if (!function_exists('redirect')) {
    function redirect($url)
    {
        header("Location: " . $url);
        exit();
    }
}

// Security Check
if (!isLoggedIn() || !hasRole('super_admin')) {
    redirect('superadmin-login.php');
}

$success = '';
$error = '';

// 1. GET USER ID FROM URL
$user_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($user_id <= 0) {
    redirect('superadmin-users.php');
}

// 2. FETCH EXISTING USER DATA
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    redirect('superadmin-users.php');
}

// 3. HANDLE UPDATE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? ''; // New password (optional)
    $role = $_POST['role'] ?? '';
    $status = $_POST['status'] ?? '';

    if (empty($name) || empty($email) || empty($phone)) {
        $error = 'Name, Email, and Phone are required';
    } else {
        // Check if email is already taken by ANOTHER user
        $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email is already in use by another account';
        } else {
            // Update Logic
            if (!empty($password)) {
                // If password is provided, hash it and update everything
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET name=?, email=?, phone_number=?, password=?, role=?, status=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssi", $name, $email, $phone, $hashed_password, $role, $status, $user_id);
            } else {
                // If password is empty, update everything EXCEPT password
                $sql = "UPDATE users SET name=?, email=?, phone_number=?, role=?, status=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssi", $name, $email, $phone, $role, $status, $user_id);
            }

            if ($stmt->execute()) {
                $success = 'User updated successfully!';
                // Refresh local user data to show updated values in form
                $user['name'] = $name;
                $user['email'] = $email;
                $user['phone_number'] = $phone;
                $user['role'] = $role;
                $user['status'] = $status;
            } else {
                $error = 'Failed to update user';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Super Admin</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    
</head>

<body style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
    <div class="dashboard-layout">
        <aside class="sidebar super-admin-sidebar">
            <div class="sidebar-header"
                style="padding: 2rem; text-align: center; border-bottom: 1px solid rgba(148,163,184,0.1);">
                <img src="../assets/images/logo.png" alt="Logo" style="height: 3rem;">
                <h3 style="color: white; margin-top: 1rem;">Super Admin</h3>
            </div>
            <nav class="sidebar-nav" style="padding: 1rem; display: flex; flex-direction: column;">
                <a href="superadmin-dashboard.php" class="nav-item">📊 Dashboard</a>
                <a href="superadmin-users.php" class="nav-item active">👥 User Management</a>
                <a href="superadmin-vehicles.php" class="nav-item">🚗 Vehicles</a>
                <a href="../logout.php" class="nav-item" style="margin-top: auto; color: #fca5a5;">🚪 Logout</a>
            </nav>
        </aside>

        <main class="main-content" style="flex: 1; padding: 2rem;">
            <div class="content-header"
                style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="color: white;">Edit User</h1>
                    <p style="color: #94a3b8;">Updating details for: <?php echo htmlspecialchars($user['name']); ?></p>
                </div>
                <a href="superadmin-users.php" class="btn btn-secondary"
                    style="color: white; text-decoration: none; padding: 0.5rem 1rem; border: 1px solid #94a3b8; border-radius: 0.5rem;">←
                    Back</a>
            </div>

            <div class="data-card"
                style="max-width: 600px; background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; padding: 2rem;">
                <?php if ($success): ?>
                    <div
                        style="background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #10b981;">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div
                        style="background: rgba(239, 68, 68, 0.2); color: #ef4444; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #ef4444;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label style="color: white; display: block; margin-bottom: 0.5rem;">Full Name</label>
                        <input type="text" name="name" class="form-input" required
                            value="<?php echo htmlspecialchars($user['name']); ?>"
                            style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;">
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label style="color: white; display: block; margin-bottom: 0.5rem;">Email</label>
                        <input type="email" name="email" class="form-input" required
                            value="<?php echo htmlspecialchars($user['email']); ?>"
                            style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;">
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label style="color: white; display: block; margin-bottom: 0.5rem;">Phone Number</label>
                        <input type="tel" name="phone" class="form-input" required
                            value="<?php echo htmlspecialchars($user['phone_number']); ?>"
                            style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;">
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label style="color: white; display: block; margin-bottom: 0.5rem;">New Password <span
                                style="font-size: 0.7rem; color: #94a3b8;">(Leave blank to keep current)</span></label>
                        <input type="password" name="password" class="form-input"
                            style="width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
                        <div class="form-group">
                            <label style="color: white; display: block; margin-bottom: 0.5rem;">Role</label>
                            <select name="role"
                                style="width: 100%; padding: 0.75rem; background: #1e293b; border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;">
                                <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User
                                </option>
                                <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin
                                </option>

                            </select>
                        </div>
                        <div class="form-group">
                            <label style="color: white; display: block; margin-bottom: 0.5rem;">Status</label>
                            <select name="status"
                                style="width: 100%; padding: 0.75rem; background: #1e293b; border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;">
                                <option value="active" <?php echo $user['status'] == 'active' ? 'selected' : ''; ?>>Active
                                </option>
                                <option value="disabled" <?php echo $user['status'] == 'disabled' ? 'selected' : ''; ?>>
                                    Disabled</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary"
                        style="width: 100%; padding: 1rem; background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%); color: white; border: none; border-radius: 0.5rem; font-weight: bold; cursor: pointer;">
                        💾 Save Changes
                    </button>
                </form>
            </div>
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