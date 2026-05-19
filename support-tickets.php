<?php
require_once 'config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_ticket') {
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    
    if (!empty($subject) && !empty($message)) {
        $stmt = $conn->prepare("INSERT INTO support_tickets (user_id, subject, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $currentUser['id'], $subject, $message);
        if ($stmt->execute()) {
            redirect('support-tickets.php?msg=created');
        }
    }
}

// Fetch user tickets
$stmt = $conn->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $currentUser['id']);
$stmt->execute();
$tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets - Bhatbhatey Rental</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #f97316;
            --primary-light: #fff7ed;
            --dark: #0f172a;
            --dark-blue: #1e293b;
            --slate: #64748b;
            --border: #e2e8f0;
            --bg: #f1f5f9;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg); display: flex; min-height: 100vh; }
        a { text-decoration: none; transition: 0.2s; }

        /* SIDEBAR */
        .sidebar { width: 250px; background: var(--dark-blue); position: fixed; height: 100%; display: flex; flex-direction: column; box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15); z-index: 100; }
        .sidebar-logo { padding: 16px 18px; border-bottom: 1px solid rgba(255, 255, 255, 0.08); display: flex; align-items: center; gap: 10px; }
        .sidebar-logo img { height: 38px; width: auto; object-fit: contain; filter: brightness(0) invert(1); }
        .logo-fallback { display: none; width: 36px; height: 36px; background: var(--primary); border-radius: 9px; align-items: center; justify-content: center; color: white; font-size: 15px; flex-shrink: 0; }
        .sidebar-logo-text { display: flex; flex-direction: column; line-height: 1.2; }
        .sidebar-logo-text .lt-name { color: white; font-size: 15px; font-weight: 800; }
        .sidebar-logo-text .lt-sub { color: #64748b; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; }
        .sidebar-nav { padding: 16px 12px; flex: 1; overflow-y: auto; }
        .nav-section-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; color: #475569; font-weight: 700; padding: 0 8px; margin: 16px 0 6px; }
        .sidebar-nav a { display: flex; align-items: center; gap: 11px; padding: 11px 12px; border-radius: 10px; color: #94a3b8; font-size: 14px; font-weight: 600; margin-bottom: 3px; }
        .sidebar-nav a i { width: 18px; text-align: center; font-size: 14px; }
        .sidebar-nav a:hover { background: #334155; color: white; }
        .sidebar-nav a.active { background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3); }
        .sidebar-nav a.danger:hover { background: #7f1d1d; color: #fca5a5; }
        .sidebar-footer { padding: 12px 14px; border-top: 1px solid rgba(255, 255, 255, 0.07); }
        .user-card { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 12px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.07); }
        .user-initials { width: 36px; height: 36px; background: var(--primary); border-radius: 9px; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 800; flex-shrink: 0; letter-spacing: 0.5px; text-transform: uppercase; }
        .user-info-inner { flex: 1; min-width: 0; }
        .u-name { color: white; font-size: 13px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .u-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 10px; color: #94a3b8; margin-top: 2px; }
        .u-badge i { font-size: 7px; color: #22c55e; }

        /* MAIN */
        .main { margin-left: 250px; padding: 32px; width: 100%; min-height: 100vh; }
        .page-header { margin-bottom: 28px; display: flex; justify-content: space-between; align-items: center; }
        .page-header h1 { font-size: 24px; font-weight: 800; color: var(--dark); }
        .page-header p { color: var(--slate); margin-top: 4px; font-size: 14px; }
        .btn-primary { padding: 10px 20px; border-radius: 9px; font-size: 14px; font-weight: 700; background: var(--primary); color: white; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; }
        .btn-primary:hover { background: #ea6c10; box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3); }
        
        .flash { padding: 13px 18px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }

        .ticket-list { display: grid; gap: 16px; }
        .ticket-card { background: white; border-radius: 12px; border: 1px solid var(--border); padding: 20px; display: flex; justify-content: space-between; align-items: center; transition: 0.2s; text-decoration: none; color: inherit; }
        .ticket-card:hover { border-color: var(--primary); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .ticket-info h3 { font-size: 16px; font-weight: 700; color: var(--dark); margin-bottom: 6px; }
        .ticket-info p { font-size: 13px; color: var(--slate); display: flex; align-items: center; gap: 8px; }
        .status-badge { padding: 4px 10px; border-radius: 100px; font-size: 12px; font-weight: 700; text-transform: capitalize; }
        .status-open { background: #fef9c3; color: #ca8a04; }
        .status-in_progress { background: #dbeafe; color: #1d4ed8; }
        .status-closed { background: #f1f5f9; color: #64748b; }

        /* MODAL */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 28px; border-radius: 16px; width: 100%; max-width: 500px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h2 { font-size: 20px; font-weight: 800; color: var(--dark); }
        .close-modal { background: none; border: none; font-size: 24px; color: var(--slate); cursor: pointer; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--slate); margin-bottom: 6px; }
        .form-input { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 9px; font-size: 14px; outline: none; }
        .form-input:focus { border-color: var(--primary); }
        textarea.form-input { resize: vertical; min-height: 120px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="index.php" class="sidebar-logo">
            <img src="nobglogo.png" alt="Bhatbhatey" onerror="this.style.display='none'; document.querySelector('.logo-fallback').style.display='flex';">
            <div class="logo-fallback"><i class="fas fa-car"></i></div>
            <div class="sidebar-logo-text">
                <span class="lt-name">Bhatbhatey</span>
                <span class="lt-sub">Rental</span>
            </div>
        </a>

        <div class="sidebar-nav">
            <div class="nav-section-label">Main</div>
            <a href="user/user-dashboard.php"><i class="fas fa-gauge-high"></i> Dashboard</a>
            <a href="vehicles.php"><i class="fas fa-car"></i> Browse Vehicles</a>
            <a href="my-bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a>
            <a href="support-tickets.php" class="active"><i class="fas fa-ticket-alt"></i> Support Tickets</a>
            <div class="nav-section-label">Account</div>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php" class="danger"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </div>

        <div class="sidebar-footer">
            <div class="user-card">
                <div class="user-initials">
                    <?php echo strtoupper(substr($currentUser['name'] ?? 'U', 0, 2)); ?>
                </div>
                <div class="user-info-inner">
                    <div class="u-name"><?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?></div>
                    <div class="u-badge"><i class="fas fa-circle"></i> Active Member</div>
                </div>
            </div>
        </div>
    </div>

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Support Tickets</h1>
                <p>Manage your support requests and issues.</p>
            </div>
            <button class="btn-primary" onclick="document.getElementById('ticketModal').classList.add('active')">
                <i class="fas fa-plus"></i> New Ticket
            </button>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
            <div class="flash"><i class="fas fa-check-circle"></i> Support ticket created successfully! Our team will respond shortly.</div>
        <?php endif; ?>

        <div class="ticket-list">
            <?php if (count($tickets) > 0): ?>
                <?php foreach ($tickets as $ticket): ?>
                    <a href="ticket-view.php?id=<?php echo $ticket['id']; ?>" class="ticket-card">
                        <div class="ticket-info">
                            <h3><?php echo htmlspecialchars($ticket['subject']); ?></h3>
                            <p>
                                <span><i class="fas fa-hashtag"></i> TKT-<?php echo str_pad($ticket['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                &bull;
                                <span><i class="far fa-clock"></i> <?php echo date('M d, Y h:i A', strtotime($ticket['created_at'])); ?></span>
                            </p>
                        </div>
                        <div class="status-badge status-<?php echo $ticket['status']; ?>">
                            <?php echo str_replace('_', ' ', $ticket['status']); ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align:center; padding: 40px; background: white; border-radius: 12px; border: 1px solid var(--border);">
                    <i class="fas fa-ticket-alt" style="font-size: 40px; color: var(--border); margin-bottom: 16px;"></i>
                    <h3 style="color: var(--dark); font-size: 18px; margin-bottom: 8px;">No Tickets Found</h3>
                    <p style="color: var(--slate); font-size: 14px;">You haven't submitted any support tickets yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create Ticket Modal -->
    <div class="modal" id="ticketModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New Ticket</h2>
                <button class="close-modal" onclick="document.getElementById('ticketModal').classList.remove('active')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_ticket">
                <div class="form-group">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-input" required placeholder="Brief description of your issue...">
                </div>
                <div class="form-group">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-input" required placeholder="Describe your issue in detail..."></textarea>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%; justify-content: center;">Submit Ticket</button>
            </form>
        </div>
    </div>
</body>
</html>
