<?php require_once 'db.php'; ?>
<?php
$error = '';
$success = '';

// Secret key to prevent unauthorized admin registration
// In a real app, this should be in a config file or environment variable
$ADMIN_SECRET_KEY = "KIRUBALANADDADMIN";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $secret_key = $_POST['secret_key'];
    $role = 'admin';

    if ($secret_key !== $ADMIN_SECRET_KEY) {
        $error = "Invalid Secret Registration Key.";
    } else {
        // Check if email exists
        $check = $conn->query("SELECT id FROM users WHERE email='$email'");
        if ($check->num_rows > 0) {
            $error = "Email already registered.";
        } else {
            // Using prepared statement for better security
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $password, $role);

            if ($stmt->execute()) {
                $success = "Admin account created successfully! You can now log in.";
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}
?>
<?php include 'header.php'; ?>

<div class="auth-wrapper"
    style="min-height: calc(100vh - 80px); display: flex; align-items: center; justify-content: center; background: #f8fafc; padding: 40px 20px;">
    <div class="auth-card fade-in"
        style="background: white; padding: 40px; border-radius: 24px; box-shadow: var(--shadow-lg); width: 100%; max-width: 450px; text-align: center; border: 1px solid #edf2f7;">
        <div
            style="width: 60px; height: 60px; background: #fee2e2; color: #ef4444; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 1.8rem;">
            <i class="ri-shield-user-fill"></i>
        </div>
        <h2 class="auth-title"
            style="font-size: 1.8rem; font-weight: 800; color: var(--secondary-color); margin-bottom: 8px;">Admin
            Registration</h2>
        <p class="auth-subtitle" style="color: var(--text-light); margin-bottom: 30px;">Create a new administrative
            account</p>

        <?php if ($error): ?>
            <div
                style="background: #fff5f5; color: #c53030; padding: 12px; border-radius: 12px; margin-bottom: 24px; font-size: 0.9rem; border: 1px solid #fed7d7;">
                <i class="ri-error-warning-line"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div
                style="background: #f0fff4; color: #2f855a; padding: 12px; border-radius: 12px; margin-bottom: 24px; font-size: 0.9rem; border: 1px solid #c6f6d5;">
                <i class="ri-checkbox-circle-line"></i> <?php echo $success; ?>
                <div style="margin-top: 10px;">
                    <a href="login.php" class="btn btn-primary btn-sm" style="padding: 6px 16px;">Go to Login</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
            <form method="POST" action="">
                <div class="form-group" style="text-align: left; margin-bottom: 20px;">
                    <label class="form-label"
                        style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Admin Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="Root Admin"
                        style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #e2e8f0; outline: none; transition: all 0.2s;">
                </div>
                <div class="form-group" style="text-align: left; margin-bottom: 20px;">
                    <label class="form-label"
                        style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Admin Email</label>
                    <input type="email" name="email" class="form-control" required placeholder="admin@tripmate.com"
                        style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #e2e8f0; outline: none; transition: all 0.2s;">
                </div>
                <div class="form-group" style="text-align: left; margin-bottom: 20px;">
                    <label class="form-label"
                        style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••"
                        style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #e2e8f0; outline: none; transition: all 0.2s;">
                </div>
                <div class="form-group" style="text-align: left; margin-bottom: 30px;">
                    <label class="form-label"
                        style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; color: #ef4444;">Secret
                        Registration Key</label>
                    <input type="password" name="secret_key" class="form-control" required
                        placeholder="Required for Admin registration"
                        style="width: 100%; padding: 12px 16px; border-radius: 12px; border: 2px solid #fee2e2; outline: none; transition: all 0.2s;">
                </div>
                <button type="submit" class="btn btn-primary"
                    style="width: 100%; padding: 14px; border-radius: 12px; font-weight: 700; font-size: 1rem; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);">Register
                    Administrator</button>
            </form>
        <?php endif; ?>

        <p class="mt-4" style="margin-top: 24px; font-size: 0.9rem; color: var(--text-light);">
            Already have an account? <a href="login.php"
                style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Log In</a>
        </p>
    </div>
</div>

<?php include 'footer.php'; ?>