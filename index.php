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
                <li><a href="#trips" class="nav-link">Popular Trips</a></li>
            </ul>
            <div class="auth-buttons">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="btn btn-primary">Dashboard</a>
                    <a href="logout.php" class="btn btn-outline" style="border:none;">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Log In</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
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
                    <?php if(isset($_SESSION['user_id'])): ?>
                         <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary">Join Now</a>
                        <a href="login.php" class="btn btn-outline">Explore</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-image fade-in" style="animation-delay: 0.2s;">
                <!-- Placeholder Image or User Uploaded Image -->
                <!-- Ideally we use a high quality tourism image -->
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
                <div class="trip-card" style="text-align: center; padding: 40px;">
                    <i class="ri-user-search-line" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 24px;"></i>
                    <h3 class="trip-title">Find a Trip</h3>
                    <p style="color: var(--text-light);">Browse organized trips by ID or destination created by verified TripMakers.</p>
                </div>
                <!-- Step 2 -->
                <div class="trip-card" style="text-align: center; padding: 40px;">
                    <i class="ri-user-add-line" style="font-size: 3rem; color: var(--accent-color); margin-bottom: 24px;"></i>
                    <h3 class="trip-title">Request to Join</h3>
                    <p style="color: var(--text-light);">Send a request. Once approved, you get full access to the itinerary and group.</p>
                </div>
                <!-- Step 3 -->
                <div class="trip-card" style="text-align: center; padding: 40px;">
                    <i class="ri-chat-smile-2-line" style="font-size: 3rem; color: #48bb78; margin-bottom: 24px;"></i>
                    <h3 class="trip-title">Collaborate</h3>
                    <p style="color: var(--text-light);">Chat with your group, share photos, and prepare for the journey together.</p>
                </div>
            </div>
        </div>
    </section>

    <footer style="background: var(--white); padding: 40px 0; border-top: 1px solid #edf2f7; margin-top: 60px;">
        <div class="container text-center">
            <p style="color: var(--text-light);">&copy; <?php echo date('Y'); ?> TripMate. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>
