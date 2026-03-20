<?php require_once 'db.php'; ?>
<?php
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $conn->real_escape_string($_POST['role']);

    // Check if email exists
    $check = $conn->query("SELECT id FROM users WHERE email='$email'");
    if ($check->num_rows > 0) {
        $error = "Email already registered.";
    } else {
        $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role')";
        if ($conn->query($sql) === TRUE) {
            header("Location: login.php");
            exit();
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>
<?php include 'header.php'; ?>

<div class="auth-wrapper">
    <div class="auth-card fade-in">
        <h2 class="auth-title">Join TripMate</h2>
        <p class="auth-subtitle">Start your journey today</p>
        
        <?php if($error): ?>
            <div style="background: #fed7d7; color: #c53030; padding: 10px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" required placeholder="John Doe">
            </div>
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required placeholder="john@example.com">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required placeholder="********">
            </div>
            <div class="form-group">
                <label class="form-label">I am a</label>
                <select name="role" class="form-select">
                    <option value="student">Student (Traveler)</option>
                    <option value="tripmaker">TripMaker (Organizer)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
        </form>
        
        <p class="mt-4" style="font-size: 0.9rem; color: var(--text-light);">
            Already have an account? <a href="login.php" style="color: var(--primary-color); font-weight: 600;">Log In</a><br>
            <a href="admin_reg.php" style="color: var(--primary-color); font-weight: 600;">Admin Register</a>
        </p>
    </div>
</div>
</body>
</html>
