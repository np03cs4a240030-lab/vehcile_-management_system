<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../admin/admin-login.php');
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $booking_id = (int)$_POST['booking_id'];
    $allowed    = ['pending', 'confirmed', 'completed', 'cancelled'];
    $new_status = in_array($_POST['new_status'], $allowed) ? $_POST['new_status'] : 'pending';

    // If confirming a booking → mark vehicle unavailable
    if ($new_status === 'confirmed') {
        $vid_res = $conn->query("SELECT vehicle_id FROM bookings WHERE id = $booking_id");
        if ($vid_res && $row = $vid_res->fetch_assoc()) {
            $conn->query("UPDATE vehicles SET availability = 0 WHERE id = " . (int)$row['vehicle_id']);
        }
    }
    // If cancelling → mark vehicle available again
    if ($new_status === 'cancelled') {
        $vid_res = $conn->query("SELECT vehicle_id FROM bookings WHERE id = $booking_id");
        if ($vid_res && $row = $vid_res->fetch_assoc()) {
            $conn->query("UPDATE vehicles SET availability = 1 WHERE id = " . (int)$row['vehicle_id']);
        }
    }

    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $booking_id);
    $stmt->execute();
    redirect('admin-bookings.php');
}

// Filter
$allowed_filters = ['all', 'pending', 'confirmed', 'completed', 'cancelled'];
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

$statusColors = [
    'pending'   => ['bg' => '#fef9c3', 'color' => '#ca8a04'],
    'confirmed' => ['bg' => '#dcfce7', 'color' => '#16a34a'],
    'completed' => ['bg' => '#dbeafe', 'color' => '#2563eb'],
    'cancelled' => ['bg' => '#fee2e2', 'color' => '#dc2626'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - Admin | Bhatbhatey Rental</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #f1f5f9; display: flex; min-height: 100vh; }

        .sidebar {
            width: 240px; background: #1e293b; color: white;
            display: flex; flex-direction: column; min-height: 100vh;
            position: fixed; top: 0; left: 0;
        }
        .sidebar-logo {
            padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex; align-items: center; gap: 12px;
        }
        .sidebar-logo img { height: 36px; }
        .sidebar-logo span { font-size: 13px; color: #94a3b8; font-weight: 600; }
        .sidebar-menu { padding: 16px 12px; flex: 1; display: flex; flex-direction: column; }
        .sidebar-menu a {
            display: flex; align-items: center; gap: 12px;
            padding: 11px 14px; border-radius: 8px;
            color: #94a3b8; text-decoration: none;
            font-size: 14px; font-weight: 500; margin-bottom: 4px; transition: all 0.2s;
        }
        .sidebar-menu a i { width: 18px; text-align: center; font-size: 15px; }
        .sidebar-menu a:hover { background: rgba(255,255,255,0.07); color: white; }
        .sidebar-menu a.active { background: #f97316; color: white; }
        .logout-link { margin-top: auto; }
        .logout-link a { color: #fca5a5 !important; }
        .logout-link a:hover { background: rgba(239,68,68,0.15) !important; }

        .main-content { margin-left: 240px; flex: 1; padding: 28px; }
        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 22px; font-weight: 700; color: #1e293b; }
        .page-header p { color: #64748b; font-size: 14px; margin-top: 3px; }

        /* FILTER TABS */
        .filter-tabs { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-tab {
            padding: 8px 16px; border-radius: 8px;
            text-decoration: none; font-size: 13px; font-weight: 600;
            border: 1px solid #e2e8f0; background: white; color: #475569;
            display: flex; align-items: center; gap: 6px; transition: all 0.2s;
        }
        .filter-tab:hover { border-color: #f97316; color: #f97316; }
        .filter-tab.active { background: #f97316; color: white; border-color: #f97316; }

        /* TABLE */
        .table-card {
            background: white; border-radius: 14px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.07); overflow: hidden;
        }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: #f8fafc; padding: 12px 16px;
            text-align: left; font-size: 12px;
            color: #64748b; text-transform: uppercase;
            letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0;
        }
        tbody tr { border-bottom: 1px solid #f1f5f9; transition: background 0.15s; }
        tbody tr:hover { background: #fafbfc; }
        tbody tr:last-child { border-bottom: none; }
        td { padding: 13px 16px; font-size: 14px; color: #1e293b; vertical-align: middle; }
        .td-secondary { font-size: 12px; color: #64748b; margin-top: 2px; }

        .status-badge {
            display: inline-block; padding: 4px 10px;
            border-radius: 20px; font-size: 12px; font-weight: 600;
        }
        .payment-badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;
        }

        select.status-select {
            padding: 6px 10px; border-radius: 8px;
            border: 1px solid #e2e8f0; font-size: 13px;
            background: #f8fafc; cursor: pointer; outline: none;
        }
        select.status-select:focus { border-color: #f97316; }

        .btn-view {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 6px 12px; border-radius: 8px;
            background: #eff6ff; color: #3b82f6;
            border: 1px solid #bfdbfe; font-size: 13px;
            cursor: pointer; text-decoration: none; transition: all 0.2s;
        }
        .btn-view:hover { background: #dbeafe; }

        .empty-state { text-align: center; padding: 60px; color: #94a3b8; }
        .empty-state i { font-size: 48px; margin-bottom: 12px; display: block; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="../assets/images/logo.png" alt="Logo">
        <span>Admin Panel</span>
    </div>
    <nav class="sidebar-menu">
        <a href="admin-dashboard.php"><i class="fas fa-gauge-high"></i> Dashboard</a>
        <a href="admin-vehicles.php"><i class="fas fa-car"></i> Vehicles</a>
        <a href="admin-bookings.php" class="active"><i class="fas fa-calendar-days"></i> Bookings</a>
        <a href="admin-users.php"><i class="fas fa-users"></i> Users</a>
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

    <!-- FILTER TABS -->
    <div class="filter-tabs">
        <a href="?status=all" class="filter-tab <?php echo $status_filter==='all'?'active':''; ?>">
            <i class="fas fa-list"></i> All
        </a>
        <a href="?status=pending" class="filter-tab <?php echo $status_filter==='pending'?'active':''; ?>">
            <i class="fas fa-clock"></i> Pending
        </a>
        <a href="?status=confirmed" class="filter-tab <?php echo $status_filter==='confirmed'?'active':''; ?>">
            <i class="fas fa-circle-check"></i> Confirmed
        </a>
        <a href="?status=completed" class="filter-tab <?php echo $status_filter==='completed'?'active':''; ?>">
            <i class="fas fa-flag-checkered"></i> Completed
        </a>
        <a href="?status=cancelled" class="filter-tab <?php echo $status_filter==='cancelled'?'active':''; ?>">
            <i class="fas fa-ban"></i> Cancelled
        </a>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Vehicle</th>
                    <th>Dates</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($bookings->num_rows > 0): ?>
                    <?php while ($b = $bookings->fetch_assoc()): ?>
                        <?php $sc = $statusColors[$b['status']] ?? ['bg'=>'#f1f5f9','color'=>'#64748b']; ?>
                        <tr>
                            <td style="color:#94a3b8; font-weight:600;">#<?php echo $b['id']; ?></td>
                            <td>
                                <div style="font-weight:600;"><?php echo htmlspecialchars($b['user_name']); ?></div>
                                <div class="td-secondary"><?php echo htmlspecialchars($b['user_email']); ?></div>
                            </td>
                            <td>
                                <div style="font-weight:600;"><?php echo htmlspecialchars($b['vehicle_name']); ?></div>
                                <div class="td-secondary">
                                    <?php
                                        $icons = ['Car'=>'fa-car','Bike'=>'fa-motorcycle','Scooter'=>'fa-motorcycle'];
                                        $ic = $icons[$b['vehicle_type']] ?? 'fa-car';
                                    ?>
                                    <i class="fas <?php echo $ic; ?>"></i> <?php echo $b['vehicle_type']; ?>
                                </div>
                            </td>
                            <td>
                                <div><?php echo date('M d', strtotime($b['start_date'])); ?> &rarr; <?php echo date('M d, Y', strtotime($b['end_date'])); ?></div>
                                <div class="td-secondary"><?php echo $b['total_days']; ?> days</div>
                            </td>
                            <td style="font-weight:700; color:#f97316;">NPR <?php echo number_format($b['total_price'], 2); ?></td>
                            <td>
                                <?php if ($b['payment_method'] === 'online'): ?>
                                    <span class="payment-badge" style="background:#eff6ff; color:#3b82f6;">
                                        <i class="fas fa-credit-card"></i> Online
                                    </span>
                                <?php else: ?>
                                    <span class="payment-badge" style="background:#f0fdf4; color:#16a34a;">
                                        <i class="fas fa-money-bill"></i> Cash
                                    </span>
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
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="fas fa-calendar-xmark"></i>
                                No bookings found
                            </div>
                        </td>
                    </tr>
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
