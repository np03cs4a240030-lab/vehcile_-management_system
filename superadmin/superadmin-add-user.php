<?php
require_once '../config.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../superadmin/superadmin-login.php');
}

$success = '';
$error   = '';

// If coming from "Add Admin" button, preset role
$preset_role = isset($_GET['role']) && $_GET['role'] === 'admin' ? 'admin' : 'user';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = sanitize($_POST['name'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $phone    = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = in_array($_POST['role'], ['user','admin']) ? $_POST['role'] : 'user';
    $status   = in_array($_POST['status'], ['active','disabled']) ? $_POST['status'] : 'active';

    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error = 'All fields are required.';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = 'This email address is already registered.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt   = $conn->prepare("INSERT INTO users (name, email, phone_number, password, role, status) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("ssssss", $name, $email, $phone, $hashed, $role, $status);

            if ($stmt->execute()) {
                $success = 'User created successfully!';
            } else {
                $error = 'Failed to create user. Please try again.';
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
    <title>Add User - Super Admin | Bhatbhatey Rental</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); min-height: 100vh; display: flex; }

        .sidebar { width: 240px; background: rgba(255,255,255,0.03); border-right: 1px solid rgba(255,255,255,0.07); display: flex; flex-direction: column; min-height: 100vh; position: fixed; top: 0; left: 0; }
        .sidebar-logo { padding: 22px 24px; border-bottom: 1px solid rgba(255,255,255,0.07); display: flex; align-items: center; gap: 12px; }
        .sidebar-logo img { height: 36px; }
        .sidebar-logo-text .title { font-size: 14px; font-weight: 700; color: white; }
        .sidebar-logo-text .sub { font-size: 11px; color: #a855f7; background: rgba(168,85,247,0.15); border: 1px solid rgba(168,85,247,0.3); border-radius: 20px; padding: 1px 8px; display: inline-block; margin-top: 2px; }
        .sidebar-menu { padding: 16px 12px; flex: 1; display: flex; flex-direction: column; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; padding: 11px 14px; border-radius: 10px; color: #94a3b8; text-decoration: none; font-size: 14px; font-weight: 500; margin-bottom: 4px; transition: all 0.2s; }
        .sidebar-menu a i { width: 18px; text-align: center; }
        .sidebar-menu a:hover { background: rgba(255,255,255,0.07); color: white; }
        .sidebar-menu a.active { background: linear-gradient(135deg, #9333ea, #7c3aed); color: white; }
        .logout-link { margin-top: auto; }
        .logout-link a { color: #fca5a5 !important; }
        .logout-link a:hover { background: rgba(239,68,68,0.1) !important; }

        .main-content { margin-left: 240px; flex: 1; padding: 28px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-header h1 { font-size: 22px; font-weight: 700; color: white; }
        .page-header p { color: #94a3b8; font-size: 14px; margin-top: 3px; }
        .btn-back { display: inline-flex; align-items: center; gap: 7px; padding: 9px 16px; border-radius: 8px; background: rgba(255,255,255,0.07); color: #94a3b8; text-decoration: none; font-size: 14px; transition: all 0.2s; }
        .btn-back:hover { background: rgba(255,255,255,0.1); color: white; }

        .form-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 28px; max-width: 560px; }

        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 22px; font-size: 14px; display: flex; align-items: center; gap: 9px; }
        .alert-success { background: rgba(16,185,129,0.12); color: #34d399; border: 1px solid rgba(16,185,129,0.2); }
        .alert-error   { background: rgba(239,68,68,0.12);  color: #f87171; border: 1px solid rgba(239,68,68,0.2); }

        .form-group { margin-bottom: 18px; }
        .form-label { display: block; font-size: 11px; font-weight: 700; color: #94a3b8; margin-bottom: 7px; text-transform: uppercase; letter-spacing: 1px; }
        .input-wrap { position: relative; }
        .input-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #475569; font-size: 14px; }
        .form-input {
            width: 100%; background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            padding: 12px 12px 12px 40px;
            border-radius: 10px; color: white;
            font-size: 14px; outline: none; transition: 0.2s;
        }
        .form-input:focus { border-color: #a855f7; background: rgba(255,255,255,0.08); box-shadow: 0 0 15px rgba(168,85,247,0.15); }
        .form-input::placeholder { color: #475569; }
        select.form-input { padding-left: 40px; cursor: pointer; }
        select.form-input option { background: #1e293b; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        .btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg,#9333ea,#7c3aed); color: white; border: none; border-radius: 10px; font-weight: 700; font-size: 15px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 8px; }
        .btn-submit:hover { opacity: 0.9; transform: translateY(-1px); box-shadow: 0 8px 25px rgba(139,92,246,0.3); }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="../assets/images/logo.png" alt="Logo">
        <div class="sidebar-logo-text">
            <div class="title">Bhatbhatey</div>
            <div class="sub">Super Admin</div>
        </div>
    </div>
    <nav class="sidebar-menu">
        <a href="superadmin-dashboard.php"><i class="fas fa-gauge-high"></i> Dashboard</a>
        <a href="superadmin-users.php" class="active"><i class="fas fa-users"></i> Users</a>
        <a href="superadmin-admins.php"><i class="fas fa-user-shield"></i> Admins</a>
        <a href="superadmin-vehicles.php"><i class="fas fa-car"></i> Vehicles</a>
        <a href="superadmin-bookings.php"><i class="fas fa-calendar-days"></i> Bookings</a>
        <div class="logout-link">
            <a href="../logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </div>
    </nav>
</aside>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1>Add New User</h1>
            <p>Create a new user or admin account</p>
        </div>
        <a href="superadmin-users.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="form-card">

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-circle-check"></i><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label class="form-label">Full Name</label>
                <div class="input-wrap">
                    <i class="fas fa-user"></i>
                    <input type="text" name="name" class="form-input" placeholder="John Doe" required
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" class="form-input" placeholder="user@example.com" required
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <div class="input-wrap">
                    <i class="fas fa-phone"></i>
                    <input type="tel" name="phone" class="form-input" placeholder="98XXXXXXXX" required
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" class="form-input" placeholder="Minimum 8 characters" required>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <div class="input-wrap">
                        <i class="fas fa-id-badge"></i>
                        <select name="role" class="form-input">
                            <option value="user"  <?php echo ($preset_role==='user'  || ($_POST['role']??'')===  'user')  ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo ($preset_role==='admin' || ($_POST['role']??'')=== 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <div class="input-wrap">
                        <i class="fas fa-toggle-on"></i>
                        <select name="status" class="form-input">
                            <option value="active">Active</option>
                            <option value="disabled">Disabled</option>
                        </select>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-user-plus"></i> Create User
            </button>

        </form>
    </div>
</main>
</body>
</html>
