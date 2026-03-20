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
    <title>TripMate - Explore the World Together</title>
    <link rel="stylesheet" href="style.css">
    <!-- Remixed Icon for some quick icons if needed -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">
                <i class="ri-compass-3-line"></i> Trip<span>Mate</span>
            </a>
            <ul class="nav-links">
                <li><a href="#home" class="nav-link">Home</a></li>
                <li><a href="#features" class="nav-link">Features</a></li>
                <li><a href="popular_trips.php" class="nav-link">Popular Trips</a></li>
            </ul>
            <div class="auth-buttons" style="display: flex; align-items: center; gap: 15px;">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="btn btn-primary">Dashboard</a>
                    <a href="logout.php" class="btn btn-outline" style="border:none;">Logout</a>
                <?php
else: ?>
                    <!-- Admin Login Module -->
                    <div style="position: relative;">
                        <a href="javascript:void(0)" class="nav-link" onclick="toggleAdminDropdown(event)" style="display: flex; align-items: center; gap: 6px; font-weight: 600; font-size: 0.9rem; color: var(--text-color); transition: color 0.2s;">
                            <i class="ri-shield-user-line" style="font-size: 1.1rem;"></i> Admin
                        </a>
                        <div id="adminLoginDropdown" class="admin-notif-dropdown" style="right: 0; top: 50px; width: 280px;">
                            <div class="notif-header">
                                <span>Admin Access</span>
                                <i class="ri-lock-2-line" style="color: var(--primary-color);"></i>
                            </div>
                            <div style="padding: 20px;">
                                <?php if (isset($_SESSION['admin_login_error'])): ?>
                                    <div style="background: #fff5f5; color: #c53030; padding: 10px; border-radius: 8px; font-size: 0.8rem; margin-bottom: 15px; border: 1px solid #feb2b2; display: flex; align-items: center; gap: 8px;">
                                        <i class="ri-error-warning-fill"></i>
                                        <?php
        echo $_SESSION['admin_login_error'];
        unset($_SESSION['admin_login_error']);
        $show_admin_error = true;
?>
                                    </div>
                                <?php
    endif; ?>
                                <form action="admin_login.php" method="POST">
                                    <div class="form-group" style="margin-bottom: 12px;">
                                        <input type="email" name="email" class="form-control" placeholder="Admin Email" required style="padding: 10px; font-size: 0.85rem; border-radius: 8px;">
                                    </div>
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <input type="password" name="password" class="form-control" placeholder="Admin Password" required style="padding: 10px; font-size: 0.85rem; border-radius: 8px;">
                                    </div>
                                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 10px; font-size: 0.85rem; border-radius: 8px;">Authenticate</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div style="width: 1px; height: 20px; background: #e2e8f0; margin: 0 5px;"></div>
                    <a href="login.php" class="btn btn-outline">Log In</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                <?php
endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container hero-content">
            <div class="hero-text fade-in">
                <h1>It's time to <br> travel with <span>TripMate</span></h1>
                <p>
                    Connect with expert TripMakers, join exclusive groups, and explore the world's most beautiful destinations together.
                    Simple, organized, and unforgettable.
                </p>
                <div class="hero-buttons">
                    <?php if (isset($_SESSION['user_id'])): ?>
                         <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                    <?php
else: ?>
                        <a href="register.php" class="btn btn-primary">Join Now</a>
                        <a href="popular_trips.php" class="btn btn-outline">Explore Trips</a>
                    <?php
endif; ?>
                </div>
            </div>
            <div class="hero-image fade-in" style="animation-delay: 0.2s;">
                <img src="https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Travel" class="hero-img-main">
                
                <div class="badge-float top-left">
                    <div class="badge-icon">✈️</div>
                    <div class="badge-text">
                        <span>Best Destinations</span>
                        <strong>Top Rated</strong>
                    </div>
                </div>

                <div class="badge-float bottom-right">
                    <div class="badge-icon">👥</div>
                    <div class="badge-text">
                        <span>Active Travelers</span>
                        <strong>500+ Joined</strong>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features / Steps -->
    <section class="section" id="features">
        <div class="container">
            <div class="text-center mb-4">
                <h2 style="font-size: 2.5rem; color: var(--secondary-color); margin-bottom: 16px;">How TripMate Works</h2>
                <p style="color: var(--text-light);">Simple steps to your next adventure</p>
            </div>
            
            <div class="trip-grid" style="grid-template-columns: repeat(3, 1fr);">
                <!-- Step 1 -->
                <div class="trip-card" style="text-align: center; padding: 40px 30px;">
                    <div style="width: 80px; height: 80px; border-radius: 22px; background: linear-gradient(135deg, rgba(108,99,255,0.1), rgba(108,99,255,0.05)); display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; transition: all 0.3s ease;">
                        <i class="ri-user-search-line" style="font-size: 2.2rem; color: var(--primary-color);"></i>
                    </div>
                    <h3 class="trip-title" style="margin-bottom: 12px;">Find a Trip</h3>
                    <p style="color: var(--text-light); font-size: 0.92rem; line-height: 1.6;">Browse organized trips by ID or destination created by verified TripMakers.</p>
                </div>
                <!-- Step 2 -->
                <div class="trip-card" style="text-align: center; padding: 40px 30px;">
                    <div style="width: 80px; height: 80px; border-radius: 22px; background: linear-gradient(135deg, rgba(245,0,87,0.1), rgba(245,0,87,0.05)); display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; transition: all 0.3s ease;">
                        <i class="ri-user-add-line" style="font-size: 2.2rem; color: var(--accent-color);"></i>
                    </div>
                    <h3 class="trip-title" style="margin-bottom: 12px;">Request to Join</h3>
                    <p style="color: var(--text-light); font-size: 0.92rem; line-height: 1.6;">Send a request. Once approved, you get full access to the itinerary and group.</p>
                </div>
                <!-- Step 3 -->
                <div class="trip-card" style="text-align: center; padding: 40px 30px;">
                    <div style="width: 80px; height: 80px; border-radius: 22px; background: linear-gradient(135deg, rgba(72,187,120,0.1), rgba(72,187,120,0.05)); display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; transition: all 0.3s ease;">
                        <i class="ri-chat-smile-2-line" style="font-size: 2.2rem; color: #48bb78;"></i>
                    </div>
                    <h3 class="trip-title" style="margin-bottom: 12px;">Collaborate</h3>
                    <p style="color: var(--text-light); font-size: 0.92rem; line-height: 1.6;">Chat with your group, share photos, and prepare for the journey together.</p>
                </div>
            </div>
        </div>
    </section>

    <footer style="background: var(--white); padding: 40px 0; border-top: 1px solid #edf2f7; margin-top: 60px;">
        <div class="container text-center">
            <p style="color: var(--text-light);">&copy; <?php echo date('Y'); ?> TripMate. All rights reserved.</p>
        </div>
    </footer>

    <script>
    function toggleAdminDropdown(event) {
        event.stopPropagation();
        const dd = document.getElementById('adminLoginDropdown');
        dd.classList.toggle('show');
    }

    // Auto-open if error exists
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($show_admin_error))
    echo "document.getElementById('adminLoginDropdown').classList.add('show');"; ?>
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const adminDd = document.getElementById('adminLoginDropdown');
        if (adminDd && adminDd.classList.contains('show')) {
            adminDd.classList.remove('show');
        }
    });
    
    // Prevent closing when clicking inside dropdown
    document.getElementById('adminLoginDropdown')?.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    </script>
</body>
</html>
