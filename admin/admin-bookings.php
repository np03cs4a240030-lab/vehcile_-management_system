<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../admin/admin-login.php');
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $booking_id = (int)$_POST['booking_id'];
    $allowed    = ['pending', 'approved', 'ongoing', 'completed', 'cancelled'];
    $new_status = in_array($_POST['new_status'], $allowed) ? $_POST['new_status'] : 'pending';

    // Auto-update vehicle availability + cancel overlapping bookings on approval
    // Fetch booking being approved
    $r = $conn->query("SELECT vehicle_id, start_date, end_date, total_days FROM bookings WHERE id = $booking_id");
    if ($r && $row = $r->fetch_assoc()) {
        $vid        = (int)$row['vehicle_id'];
        $start_date = $row['start_date'];
        $total_days = (int)$row['total_days'];

        // Compute real end_date: use stored end_date if valid, else calculate from total_days
        $end_date = (!empty($row['end_date']) && $row['end_date'] !== '0000-00-00')
            ? $row['end_date']
            : date('Y-m-d', strtotime($start_date . ' + ' . $total_days . ' days'));

        if (in_array($new_status, ['approved', 'ongoing'])) {
            $conn->query("UPDATE vehicles SET availability = 0 WHERE id = $vid");

            // Cancel all other pending bookings for the same vehicle that overlap these dates
            // For bookings with broken end_date (0000-00-00), compute from total_days
            $pending = $conn->query("
                SELECT id, start_date, end_date, total_days FROM bookings
                WHERE id != $booking_id
                  AND vehicle_id = $vid
                  AND status = 'pending'
            ");
            if ($pending) {
                while ($pb = $pending->fetch_assoc()) {
                    $pb_start = $pb['start_date'];
                    $pb_end   = (!empty($pb['end_date']) && $pb['end_date'] !== '0000-00-00')
                        ? $pb['end_date']
                        : date('Y-m-d', strtotime($pb_start . ' + ' . (int)$pb['total_days'] . ' days'));

                    // Overlap: approved_start < pending_end AND approved_end > pending_start
                    if ($start_date < $pb_end && $end_date > $pb_start) {
                        $pbid = (int)$pb['id'];
                        $conn->query("UPDATE bookings SET status = 'cancelled' WHERE id = $pbid");
                    }
                }
            }

        } elseif (in_array($new_status, ['completed', 'cancelled'])) {
            $conn->query("UPDATE vehicles SET availability = 1 WHERE id = $vid");
        }
    }

    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $booking_id);
    $stmt->execute();
    setFlash('success', 'Booking #' . str_pad($booking_id, 5, '0', STR_PAD_LEFT) . ' updated to ' . ucfirst($new_status));
    redirect('admin-bookings.php' . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
}

// Filter
$allowed_filters = ['all', 'pending', 'approved', 'ongoing', 'completed', 'cancelled'];
$status_filter   = (isset($_GET['status']) && in_array($_GET['status'], $allowed_filters)) ? $_GET['status'] : 'all';

// Counts per status
$statusCounts = [];
$cr = $conn->query("SELECT status, COUNT(*) AS c FROM bookings GROUP BY status");
while ($row = $cr->fetch_assoc()) { $statusCounts[$row['status']] = $row['c']; }
$totalCount = array_sum($statusCounts);

// Search
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$sql = "SELECT b.*, u.name AS user_name, u.email AS user_email,
               u.phone_number AS user_phone,
               v.name AS vehicle_name, v.type AS vehicle_type
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN vehicles v ON b.vehicle_id = v.id";

$conditions = [];
$params     = [];
$types      = '';

if ($status_filter !== 'all') {
    $conditions[] = "b.status = ?";
    $params[]     = $status_filter;
    $types       .= 's';
}
if ($search) {
    $like         = '%' . $search . '%';
    $conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR v.name LIKE ?)";
    $params[]     = $like; $params[] = $like; $params[] = $like;
    $types       .= 'sss';
}

if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY b.created_at DESC';

if ($params) {
    $stmt2 = $conn->prepare($sql);
    $stmt2->bind_param($types, ...$params);
    $stmt2->execute();
    $bookings = $stmt2->get_result();
} else {
    $bookings = $conn->query($sql);
}

$flash = getFlash('success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management - Admin | Bhatbhatey Rental</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
        body { background:#f1f5f9; display:flex; min-height:100vh; }

        /* SIDEBAR */
        .sidebar { width:240px; background:#1e293b; color:white; display:flex; flex-direction:column; min-height:100vh; position:fixed; top:0; left:0; z-index:100; }
        .sidebar-logo { padding:20px 24px; border-bottom:1px solid rgba(255,255,255,0.08); display:flex; align-items:center; gap:12px; }
        .sidebar-logo img { height:36px; }
        .sidebar-logo span { font-size:13px; color:#94a3b8; font-weight:600; }
        .sidebar-menu { padding:16px 12px; flex:1; display:flex; flex-direction:column; }
        .sidebar-menu a { display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:8px; color:#94a3b8; text-decoration:none; font-size:14px; font-weight:500; margin-bottom:4px; transition:all 0.2s; }
        .sidebar-menu a i { width:18px; text-align:center; font-size:15px; }
        .sidebar-menu a:hover { background:rgba(255,255,255,0.07); color:white; }
        .sidebar-menu a.active { background:#f97316; color:white; }
        .logout-link { margin-top:auto; }
        .logout-link a { color:#fca5a5 !important; }
        .logout-link a:hover { background:rgba(239,68,68,0.15) !important; }

        /* MAIN */
        .main-content { margin-left:240px; flex:1; padding:28px; }
        .page-header { margin-bottom:24px; display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; }
        .page-header h1 { font-size:22px; font-weight:700; color:#1e293b; }
        .page-header p { color:#64748b; font-size:14px; margin-top:3px; }

        /* FLASH */
        .flash { display:flex; align-items:center; gap:10px; padding:12px 18px; border-radius:10px; margin-bottom:18px; font-size:14px; font-weight:500; background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; }

        /* SEARCH */
        .top-controls { display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; align-items:center; }
        .search-wrap { display:flex; background:white; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.05); }
        .search-wrap input { padding:9px 14px; border:none; outline:none; font-size:14px; min-width:220px; }
        .search-wrap button { padding:9px 16px; background:#f97316; border:none; color:white; cursor:pointer; font-size:14px; }
        .search-wrap button:hover { background:#ea6c10; }
        .btn-clear { padding:9px 14px; border-radius:10px; border:1px solid #e2e8f0; background:white; color:#475569; font-size:14px; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
        .btn-clear:hover { background:#f1f5f9; }

        /* FILTER TABS */
        .filter-tabs { display:flex; gap:8px; margin-bottom:18px; flex-wrap:wrap; }
        .filter-tab { padding:8px 14px; border-radius:8px; text-decoration:none; font-size:13px; font-weight:600; border:1px solid #e2e8f0; background:white; color:#475569; display:flex; align-items:center; gap:6px; transition:all 0.2s; }
        .filter-tab:hover { border-color:#f97316; color:#f97316; }
        .filter-tab.active { background:#f97316; color:white; border-color:#f97316; }
        .tab-count { background:rgba(0,0,0,0.12); border-radius:20px; padding:1px 7px; font-size:11px; }
        .filter-tab.active .tab-count { background:rgba(255,255,255,0.25); }

        /* TABLE */
        .table-card { background:white; border-radius:14px; box-shadow:0 1px 4px rgba(0,0,0,0.07); overflow:hidden; }
        table { width:100%; border-collapse:collapse; }
        thead th { background:#f8fafc; padding:12px 16px; text-align:left; font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid #e2e8f0; white-space:nowrap; }
        tbody tr { border-bottom:1px solid #f1f5f9; transition:background 0.15s; }
        tbody tr:hover { background:#fafbfc; }
        tbody tr:last-child { border-bottom:none; }
        td { padding:13px 16px; font-size:14px; color:#1e293b; vertical-align:middle; }
        .td-secondary { font-size:12px; color:#64748b; margin-top:2px; }

        /* STATUS BADGES */
        .status-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600; }
        .badge-pending   { background:#fef9c3; color:#ca8a04; }
        .badge-approved  { background:#d1fae5; color:#065f46; }
        .badge-ongoing   { background:#fce7f3; color:#be185d; }
        .badge-completed { background:#dbeafe; color:#2563eb; }
        .badge-cancelled { background:#fee2e2; color:#dc2626; }

        .payment-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600; }

        /* ACTION BUTTONS */
        .action-cell { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
        .btn-approve { display:inline-flex; align-items:center; gap:5px; padding:6px 11px; border-radius:7px; background:#dcfce7; color:#16a34a; border:1px solid #bbf7d0; font-size:12px; cursor:pointer; font-weight:600; transition:all 0.2s; }
        .btn-approve:hover { background:#bbf7d0; }
        .btn-reject  { display:inline-flex; align-items:center; gap:5px; padding:6px 11px; border-radius:7px; background:#fee2e2; color:#dc2626; border:1px solid #fecaca; font-size:12px; cursor:pointer; font-weight:600; transition:all 0.2s; }
        .btn-reject:hover { background:#fecaca; }
        .btn-view    { display:inline-flex; align-items:center; gap:5px; padding:6px 11px; border-radius:7px; background:#eff6ff; color:#3b82f6; border:1px solid #bfdbfe; font-size:12px; cursor:pointer; font-weight:600; transition:all 0.2s; }
        .btn-view:hover { background:#dbeafe; }
        select.status-select { padding:6px 10px; border-radius:8px; border:1px solid #e2e8f0; font-size:13px; background:#f8fafc; cursor:pointer; outline:none; color:#1e293b; }
        select.status-select:focus { border-color:#f97316; }

        /* EMPTY STATE */
        .empty-state { text-align:center; padding:60px; color:#94a3b8; }
        .empty-state i { font-size:48px; margin-bottom:12px; display:block; }

        /* MODAL */
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:1000; display:none; align-items:center; justify-content:center; backdrop-filter:blur(4px); }
        .modal-overlay.open { display:flex; }
        .modal-box { background:white; border-radius:16px; padding:28px; max-width:500px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.2); }
        .modal-title { font-size:17px; font-weight:700; color:#1e293b; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
        .modal-title i { color:#f97316; }
        .modal-detail-row { display:flex; justify-content:space-between; align-items:flex-start; padding:9px 0; border-bottom:1px solid #f1f5f9; font-size:14px; gap:12px; }
        .modal-detail-row:last-child { border-bottom:none; }
        .modal-label { color:#64748b; flex-shrink:0; }
        .modal-value { font-weight:600; color:#1e293b; text-align:right; word-break:break-word; }
        .btn-close-modal { margin-top:18px; padding:10px 20px; background:#f1f5f9; border:none; border-radius:8px; font-size:14px; font-weight:600; color:#475569; cursor:pointer; width:100%; }
        .btn-close-modal:hover { background:#e2e8f0; }

        /* ADDON PILL */
        .addon-pill { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:20px; font-size:11px; font-weight:600; background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }
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
        <div>
            <h1>Booking Management</h1>
            <p>View, approve, reject and monitor all vehicle bookings</p>
        </div>
    </div>

    <?php if ($flash): ?>
    <div class="flash"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>

    <!-- SEARCH -->
    <div class="top-controls">
        <form method="GET" class="search-wrap" style="display:flex;">
            <?php if ($status_filter !== 'all'): ?>
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
            <?php endif; ?>
            <input type="text" name="search" placeholder="Search customer, vehicle..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit"><i class="fas fa-magnifying-glass"></i></button>
        </form>
        <?php if ($search): ?>
        <a href="admin-bookings.php<?php echo $status_filter !== 'all' ? '?status='.$status_filter : ''; ?>" class="btn-clear">
            <i class="fas fa-xmark"></i> Clear
        </a>
        <?php endif; ?>
    </div>

    <!-- FILTER TABS -->
    <div class="filter-tabs">
        <?php
        $tabs = [
            'all'       => ['fa-list',          'All',       $totalCount],
            'pending'   => ['fa-clock',          'Pending',   $statusCounts['pending'] ?? 0],
            'approved'  => ['fa-circle-check',   'Approved',  $statusCounts['approved'] ?? 0],
            'ongoing'   => ['fa-car-side',       'Ongoing',   $statusCounts['ongoing'] ?? 0],
            'completed' => ['fa-flag-checkered', 'Completed', $statusCounts['completed'] ?? 0],
            'cancelled' => ['fa-ban',            'Cancelled', $statusCounts['cancelled'] ?? 0],
        ];
        foreach ($tabs as $key => [$icon, $label, $count]):
        ?>
        <a href="?status=<?php echo $key; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>"
           class="filter-tab <?php echo $status_filter === $key ? 'active' : ''; ?>">
            <i class="fas <?php echo $icon; ?>"></i> <?php echo $label; ?>
            <span class="tab-count"><?php echo $count; ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Vehicle</th>
                    <th>Dates</th>
                    <th>Add-ons</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($bookings && $bookings->num_rows > 0): ?>
                    <?php while ($b = $bookings->fetch_assoc()):
                        $statusBadgeMap = ['pending'=>'badge-pending','approved'=>'badge-approved','ongoing'=>'badge-ongoing','completed'=>'badge-completed','cancelled'=>'badge-cancelled'];
                        $statusIconMap  = ['pending'=>'fa-clock','approved'=>'fa-circle-check','ongoing'=>'fa-car-side','completed'=>'fa-flag-checkered','cancelled'=>'fa-ban'];
                        $sc  = $statusBadgeMap[$b['status']] ?? 'badge-pending';
                        $si  = $statusIconMap[$b['status']]  ?? 'fa-clock';
                        $ic  = ['Car'=>'fa-car','Bike'=>'fa-motorcycle','Scooter'=>'fa-motorcycle'][$b['vehicle_type']] ?? 'fa-car';
                        $bId = $b['id'];
                        $bIdPad = '#' . str_pad($bId, 5, '0', STR_PAD_LEFT);
                        $days = $b['total_days'] ?? '—';
                    ?>
                    <tr>
                        <td style="color:#94a3b8; font-weight:700; white-space:nowrap;"><?php echo $bIdPad; ?></td>
                        <td>
                            <div style="font-weight:600;"><?php echo htmlspecialchars($b['user_name']); ?></div>
                            <div class="td-secondary"><?php echo htmlspecialchars($b['user_email']); ?></div>
                            <?php if (!empty($b['user_phone'])): ?>
                            <div class="td-secondary"><i class="fas fa-phone" style="font-size:10px;"></i> <?php echo htmlspecialchars($b['user_phone']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight:600;"><?php echo htmlspecialchars($b['vehicle_name']); ?></div>
                            <div class="td-secondary"><i class="fas <?php echo $ic; ?>"></i> <?php echo $b['vehicle_type']; ?></div>
                        </td>
                        <td style="white-space:nowrap;">
                            <div><?php echo date('M d', strtotime($b['start_date'])); ?> &rarr; <?php echo date('M d, Y', strtotime($b['end_date'])); ?></div>
                            <div class="td-secondary"><?php echo $days; ?> days</div>
                        </td>
                        <td>
                            <span style="color:#94a3b8; font-size:12px;">—</span>
                        </td>
                        <td style="font-weight:700; color:#f97316; white-space:nowrap;">NPR <?php echo number_format($b['total_price'], 2); ?></td>
                        <td>
                            <?php if (strtolower($b['payment_method']) === 'online'): ?>
                                <span class="payment-badge" style="background:#eff6ff; color:#3b82f6;"><i class="fas fa-credit-card"></i> Online</span>
                            <?php else: ?>
                                <span class="payment-badge" style="background:#f0fdf4; color:#16a34a;"><i class="fas fa-money-bill-wave"></i> Cash</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $sc; ?>">
                                <i class="fas <?php echo $si; ?>"></i> <?php echo ucfirst($b['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-cell">
                                <?php if ($b['status'] === 'pending'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="booking_id" value="<?php echo $bId; ?>">
                                        <input type="hidden" name="new_status" value="approved">
                                        <button type="submit" class="btn-approve"><i class="fas fa-check"></i> Approve</button>
                                    </form>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="booking_id" value="<?php echo $bId; ?>">
                                        <input type="hidden" name="new_status" value="cancelled">
                                        <button type="submit" class="btn-reject"><i class="fas fa-times"></i> Reject</button>
                                    </form>
                                <?php else: ?>
                                    <select class="status-select" onchange="updateStatus(<?php echo $bId; ?>, this.value, this)" data-original="<?php echo $b['status']; ?>">
                                        <option value="pending"   <?php echo $b['status']==='pending'  ?'selected':''; ?>>Pending</option>
                                        <option value="approved"  <?php echo $b['status']==='approved' ?'selected':''; ?>>Approved</option>
                                        <option value="ongoing"   <?php echo $b['status']==='ongoing'  ?'selected':''; ?>>Ongoing</option>
                                        <option value="completed" <?php echo $b['status']==='completed'?'selected':''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $b['status']==='cancelled'?'selected':''; ?>>Cancelled</option>
                                    </select>
                                <?php endif; ?>
                                <button class="btn-view"
                                    onclick='showDetail(<?php echo json_encode([
                                        "id"       => $bIdPad,
                                        "customer" => $b["user_name"],
                                        "email"    => $b["user_email"],
                                        "phone"    => $b["user_phone"] ?? "N/A",
                                        "vehicle"  => $b["vehicle_name"] . " (" . $b["vehicle_type"] . ")",
                                        "start"    => date("M d, Y", strtotime($b["start_date"])),
                                        "end"      => date("M d, Y", strtotime($b["end_date"])),
                                        "days"     => $b["total_days"] ?? "—",
                                        "payment"  => $b["payment_method"],
                                        "total"    => "NPR " . number_format($b["total_price"], 2),
                                        "status"   => ucfirst($b["status"]),
                                        "created"  => date("M d, Y", strtotime($b["created_at"])),
                                    ]); ?>)'>
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="fas fa-calendar-xmark"></i>
                                <?php echo $search ? "No bookings match your search." : "No bookings found for this filter."; ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- DETAIL MODAL -->
<div class="modal-overlay" id="detailModal">
    <div class="modal-box">
        <div class="modal-title"><i class="fas fa-calendar-days"></i> Booking Details</div>
        <div id="modalContent"></div>
        <button class="btn-close-modal" onclick="closeModal()"><i class="fas fa-times"></i> Close</button>
    </div>
</div>

<script>
function updateStatus(bookingId, newStatus, selectEl) {
    if (!confirm('Update booking to "' + newStatus + '"?')) {
        selectEl.value = selectEl.dataset.original || selectEl.value;
        return;
    }
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

function showDetail(d) {
    const rows = [
        ['Booking ID', d.id],
        ['Customer', d.customer],
        ['Email', d.email],
        ['Phone', d.phone],
        ['Vehicle', d.vehicle],
        ['Start Date', d.start],
        ['End Date', d.end],
        ['Duration', d.days + ' day(s)'],
        ['Payment', d.payment],
        ['Total', d.total],
        ['Status', d.status],
        ['Booked On', d.created],
    ];
    document.getElementById('modalContent').innerHTML = rows.map(([l, v]) =>
        `<div class="modal-detail-row"><span class="modal-label">${l}</span><span class="modal-value">${v}</span></div>`
    ).join('');
    document.getElementById('detailModal').classList.add('open');
}

function closeModal() {
    document.getElementById('detailModal').classList.remove('open');
}

document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>