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

<style>
/* ── Theme Definitions ── */
:root {
    --primary-color: #ea580c;
    --primary-hover: #c2410c;
    --secondary-color: #1e293b;
    --text-color: #334155;
    --text-light: #64748b;
    --bg-light: #f8fafc;
    --white: #ffffff;
    --shadow-sm: 0 4px 6px rgba(0,0,0,0.05);
    --shadow-md: 0 10px 25px rgba(0,0,0,0.08);
    --radius-md: 20px;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.auth-wrapper {
    min-height: calc(100vh - 80px);
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #fffaf5 0%, #ffffff 100%);
    padding: 60px 20px;
}

.auth-card {
    background: var(--white);
    width: 100%;
    max-width: 480px;
    padding: 40px;
    border-radius: 24px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.04);
    border: 1px solid #f1f5f9;
    text-align: center;
}

.auth-icon {
    width: 60px;
    height: 60px;
    background: #ffedd5;
    color: var(--primary-color);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin: 0 auto 20px;
    box-shadow: var(--shadow-sm);
}

.auth-title {
    font-size: 2.2rem;
    font-weight: 800;
    color: var(--secondary-color);
    margin-bottom: 8px;
    letter-spacing: -0.5px;
}

.auth-subtitle {
    font-size: 1.05rem;
    color: var(--text-light);
    margin-bottom: 30px;
}

.form-group {
    margin-bottom: 20px;
    text-align: left;
}

.form-label {
    display: block;
    font-weight: 700;
    color: var(--secondary-color);
    margin-bottom: 8px;
    font-size: 0.95rem;
}

.form-control, .form-select {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    font-size: 0.95rem;
    color: var(--text-color);
    background: #f8fafc;
    transition: var(--transition);
    font-family: inherit;
}

.form-control:focus, .form-select:focus {
    outline: none;
    border-color: var(--primary-color);
    background: var(--white);
    box-shadow: 0 0 0 4px rgba(234, 88, 12, 0.1);
}

.form-select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='24' height='24' fill='%2364748b'%3E%3Cpath d='M12 15L7.75736 10.7574L9.17157 9.34315L12 12.1716L14.8284 9.34315L16.2426 10.7574L12 15Z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
}

.btn-primary {
    background: var(--primary-color);
    color: var(--white);
    padding: 14px;
    border-radius: 14px;
    font-weight: 800;
    font-size: 1rem;
    border: none;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: 0 4px 14px rgba(234, 88, 12, 0.25);
    width: 100%;
    margin-top: 10px;
}

.btn-primary:hover {
    background: var(--primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(234, 88, 12, 0.4);
}

.auth-alert {
    background: #fef2f2;
    color: #dc2626;
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 24px;
    border: 1px solid #fee2e2;
    display: flex;
    align-items: center;
    gap: 8px;
    text-align: left;
}

.auth-footer {
    margin-top: 25px;
    font-size: 0.95rem;
    color: var(--text-light);
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.auth-footer a {
    color: var(--primary-color);
    font-weight: 800;
    text-decoration: none;
    transition: var(--transition);
}

.auth-footer a:hover {
    color: var(--primary-hover);
}
</style>

<div class="auth-wrapper">
    <div class="auth-card fade-in">
        <div class="auth-icon"><i class="ri-user-add-line"></i></div>
        <h2 class="auth-title">Join TripMate</h2>
        <p class="auth-subtitle">Start your journey today</p>
        
        <?php if($error): ?>
            <div class="auth-alert">
                <i class="ri-error-warning-fill" style="font-size: 1.1rem;"></i> <?php echo $error; ?>
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
            <button type="submit" class="btn-primary">Create Account</button>
        </form>
        
        <div class="auth-footer">
            <span>Already have an account? <a href="login.php">Log In</a></span>
            <span><a href="admin_reg.php" style="font-size: 0.85rem; font-weight: 600;">Admin Register</a></span>
        </div>
    </div>
</div>
</body>
</html>
