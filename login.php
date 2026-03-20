<?php require_once 'db.php'; ?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            if ($user['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
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
    max-width: 440px;
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

.form-control {
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

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    background: var(--white);
    box-shadow: 0 0 0 4px rgba(234, 88, 12, 0.1);
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
</style>

<div class="auth-wrapper">
    <div class="auth-card fade-in">
        <div class="auth-icon"><i class="ri-user-smile-line"></i></div>
        <h2 class="auth-title">Welcome Back</h2>
        <p class="auth-subtitle">Login to your TripMate account</p>
        
        <?php if($error): ?>
            <div class="auth-alert">
                <i class="ri-error-warning-fill" style="font-size: 1.1rem;"></i> <?php echo $error; ?>
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
            <button type="submit" class="btn-primary">Secure Login</button>
        </form>
        
        <p class="mt-4" style="font-size: 0.95rem; color: var(--text-light); margin-top: 25px;">
            Don't have an account? <a href="register.php" style="color: var(--primary-color); font-weight: 800; text-decoration: none;">Sign Up</a>
        </p>
    </div>
</div>
<?php include_once 'cursor.php'; ?>
</body>
</html>
