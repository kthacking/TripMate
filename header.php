<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TripMate</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="script.js" defer></script>
</head>
<body>
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">
                <i class="ri-compass-3-line"></i> Trip<span>Mate</span>
            </a>
            <ul class="nav-links">
                <li><a href="index.php" class="nav-link">Home</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'tripmaker'): ?>
                        <li><a href="create_trip.php" class="nav-link">Create Trip</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            <div class="auth-buttons">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <span style="margin-right: 15px; font-weight: 500; color: var(--text-color);">Hi, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <a href="logout.php" class="btn btn-outline" style="border:none;">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Log In</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <!-- Content padding for fixed navbar -->
    <div style="height: 80px;"></div>
