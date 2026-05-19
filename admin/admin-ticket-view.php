<?php
session_start();
require_once '../config.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('admin-login.php');
}

$currentUser = getCurrentUser();

if (!isset($_GET['id'])) {
    redirect('admin-tickets.php');
}
$ticket_id = (int)$_GET['id'];

// Fetch ticket details
$stmt = $conn->prepare("SELECT st.*, u.name as user_name FROM support_tickets st JOIN users u ON st.user_id = u.id WHERE st.id = ?");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();

if (!$ticket) {
    redirect('admin-tickets.php');
}

// Handle reply or status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'reply') {
        $message = sanitize($_POST['message']);
        if (!empty($message)) {
            $stmt = $conn->prepare("INSERT INTO ticket_replies (ticket_id, sender_id, message) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $ticket_id, $currentUser['id'], $message);
            $stmt->execute();
            
            // Auto update to in_progress if it was open
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
    redirect('admin-ticket-view.php?id=' . $ticket_id);
}

// Fetch replies
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
    <title>View Ticket - Admin</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* sidebar styles */
        .sidebar { width: 240px; background: #1e293b; color: white; display: flex; flex-direction: column; min-height: 100vh; position: fixed; top: 0; left: 0; }
        .sidebar-logo { padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; gap: 12px; }
        .sidebar-logo img { height: 36px; }
        .sidebar-logo span { font-size: 13px; color: #94a3b8; font-weight: 600; }
        .sidebar-menu { padding: 16px 12px; flex: 1; display: flex; flex-direction: column; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; padding: 11px 14px; border-radius: 8px; color: #94a3b8; text-decoration: none; font-size: 14px; font-weight: 500; margin-bottom: 4px; transition: all 0.2s; }
        .sidebar-menu a i { width: 18px; text-align: center; font-size: 15px; }
        .sidebar-menu a:hover { background: rgba(255,255,255,0.07); color: white; }
        .sidebar-menu a.active { background: #f97316; color: white; }
        .sidebar-menu .logout-link { margin-top: auto; }
        .sidebar-menu .logout-link a { color: #fca5a5; }
        .sidebar-menu .logout-link a:hover { background: rgba(239,68,68,0.15); color: #fca5a5; }

        /* chat specific styles */
        .chat-container { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; flex-direction: column; min-height: 600px; margin-top: 20px; }
        .chat-header { padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
        .chat-title { font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
        .chat-meta { font-size: 13px; color: #64748b; }
        
        .status-form { display: flex; align-items: center; gap: 10px; }
        .status-select { padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; font-weight: 600; outline: none; }
        .btn-update { padding: 6px 12px; border-radius: 6px; background: #1e293b; color: white; border: none; font-size: 12px; font-weight: 600; cursor: pointer; }

        .chat-messages { flex: 1; padding: 24px; overflow-y: auto; background: #f8fafc; }
        .message { margin-bottom: 24px; max-width: 75%; }
        .message.mine { margin-left: auto; }
        .message-sender { font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
        .message.mine .message-sender { justify-content: flex-end; }
        
        .message-bubble { padding: 14px 18px; border-radius: 16px; font-size: 14px; line-height: 1.5; color: #1e293b; background: white; border: 1px solid #e2e8f0; border-top-left-radius: 4px; }
        .message.mine .message-bubble { background: #f97316; color: white; border: none; border-top-left-radius: 16px; border-top-right-radius: 4px; }
        
        .message-time { font-size: 11px; color: #94a3b8; margin-top: 6px; }
        .message.mine .message-time { text-align: right; }

        .chat-input { padding: 20px 24px; border-top: 1px solid #e2e8f0; background: white; }
        .chat-input form { display: flex; gap: 16px; align-items: flex-end; }
        .chat-textarea { flex: 1; padding: 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; resize: none; height: 80px; font-family: inherit; }
        .chat-textarea:focus { border-color: #f97316; }
        .btn-send { padding: 0 24px; height: 80px; border-radius: 8px; background: #f97316; color: white; border: none; font-size: 15px; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-send:hover { background: #ea6c10; }
        .admin-badge { background: #fee2e2; color: #dc2626; padding: 2px 6px; border-radius: 4px; font-size: 9px; text-transform: uppercase; }
        .user-badge { background: #e0e7ff; color: #4338ca; padding: 2px 6px; border-radius: 4px; font-size: 9px; text-transform: uppercase; }
    </style>
</head>
<body style="background: var(--brand-light-gray);">
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="../assets/images/logo.png" alt="Logo">
                <span>Admin Panel</span>
            </div>
            <nav class="sidebar-menu">
                <a href="admin-dashboard.php"><i class="fas fa-gauge-high"></i> Dashboard</a>
                <a href="admin-vehicles.php"><i class="fas fa-car"></i> Vehicles</a>
                <a href="admin-bookings.php"><i class="fas fa-calendar-days"></i> Bookings</a>
                <a href="admin-tickets.php" class="active"><i class="fas fa-ticket-alt"></i> Support Tickets</a>
                <a href="admin-users.php"><i class="fas fa-users"></i> Users</a>
                <a href="admin-change-password.php"><i class="fas fa-key"></i> Change Password</a>
                <div class="logout-link">
                    <a href="../logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
                </div>
            </nav>
        </aside>
        
        <main class="main-content">
            <div class="content-header">
                <div>
                    <a href="admin-tickets.php" style="color: #64748b; text-decoration: none; font-size: 14px; margin-bottom: 8px; display: inline-block;">
                        <i class="fas fa-arrow-left"></i> Back to Tickets
                    </a>
                    <h1>Ticket #<?php echo str_pad($ticket['id'], 4, '0', STR_PAD_LEFT); ?></h1>
                </div>
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
