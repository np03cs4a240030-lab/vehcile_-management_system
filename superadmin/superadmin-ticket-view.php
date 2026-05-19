<?php
session_start();
require_once '../config.php';

if (!isLoggedIn() || !hasRole('super_admin')) {
    redirect('../admin/admin-login.php');
}

$currentUser = getCurrentUser();

if (!isset($_GET['id'])) {
    redirect('superadmin-tickets.php');
}
$ticket_id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT st.*, u.name as user_name FROM support_tickets st JOIN users u ON st.user_id = u.id WHERE st.id = ?");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();

if (!$ticket) {
    redirect('superadmin-tickets.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'reply') {
        $message = sanitize($_POST['message']);
        if (!empty($message)) {
            $stmt = $conn->prepare("INSERT INTO ticket_replies (ticket_id, sender_id, message) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $ticket_id, $currentUser['id'], $message);
            $stmt->execute();
            
            if ($ticket['status'] === 'open') {
                $conn->query("UPDATE support_tickets SET status = 'in_progress' WHERE id = $ticket_id");
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $new_status = $_POST['status'];
        if (in_array($new_status, ['open', 'in_progress', 'closed'])) {
            $stmt = $conn->prepare("UPDATE support_tickets SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $ticket_id);
            $stmt->execute();
        }
    }
    redirect('superadmin-ticket-view.php?id=' . $ticket_id);
}

$stmt = $conn->prepare("
    SELECT tr.*, u.name, u.role 
    FROM ticket_replies tr 
    JOIN users u ON tr.sender_id = u.id 
    WHERE tr.ticket_id = ? 
    ORDER BY tr.created_at ASC
");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$replies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Ticket - Super Admin</title>
    
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --bg-dark: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.1);
            --success: #10b981;
            --danger: #ef4444;
        }

        body {
            background: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            margin: 0;
        }

        .dashboard-layout { display: flex; min-height: 100vh; }

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

        .main-content { margin-left: 240px; width: calc(100% - 240px); padding: 28px; min-height: 100vh; box-sizing: border-box; overflow-x: hidden; }

        .chat-container { background: var(--card-bg); border-radius: 12px; border: 1px solid var(--border); overflow: hidden; display: flex; flex-direction: column; min-height: 600px; margin-top: 20px; }
        .chat-header { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.2); }
        .chat-title { font-size: 18px; font-weight: 700; color: white; margin-bottom: 4px; }
        .chat-meta { font-size: 13px; color: var(--text-muted); }
        
        .status-form { display: flex; align-items: center; gap: 10px; }
        .status-select { padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px; font-weight: 600; outline: none; background: rgba(15, 23, 42, 0.8); color: white; }
        .btn-update { padding: 8px 16px; border-radius: 6px; background: rgba(255,255,255,0.1); color: white; border: 1px solid var(--border); font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-update:hover { background: rgba(255,255,255,0.2); }

        .chat-messages { flex: 1; padding: 24px; overflow-y: auto; }
        .message { margin-bottom: 24px; max-width: 75%; }
        .message.mine { margin-left: auto; }
        .message-sender { font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
        .message.mine .message-sender { justify-content: flex-end; }
        
        .message-bubble { padding: 14px 18px; border-radius: 16px; font-size: 14px; line-height: 1.5; color: white; background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-top-left-radius: 4px; }
        .message.mine .message-bubble { background: var(--primary); color: white; border: none; border-top-left-radius: 16px; border-top-right-radius: 4px; }
        
        .message-time { font-size: 11px; color: var(--text-muted); margin-top: 6px; }
        .message.mine .message-time { text-align: right; }

        .chat-input { padding: 20px 24px; border-top: 1px solid var(--border); background: rgba(0,0,0,0.2); }
        .chat-input form { display: flex; gap: 16px; align-items: flex-end; }
        .chat-textarea { flex: 1; padding: 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; outline: none; resize: none; height: 80px; font-family: inherit; background: rgba(15, 23, 42, 0.5); color: white; }
        .chat-textarea:focus { border-color: var(--primary); }
        .btn-send { padding: 0 24px; height: 80px; border-radius: 8px; background: var(--primary); color: white; border: none; font-size: 15px; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-send:hover { background: var(--primary-hover); }
        
        .admin-badge { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 2px 6px; border-radius: 4px; font-size: 9px; text-transform: uppercase; }
        .user-badge { background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3); padding: 2px 6px; border-radius: 4px; font-size: 9px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
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
                <a href="superadmin-bookings.php"><i class="fas fa-calendar-days"></i> Bookings</a>
                <a href="superadmin-tickets.php" class="active"><i class="fas fa-ticket-alt"></i> Support Tickets</a>
                <a href="superadmin-settings.php"><i class="fas fa-gear"></i> Settings</a>
                <div class="logout-link">
                    <a href="../logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <div>
                <a href="superadmin-tickets.php" style="color: var(--text-muted); text-decoration: none; font-size: 14px; margin-bottom: 8px; display: inline-block;">
                    <i class="fas fa-arrow-left"></i> Back to Tickets
                </a>
                <h1 style="font-size: 24px; font-weight: 700; margin: 0 0 4px 0;">Ticket #<?php echo str_pad($ticket['id'], 4, '0', STR_PAD_LEFT); ?></h1>
            </div>

            <div class="chat-container">
                <div class="chat-header">
                    <div>
                        <div class="chat-title"><?php echo htmlspecialchars($ticket['subject']); ?></div>
                        <div class="chat-meta">Opened by <strong><?php echo htmlspecialchars($ticket['user_name']); ?></strong> on <?php echo date('M d, Y h:i A', strtotime($ticket['created_at'])); ?></div>
                    </div>
                    <form method="POST" class="status-form">
                        <input type="hidden" name="action" value="update_status">
                        <select name="status" class="status-select">
                            <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                        <button type="submit" class="btn-update">Update</button>
                    </form>
                </div>

                <div class="chat-messages" id="chatMessages">
                    <div class="message">
                        <div class="message-sender">
                            <?php echo htmlspecialchars($ticket['user_name']); ?>
                            <span class="user-badge">User</span>
                        </div>
                        <div class="message-bubble"><?php echo nl2br(htmlspecialchars($ticket['message'])); ?></div>
                        <div class="message-time"><?php echo date('M d, h:i A', strtotime($ticket['created_at'])); ?></div>
                    </div>

                    <?php foreach ($replies as $reply): 
                        $isMine = ($reply['sender_id'] == $currentUser['id']);
                        $isAdmin = in_array($reply['role'], ['admin', 'super_admin']);
                    ?>
                        <div class="message <?php echo $isMine ? 'mine' : ''; ?>">
                            <div class="message-sender">
                                <?php echo $isMine ? 'You' : htmlspecialchars($reply['name']); ?>
                                <?php if ($isAdmin): ?><span class="admin-badge">Support</span><?php else: ?><span class="user-badge">User</span><?php endif; ?>
                            </div>
                            <div class="message-bubble"><?php echo nl2br(htmlspecialchars($reply['message'])); ?></div>
                            <div class="message-time"><?php echo date('M d, h:i A', strtotime($reply['created_at'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="chat-input">
                    <form method="POST">
                        <input type="hidden" name="action" value="reply">
                        <textarea name="message" class="chat-textarea" required placeholder="Type your reply here..."></textarea>
                        <button type="submit" class="btn-send"><i class="fas fa-paper-plane"></i> Send</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
    </script>
</body>
</html>
