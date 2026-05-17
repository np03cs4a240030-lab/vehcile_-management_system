<?php
require_once '../includes/connection.php';


$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        handleLogin();
        break;
    case 'DELETE':
        handleLogout();
        break;
    default:
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function handleLogin() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $email = sanitize($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password required']);
        return;
    }

    $sql = "SELECT * FROM users WHERE email = ? AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            unset($user['password']);
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
}

function handleLogout() {
    session_destroy();
    echo json_encode(['success' => true]);
}
?>
