<?php require_once 'db.php'; ?>
<?php
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT id, name, password, role FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Simple password verification
        if (password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>
<?php include 'header.php'; ?>

<div class="auth-wrapper">
    <div class="auth-card fade-in">
        <h2 class="auth-title">Welcome Back</h2>
        <p class="auth-subtitle">Login to your TripMate account</p>
        
        <?php if($error): ?>
            <div style="background: #fed7d7; color: #c53030; padding: 10px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required placeholder="john@example.com">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required placeholder="********">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Log In</button>
        </form>
        
        <p class="mt-4" style="font-size: 0.9rem; color: var(--text-light);">
            Don't have an account? <a href="register.php" style="color: var(--primary-color); font-weight: 600;">Register</a>
        </p>
    </div>
</div>
</body>
</html>
