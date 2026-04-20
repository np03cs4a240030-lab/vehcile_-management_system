<?php
require_once '../config.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../superadmin/superadmin-login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $booking_id = (int)$_POST['booking_id'];
    $allowed    = ['pending', 'confirmed', 'completed', 'cancelled'];
    $new_status = in_array($_POST['new_status'], $allowed) ? $_POST['new_status'] : 'pending';

    // Auto-update vehicle availability
    if ($new_status === 'confirmed') {
        $r = $conn->query("SELECT vehicle_id FROM bookings WHERE id = $booking_id");
        if ($r && $row = $r->fetch_assoc()) {
            $conn->query("UPDATE vehicles SET availability = 0 WHERE id = " . (int)$row['vehicle_id']);
        }
    }
    if ($new_status === 'cancelled' || $new_status === 'completed') {
        $r = $conn->query("SELECT vehicle_id FROM bookings WHERE id = $booking_id");
        if ($r && $row = $r->fetch_assoc()) {
            $conn->query("UPDATE vehicles SET availability = 1 WHERE id = " . (int)$row['vehicle_id']);
        }
    }

    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $booking_id);
    $stmt->execute();
    redirect('superadmin-bookings.php');
}

$allowed_filters = ['all','pending','confirmed','completed','cancelled'];
$status_filter   = isset($_GET['status']) && in_array($_GET['status'], $allowed_filters) ? $_GET['status'] : 'all';

$sql = "SELECT b.*, u.name as user_name, u.email as user_email,
               v.name as vehicle_name, v.type as vehicle_type
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN vehicles v ON b.vehicle_id = v.id";

if ($status_filter !== 'all') {
    $stmt2 = $conn->prepare($sql . " WHERE b.status = ? ORDER BY b.created_at DESC");
    $stmt2->bind_param("s", $status_filter);
    $stmt2->execute();
    $bookings = $stmt2->get_result();
} else {
    $bookings = $conn->query($sql . " ORDER BY b.created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - Super Admin | Bhatbhatey Rental</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); min-height: 100vh; display: flex; }

        .sidebar {
            width: 240px; background: rgba(255,255,255,0.03);
            border-right: 1px solid rgba(255,255,255,0.07);
            display: flex; flex-direction: column; min-height: 100vh;
            position: fixed; top: 0; left: 0;
        }
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
        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 22px; font-weight: 700; color: white; }
        .page-header p { color: #94a3b8; font-size: 14px; margin-top: 3px; }

        .filter-tabs { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-tab { padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.04); color: #94a3b8; display: flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .filter-tab:hover { background: rgba(255,255,255,0.08); color: white; }
        .filter-tab.active { background: linear-gradient(135deg, #9333ea, #7c3aed); color: white; border-color: transparent; }

        .table-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: rgba(255,255,255,0.03); padding: 12px 16px; text-align: left; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid rgba(255,255,255,0.06); }
        tbody tr { border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.15s; }
        tbody tr:hover { background: rgba(255,255,255,0.03); }
        tbody tr:last-child { border-bottom: none; }
        td { padding: 13px 16px; font-size: 14px; color: #e2e8f0; vertical-align: middle; }
        .td-secondary { font-size: 12px; color: #64748b; margin-top: 2px; }

        .badge { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-success { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.2); }
        .badge-danger  { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.2); }
        .badge-primary { background: rgba(59,130,246,0.15); color: #60a5fa; border: 1px solid rgba(59,130,246,0.2); }
        .badge-warning { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.2); }

        select.status-select { padding: 6px 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.07); color: white; font-size: 13px; cursor: pointer; outline: none; }
        select.status-select:focus { border-color: #a855f7; }
        select.status-select option { background: #1e293b; color: white; }

        .btn-view { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 8px; background: rgba(59,130,246,0.1); color: #60a5fa; border: 1px solid rgba(59,130,246,0.2); font-size: 13px; cursor: pointer; transition: all 0.2s; }
        .btn-view:hover { background: rgba(59,130,246,0.2); }
        .empty-state { text-align: center; padding: 60px; color: #94a3b8; }
        .empty-state i { font-size: 48px; margin-bottom: 12px; display: block; }
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
        <a href="superadmin-users.php"><i class="fas fa-users"></i> Users</a>
        <a href="superadmin-admins.php"><i class="fas fa-user-shield"></i> Admins</a>
        <a href="superadmin-vehicles.php"><i class="fas fa-car"></i> Vehicles</a>
        <a href="superadmin-bookings.php" class="active"><i class="fas fa-calendar-days"></i> Bookings</a>
        <div class="logout-link">
            <a href="../logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </div>
    </nav>
</aside>

<main class="main-content">
    <div class="page-header">
        <h1>Booking Management</h1>
        <p>View and manage all vehicle bookings</p>
    </div>

    <div class="filter-tabs">
        <a href="?status=all" class="filter-tab <?php echo $status_filter==='all'?'active':''; ?>"><i class="fas fa-list"></i> All</a>
        <a href="?status=pending" class="filter-tab <?php echo $status_filter==='pending'?'active':''; ?>"><i class="fas fa-clock"></i> Pending</a>
        <a href="?status=confirmed" class="filter-tab <?php echo $status_filter==='confirmed'?'active':''; ?>"><i class="fas fa-circle-check"></i> Confirmed</a>
        <a href="?status=completed" class="filter-tab <?php echo $status_filter==='completed'?'active':''; ?>"><i class="fas fa-flag-checkered"></i> Completed</a>
        <a href="?status=cancelled" class="filter-tab <?php echo $status_filter==='cancelled'?'active':''; ?>"><i class="fas fa-ban"></i> Cancelled</a>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Vehicle</th>
                    <th>Dates</th>
                    <th>Days</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($bookings->num_rows > 0): ?>
                    <?php while ($b = $bookings->fetch_assoc()): ?>
                        <tr>
                            <td style="color:#64748b; font-weight:600;">#<?php echo $b['id']; ?></td>
                            <td>
                                <div style="font-weight:600;"><?php echo htmlspecialchars($b['user_name']); ?></div>
                                <div class="td-secondary"><?php echo htmlspecialchars($b['user_email']); ?></div>
                            </td>
                            <td>
                                <div style="font-weight:600;"><?php echo htmlspecialchars($b['vehicle_name']); ?></div>
                                <div class="td-secondary">
                                    <?php $ic = ['Car'=>'fa-car','Bike'=>'fa-motorcycle','Scooter'=>'fa-motorcycle']; ?>
                                    <i class="fas <?php echo $ic[$b['vehicle_type']] ?? 'fa-car'; ?>"></i>
                                    <?php echo $b['vehicle_type']; ?>
                                </div>
                            </td>
                            <td class="td-secondary">
                                <?php echo date('M d', strtotime($b['start_date'])); ?> &rarr; <?php echo date('M d, Y', strtotime($b['end_date'])); ?>
                            </td>
                            <td><?php echo $b['total_days']; ?> days</td>
                            <td style="color:#fbbf24; font-weight:700;">NPR <?php echo number_format($b['total_price'], 2); ?></td>
                            <td>
                                <?php if ($b['payment_method']==='online'): ?>
                                    <span class="badge badge-primary"><i class="fas fa-credit-card"></i> Online</span>
                                <?php else: ?>
                                    <span class="badge badge-success"><i class="fas fa-money-bill"></i> Cash</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <select class="status-select" onchange="updateStatus(<?php echo $b['id']; ?>, this.value)">
                                    <option value="pending"   <?php echo $b['status']==='pending'  ?'selected':''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $b['status']==='confirmed'?'selected':''; ?>>Confirmed</option>
                                    <option value="completed" <?php echo $b['status']==='completed'?'selected':''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $b['status']==='cancelled'?'selected':''; ?>>Cancelled</option>
                                </select>
                            </td>
                            <td>
                                <button class="btn-view"
                                    onclick="alert('Booking #<?php echo $b['id']; ?>\nCustomer: <?php echo addslashes($b['user_name']); ?>\nVehicle: <?php echo addslashes($b['vehicle_name']); ?>\nDays: <?php echo $b['total_days']; ?>\nTotal: NPR <?php echo number_format($b['total_price'],2); ?>\nStatus: <?php echo $b['status']; ?>')">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9"><div class="empty-state"><i class="fas fa-calendar-xmark"></i>No bookings found</div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
function updateStatus(bookingId, newStatus) {
    if (!confirm('Update booking status to "' + newStatus + '"?')) { return; }
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="booking_id" value="${bookingId}">
        <input type="hidden" name="new_status" value="${newStatus}">
    `;
    document.body.appendChild(form);
    form.submit();
}
</script>
</body>
</html>
