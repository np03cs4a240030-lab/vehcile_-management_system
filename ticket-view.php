<?php
require_once 'config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();

if (!isset($_GET['id'])) {
    redirect('support-tickets.php');
}
$ticket_id = (int)$_GET['id'];

// Check if ticket belongs to user
$stmt = $conn->prepare("SELECT * FROM support_tickets WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $ticket_id, $currentUser['id']);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();

if (!$ticket) {
    redirect('support-tickets.php');
}

// Handle reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply') {
    $message = sanitize($_POST['message']);
    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO ticket_replies (ticket_id, sender_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $ticket_id, $currentUser['id'], $message);
        $stmt->execute();

        // If ticket is closed, reopen it
        if ($ticket['status'] === 'closed') {
            $stmt = $conn->prepare("UPDATE support_tickets SET status = 'open' WHERE id = ?");
            $stmt->bind_param("i", $ticket_id);
            $stmt->execute();
        }
        redirect('ticket-view.php?id=' . $ticket_id);
    }
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
    <title>Ticket #<?php echo str_pad($ticket['id'], 4, '0', STR_PAD_LEFT); ?> - Bhatbhatey Rental</title>
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
        .sidebar-footer { padding: 12px 14px; border-top: 1px solid rgba(255, 255, 255, 0.07); }
        .user-card { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 12px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.07); }
        .user-initials { width: 36px; height: 36px; background: var(--primary); border-radius: 9px; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 800; flex-shrink: 0; text-transform: uppercase; }
        .user-info-inner { flex: 1; min-width: 0; }
        .u-name { color: white; font-size: 13px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* MAIN */
        .main { margin-left: 250px; padding: 32px; width: 100%; min-height: 100vh; }
        .page-header { margin-bottom: 28px; display: flex; justify-content: space-between; align-items: flex-start; }
        .page-header h1 { font-size: 24px; font-weight: 800; color: var(--dark); margin-bottom: 8px; }
        .page-header p { color: var(--slate); font-size: 14px; display: flex; align-items: center; gap: 8px; }
        
        .btn-secondary { padding: 10px 20px; border-radius: 9px; font-size: 14px; font-weight: 600; background: white; color: var(--slate); border: 1px solid var(--border); cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; }
        .btn-secondary:hover { background: #f8fafc; color: var(--dark); }

        .btn-primary { padding: 12px 24px; border-radius: 9px; font-size: 14px; font-weight: 700; background: var(--primary); color: white; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; transition: 0.2s; width: 100%; }
        .btn-primary:hover { background: #ea6c10; box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3); }

        .chat-container { background: white; border-radius: 16px; border: 1px solid var(--border); overflow: hidden; display: flex; flex-direction: column; min-height: 500px; }
        
        .chat-header { padding: 20px 24px; border-bottom: 1px solid var(--border); background: #f8fafc; }
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 100px; font-size: 12px; font-weight: 700; text-transform: capitalize; margin-bottom: 12px; }
        .status-open { background: #fef9c3; color: #ca8a04; }
        .status-in_progress { background: #dbeafe; color: #1d4ed8; }
        .status-closed { background: #f1f5f9; color: #64748b; }
        
        .chat-messages { flex: 1; padding: 24px; overflow-y: auto; background: #f8fafc; }
        .message { margin-bottom: 24px; max-width: 80%; }
        .message.mine { margin-left: auto; }
        .message-sender { font-size: 12px; font-weight: 700; color: var(--slate); margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
        .message.mine .message-sender { justify-content: flex-end; }
        .sender-badge { padding: 2px 6px; border-radius: 4px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; background: #e2e8f0; color: #475569; }
        .admin-badge { background: #fee2e2; color: #dc2626; }
        
        .message-bubble { padding: 14px 18px; border-radius: 16px; font-size: 14px; line-height: 1.5; color: var(--dark); background: white; border: 1px solid var(--border); border-top-left-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .message.mine .message-bubble { background: var(--primary); color: white; border: none; border-top-left-radius: 16px; border-top-right-radius: 4px; box-shadow: 0 4px 12px rgba(249, 115, 22, 0.2); }
        
        .message-time { font-size: 11px; color: #94a3b8; margin-top: 6px; }
        .message.mine .message-time { text-align: right; }

        .chat-input { padding: 20px 24px; border-top: 1px solid var(--border); background: white; }
        .chat-input form { display: flex; gap: 16px; align-items: flex-end; }
        .chat-textarea { flex: 1; padding: 14px; border: 1px solid var(--border); border-radius: 12px; font-size: 14px; outline: none; resize: none; height: 80px; font-family: inherit; }
        .chat-textarea:focus { border-color: var(--primary); }
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
                </div>
            </div>
        </div>
    </div>

    <div class="main">
        <div class="page-header">
            <div>
                <a href="support-tickets.php" class="btn-secondary" style="margin-bottom: 16px;"><i class="fas fa-arrow-left"></i> Back to Tickets</a>
                <h1><?php echo htmlspecialchars($ticket['subject']); ?></h1>
                <p>
                    <span><i class="fas fa-hashtag"></i> TKT-<?php echo str_pad($ticket['id'], 4, '0', STR_PAD_LEFT); ?></span>
                    &bull;
                    <span><i class="far fa-clock"></i> <?php echo date('M d, Y h:i A', strtotime($ticket['created_at'])); ?></span>
                </p>
            </div>
        </div>

        <div class="chat-container">
            <div class="chat-header">
                <div class="status-badge status-<?php echo $ticket['status']; ?>">
                    <?php echo str_replace('_', ' ', $ticket['status']); ?>
                </div>
                <p style="font-size: 14px; color: var(--slate);">If your issue is resolved, an admin will close this ticket.</p>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <!-- Original Ticket Message -->
                <div class="message mine">
                    <div class="message-sender">You</div>
                    <div class="message-bubble"><?php echo nl2br(htmlspecialchars($ticket['message'])); ?></div>
                    <div class="message-time"><?php echo date('M d, h:i A', strtotime($ticket['created_at'])); ?></div>
                </div>

                <!-- Replies -->
                <?php foreach ($replies as $reply): 
                    $isMine = ($reply['sender_id'] == $currentUser['id']);
                    $isAdmin = in_array($reply['role'], ['admin', 'super_admin']);
                ?>
                    <div class="message <?php echo $isMine ? 'mine' : ''; ?>">
                        <div class="message-sender">
                            <?php echo $isMine ? 'You' : htmlspecialchars($reply['name']); ?>
                            <?php if ($isAdmin): ?><span class="sender-badge admin-badge">Support</span><?php endif; ?>
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
                    <button type="submit" class="btn-primary" style="width: auto; height: 80px; padding: 0 32px;"><i class="fas fa-paper-plane"></i> Send</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Scroll to bottom of chat
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
    </script>
</body>
</html>
