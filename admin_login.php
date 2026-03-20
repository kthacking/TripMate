<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // Specifically check for admin role
    $sql = "SELECT id, name, password, role FROM users WHERE email = '$email' AND role = 'admin'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            unset($_SESSION['admin_login_error']);
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $_SESSION['admin_login_error'] = "Invalid admin password.";
        }
    } else {
        $_SESSION['admin_login_error'] = "Admin account not found.";
    }
    
    // Redirect back to the page where login was attempted
    $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
    header("Location: $redirect");
    exit();
} else {
    // If someone tries to access this page directly via GET, send them home
    header("Location: index.php");
    exit();
}
?>
