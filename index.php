<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// Dynamic Data Fetching
$featured_trips = $conn->query("SELECT * FROM trips WHERE status='active' ORDER BY created_at DESC LIMIT 15");
$destinations = $conn->query("SELECT destination, MIN(image_url) as image FROM trips WHERE status='active' AND image_url IS NOT NULL AND image_url != '' GROUP BY destination LIMIT 23");
$media_gallery = $conn->query("SELECT file_path FROM media WHERE type='image' ORDER BY uploaded_at DESC LIMIT 6");
$reviews = $conn->query("SELECT r.*, u.name as user_name FROM reviews r JOIN users u ON r.student_id = u.id ORDER BY r.created_at DESC LIMIT 3");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TripMate - Explore the World Together</title>
    <!-- Remixed Icon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        
        :root {
            --primary-color: #ea580c; /* Warm Orange */
            --primary-hover: #c2410c;
            --secondary-color: #1e293b;
            --text-color: #334155;
            --text-light: #64748b;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --radius-sm: 12px;
            --radius-md: 20px;
            --radius-lg: 30px;
            --shadow-sm: 0 4px 6px rgba(0,0,0,0.05);
            --shadow-md: 0 10px 25px rgba(0,0,0,0.08);
            --shadow-lg: 0 20px 40px rgba(234, 88, 12, 0.15);
            --font-family: 'Plus Jakarta Sans', sans-serif;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { font-family: var(--font-family); color: var(--text-color); background: var(--white); line-height: 1.6; }

        .container { max-width: 1280px; margin: 0 auto; padding: 0 20px; }
        .section { padding: 80px 0; }
        .text-center { text-align: center; }
        
        /* Typography */
        h1, h2, h3, h4 { color: var(--secondary-color); font-weight: 800; line-height: 1.2; }
        .section-title { font-size: 2.5rem; margin-bottom: 10px; }
        .section-subtitle { font-size: 1.1rem; color: var(--text-light); margin-bottom: 50px; }

        /* Buttons */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 14px 28px; border-radius: 50px; font-weight: 700; font-size: 1rem; text-decoration: none; transition: var(--transition); border: none; cursor: pointer; }
        .btn-primary { background: var(--primary-color); color: var(--white); box-shadow: var(--shadow-sm); }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-3px); box-shadow: var(--shadow-lg); }
        .btn-outline { background: transparent; color: var(--secondary-color); border: 2px solid #e2e8f0; }
        .btn-outline:hover { border-color: var(--secondary-color); background: var(--bg-light); }

        /* Navigation */
        .navbar { position: fixed; top: 0; left: 0; width: 100%; padding: 20px 0; z-index: 1000; background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(0,0,0,0.05); transition: var(--transition); }
        .nav-container { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: 800; color: var(--secondary-color); text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .logo span { color: var(--primary-color); }
        .nav-links { display: flex; gap: 30px; list-style: none; }
        .nav-link { color: var(--text-color); text-decoration: none; font-weight: 600; font-size: 1rem; transition: var(--transition); }
        .nav-link:hover { color: var(--primary-color); }
        
        /* Admin Login Dropdown */
        .admin-notif-dropdown { position: absolute; background: var(--white); border-radius: var(--radius-sm); box-shadow: var(--shadow-md); opacity: 0; visibility: hidden; transform: translateY(10px); transition: var(--transition); border: 1px solid #e2e8f0; }
        .admin-notif-dropdown.show { opacity: 1; visibility: visible; transform: translateY(0); }
        .notif-header { padding: 15px 20px; border-bottom: 1px solid #e2e8f0; font-weight: 700; display: flex; justify-content: space-between; }

        /* Hero Section */
        .hero { padding: 140px 0 80px; background: linear-gradient(135deg, #fffaf5 0%, #ffffff 100%); overflow: hidden; }
        .hero-container { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; }
        .hero-text h1 { font-size: 4rem; letter-spacing: -1.5px; margin-bottom: 20px; }
        .hero-text h1 span { color: var(--primary-color); position: relative; }
        .hero-text p { font-size: 1.2rem; color: var(--text-light); margin-bottom: 40px; }
        
        .hero-visual { position: relative; }
        .hero-img-wrap { width: 100%; aspect-ratio: 4/5; border-radius: var(--radius-lg) var(--radius-lg) var(--radius-lg) 0; overflow: hidden; box-shadow: var(--shadow-md); }
        .hero-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
        
        .hero-float-card { position: absolute; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); padding: 15px 20px; border-radius: var(--radius-sm); box-shadow: var(--shadow-md); display: flex; align-items: center; gap: 12px; }
        .float-top { top: 40px; left: -30px; animation: float 4s ease-in-out infinite; }
        .float-bottom { bottom: 40px; right: -30px; animation: float 5s ease-in-out infinite reverse; }
        .float-icon { width: 40px; height: 40px; background: #fff7ed; color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        
        @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-10px); } 100% { transform: translateY(0px); } }

        /* Featured Trips Grid */
        .trip-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; }
        .trip-card { background: var(--white); border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-sm); transition: var(--transition); border: 1px solid #f1f5f9; display: flex; flex-direction: column; text-decoration: none; color: inherit; }
        .trip-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-md); }
        .trip-img { width: 100%; height: 220px; overflow: hidden; position: relative; }
        .trip-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .trip-card:hover .trip-img img { transform: scale(1.05); }
        .trip-badge { position: absolute; top: 15px; left: 15px; background: rgba(255,255,255,0.9); padding: 5px 12px; border-radius: 20px; font-weight: 700; font-size: 0.8rem; color: var(--primary-color); }
        .trip-content { padding: 25px; flex-grow: 1; display: flex; flex-direction: column; }
        .trip-loc { color: var(--text-light); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; display: flex; align-items: center; gap: 5px; }
        .trip-title { font-size: 1.3rem; margin-bottom: 15px; }
        .trip-footer { margin-top: auto; display: flex; justify-content: space-between; align-items: flex-end; padding-top: 15px; border-top: 1px solid #f1f5f9; }
        .trip-price { font-size: 1.4rem; font-weight: 800; color: var(--primary-color); }
        .trip-price span { font-size: 0.85rem; color: var(--text-light); font-weight: 500; }

        /* Destinations Matrix */
        .dest-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .dest-item { position: relative; height: 350px; border-radius: var(--radius-md); overflow: hidden; cursor: pointer; }
        .dest-item:nth-child(2) { grid-column: span 2; }
        .dest-item img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s; }
        .dest-item { box-shadow: inset 0 -80px 50px -20px rgba(0,0,0,0.6); }
        .dest-item:hover img { transform: scale(1.1); }
        .dest-info { position: absolute; bottom: 25px; left: 25px; color: var(--white); z-index: 2; }
        .dest-info h3 { color: var(--white); font-size: 1.5rem; }

        /* Why Choose Us */
        .feature-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 30px; }
        .feature-card { padding: 35px 25px; background: #fffaf5; border-radius: var(--radius-md); text-align: center; transition: var(--transition); border: 1px solid transparent; }
        .feature-card:hover { border-color: #fed7aa; background: var(--white); box-shadow: var(--shadow-md); transform: translateY(-5px); }
        .feat-icon { width: 60px; height: 60px; background: var(--white); color: var(--primary-color); border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 20px; box-shadow: var(--shadow-sm); }
        .feature-card h3 { font-size: 1.1rem; margin-bottom: 10px; }
        .feature-card p { font-size: 0.9rem; color: var(--text-light); }

        /* Media Gallery */
        .gallery-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        .gallery-img { aspect-ratio: 1; border-radius: var(--radius-sm); overflow: hidden; }
        .gallery-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s; }
        .gallery-img:hover img { transform: scale(1.05); }

        /* Reviews */
        .review-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; }
        .review-card { background: var(--white); padding: 30px; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border: 1px solid #f1f5f9; }
        .stars { color: #fbbf24; margin-bottom: 15px; font-size: 1.1rem; }
        .review-text { font-size: 1rem; font-style: italic; color: var(--text-color); margin-bottom: 20px; }
        .review-author { display: flex; align-items: center; gap: 15px; }
        .author-av { width: 45px; height: 45px; border-radius: 50%; background: #fed7aa; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--primary-color); }
        
        /* CTA Section */
        .cta-banner { background: var(--secondary-color); border-radius: var(--radius-lg); padding: 80px 40px; text-align: center; color: var(--white); position: relative; overflow: hidden; }
        .cta-banner::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at 80% 20%, rgba(234,88,12,0.2) 0%, transparent 50%); }
        .cta-banner h2 { color: var(--white); font-size: 3rem; margin-bottom: 20px; z-index: 1; position: relative; }
        .cta-banner p { font-size: 1.2rem; opacity: 0.8; margin-bottom: 30px; z-index: 1; position: relative; }

        /* Footer */
        .footer { background: #f8fafc; padding: 60px 0 30px; border-top: 1px solid #e2e8f0; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; margin-bottom: 40px; }
        .footer-col h4 { font-size: 1.1rem; margin-bottom: 20px; }
        .footer-links { list-style: none; }
        .footer-links li { margin-bottom: 12px; }
        .footer-links a { color: var(--text-light); text-decoration: none; transition: var(--transition); font-weight: 500; }
        .footer-links a:hover { color: var(--primary-color); }
        .social-icons { display: flex; gap: 15px; margin-top: 20px; }
        .social-icons a { width: 36px; height: 36px; border-radius: 50%; background: var(--white); display: flex; align-items: center; justify-content: center; color: var(--text-color); box-shadow: var(--shadow-sm); transition: var(--transition); text-decoration: none; }
        .social-icons a:hover { background: var(--primary-color); color: var(--white); transform: translateY(-3px); }
        .footer-bottom { text-align: center; padding-top: 30px; border-top: 1px solid #e2e8f0; color: var(--text-light); font-size: 0.9rem; }

        @media (max-width: 1024px) {
            .hero-container { grid-template-columns: 1fr; text-align: center; }
            .hero-text h1 { font-size: 3.5rem; }
            .dest-grid { grid-template-columns: repeat(2, 1fr); }
            .dest-item:nth-child(2) { grid-column: span 1; }
            .feature-grid, .review-grid { grid-template-columns: repeat(2, 1fr); }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .hero-text h1 { font-size: 2.5rem; }
            .feature-grid, .review-grid, .gallery-grid, .dest-grid { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr; }
            .section { padding: 50px 0; }
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">
                <i class="ri-compass-3-fill"></i> Trip<span>Mate</span>
            </a>
            <ul class="nav-links">
                <li><a href="#home" class="nav-link">Home</a></li>
                <li><a href="#destinations" class="nav-link">Destinations</a></li>
                <li><a href="#trips" class="nav-link">Tours</a></li>
                <li><a href="#reviews" class="nav-link">Reviews</a></li>
            </ul>
            <div style="display: flex; align-items: center; gap: 15px;">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="btn btn-primary" style="padding: 10px 20px;">Dashboard</a>
                    <a href="logout.php" class="btn btn-outline" style="padding: 10px 20px; border:none;">Logout</a>
                <?php else: ?>
                    <!-- Admin Login Module -->
                    <div style="position: relative;">
                        <a href="javascript:void(0)" class="nav-link" onclick="toggleAdminDropdown(event)" style="display: flex; align-items: center; gap: 6px; font-weight: 700; font-size: 0.9rem;">
                            <i class="ri-shield-user-fill"></i> Admin
                        </a>
                        <div id="adminLoginDropdown" class="admin-notif-dropdown" style="right: 0; top: 40px; width: 280px; padding: 20px;">
                            <div class="notif-header" style="padding: 0 0 15px 0; margin-bottom: 15px;">
                                <span>Admin Access</span><i class="ri-lock-2-line" style="color: var(--primary-color);"></i>
                            </div>
                            <?php if (isset($_SESSION['admin_login_error'])): ?>
                                <div style="background: #fef2f2; color: #dc2626; padding: 10px; border-radius: 8px; font-size: 0.8rem; margin-bottom: 15px; border: 1px solid #fecaca; display: flex; align-items: center; gap: 8px;">
                                    <i class="ri-error-warning-fill"></i>
                                    <?php echo $_SESSION['admin_login_error']; unset($_SESSION['admin_login_error']); $show_admin_error = true; ?>
                                </div>
                            <?php endif; ?>
                            <form action="admin_login.php" method="POST">
                                <input type="email" name="email" placeholder="Admin Email" required style="width: 100%; padding: 12px; margin-bottom: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit;">
                                <input type="password" name="password" placeholder="Admin Password" required style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit;">
                                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; border-radius: 8px;">Authenticate</button>
                            </form>
                        </div>
                    </div>
                    <div style="width: 1px; height: 20px; background: #e2e8f0; margin: 0 5px;"></div>
                    <a href="login.php" class="nav-link">Log In</a>
                    <a href="register.php" class="btn btn-primary" style="padding: 10px 24px;">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container hero-container">
            <div class="hero-text">
                <h1>Travel Memories <br> You'll <span>Never Forget</span></h1>
                <p>Navigating the globe effortlessly, we transform wanderlust dreams into seamless adventures. With us, the world becomes your accessible playground.</p>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <a href="#trips" class="btn btn-primary">Find Out More <i class="ri-arrow-right-line"></i></a>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="register.php" class="btn btn-outline"><i class="ri-play-circle-line"></i> Start Journey</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-img-wrap">
                    <img src="https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?q=80&w=2021&auto=format&fit=crop" alt="Travel">
                </div>
                <!-- Floating Elements -->
                <div class="hero-float-card float-top">
                    <div class="float-icon"><i class="ri-map-pin-line"></i></div>
                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-light); font-weight: 600;">Location</div>
                        <div style="font-weight: 800; color: var(--secondary-color);">Global Access</div>
                    </div>
                </div>
                <div class="hero-float-card float-bottom">
                    <div class="float-icon"><i class="ri-star-smile-line"></i></div>
                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-light); font-weight: 600;">Satisfaction</div>
                        <div style="font-weight: 800; color: var(--secondary-color);">4.9/5 Rating</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="section">
        <div class="container">
            <div class="text-center" style="margin-bottom: 50px;">
                <h2 class="section-title">We Make World Travel Easy</h2>
                <p class="section-subtitle">Experience the difference with our premium expedition planning.</p>
            </div>
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feat-icon"><i class="ri-secure-payment-line"></i></div>
                    <h3>Secure Booking</h3>
                    <p>Verified Tripmakers and encrypted payments guarantee your trip safety.</p>
                </div>
                <div class="feature-card">
                    <div class="feat-icon"><i class="ri-money-dollar-circle-line"></i></div>
                    <h3>Best Value</h3>
                    <p>Discover trips that fit your budget with transparent pricing.</p>
                </div>
                <div class="feature-card">
                    <div class="feat-icon"><i class="ri-compass-discover-line"></i></div>
                    <h3>Expert Guides</h3>
                    <p>Navigate new places with seasoned TripMakers leading the way.</p>
                </div>
                <div class="feature-card">
                    <div class="feat-icon"><i class="ri-group-line"></i></div>
                    <h3>Community Experience</h3>
                    <p>Travel in groups, forge new friendships, and share the journey.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Destinations -->
    <section class="section" id="destinations" style="background: var(--bg-light);">
        <div class="container">
            <div class="text-center" style="margin-bottom: 50px;">
                <h2 class="section-title">Find Your Best <span style="font-weight: 400;">Destination</span></h2>
                <p class="section-subtitle">We have endless destinations you can choose from.</p>
            </div>
            <div class="dest-grid">
                <?php if ($destinations && $destinations->num_rows > 0): 
                    while ($dest = $destinations->fetch_assoc()): 
                        $fallback = 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=800&q=80';
                        $img = !empty($dest['image']) ? $dest['image'] : $faback;
                ?>
                    <div class="dest-item">
                        <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($dest['destination']); ?>" onerror="this.src='<?php echo $fallback; ?>'">
                        <div class="dest-info">
                            <h3><?php echo htmlspecialchars($dest['destination']); ?></h3>
                            <span style="font-size: 0.9rem; font-weight: 600;"><i class="ri-map-pin-2-fill text-primary" style="color:var(--primary-color);"></i> Explore</span>
                        </div>
                    </div>
                <?php endwhile; else: ?>
                    <p style="grid-column: 1/-1; text-align: center; color: var(--text-light);">Destinations populating soon...</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Popular Trips -->
    <section class="section" id="trips">
        <div class="container">
            <div class="text-center" style="margin-bottom: 50px;">
                <h2 class="section-title">Best <span style="font-weight: 400;">Vacation Plan</span></h2>
                <p class="section-subtitle">Plan your perfect vacation. Choose among hundreds of all-inclusive offers!</p>
            </div>
            
            <div class="trip-grid">
                <?php if ($featured_trips && $featured_trips->num_rows > 0): 
                    while ($trip = $featured_trips->fetch_assoc()): 
                        // Calculate Duration
                        $date1 = new DateTime($trip['start_date']);
                        $date2 = new DateTime($trip['end_date']);
                        $diff = $date1->diff($date2);
                        $duration = $diff->days . ' Days';
                        $img = !empty($trip['image_url']) ? $trip['image_url'] : 'https://images.unsplash.com/photo-1503220317375-aaad61436b1b?w=600&q=80';
                ?>
                    <a href="trip.php?id=<?php echo $trip['id']; ?>" class="trip-card">
                        <div class="trip-img">
                            <span class="trip-badge"><i class="ri-star-s-fill"></i> 4.9</span>
                            <img src="<?php echo htmlspecialchars($img); ?>" alt="Trip" onerror="this.src='https://images.unsplash.com/photo-1503220317375-aaad61436b1b?w=600&q=80'">
                        </div>
                        <div class="trip-content">
                            <div class="trip-loc"><i class="ri-map-pin-user-line" style="color:var(--primary-color);"></i> <?php echo htmlspecialchars($trip['destination']); ?></div>
                            <h3 class="trip-title"><?php echo htmlspecialchars($trip['title']); ?></h3>
                            <div class="trip-footer">
                                <div style="color: var(--text-light); font-weight: 600; font-size: 0.9rem;">
                                    <i class="ri-calendar-event-line"></i> <?php echo $duration; ?>
                                </div>
                                <div class="trip-price">
                                    $<?php echo number_format($trip['cost']); ?><span>/person</span>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endwhile; else: ?>
                     <div style="grid-column: 1/-1; text-align: center; padding: 50px; background: #f8fafc; border-radius: 20px;">
                        <i class="ri-plane-line" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 15px; display: block;"></i>
                        <h3 style="color: #64748b;">No active trips perfectly matched right now.</h3>
                        <p style="color: #94a3b8;">TripMakers are crafting new experiences. Check back soon!</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="text-center" style="margin-top: 40px;">
                <a href="popular_trips.php" class="btn btn-outline" style="padding: 12px 30px;">See All Expeditions</a>
            </div>
        </div>
    </section>

    <!-- Media Gallery -->
    <?php if ($media_gallery && $media_gallery->num_rows > 0): ?>
    <section class="section" style="background: var(--bg-light);">
        <div class="container text-center">
            <h2 class="section-title">Our <span style="font-weight: 400;">Gallery</span></h2>
            <p class="section-subtitle">An insight into incredible travel experiences around the world</p>
            <div class="gallery-grid">
                <?php while ($img = $media_gallery->fetch_assoc()): ?>
                    <div class="gallery-img">
                        <img src="<?php echo htmlspecialchars($img['file_path']); ?>" alt="Gallery Image" onerror="this.style.display='none'">
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Reviews -->
    <?php if ($reviews && $reviews->num_rows > 0): ?>
    <section class="section" id="reviews">
        <div class="container">
            <div class="text-center" style="margin-bottom: 50px;">
                <h2 class="section-title">What They Say</h2>
                <p class="section-subtitle">Testimonials from our happy travelers.</p>
            </div>
            <div class="review-grid">
                <?php while ($rev = $reviews->fetch_assoc()): ?>
                <div class="review-card">
                    <div class="stars">
                        <?php for ($i=0; $i<$rev['rating']; $i++) echo "<i class='ri-star-fill'></i>"; ?>
                    </div>
                    <p class="review-text">"<?php echo htmlspecialchars($rev['review_text'] ?? 'An absolute dream of an experience. Highly recommended!'); ?>"</p>
                    <div class="review-author">
                        <div class="author-av"><?php echo strtoupper(substr($rev['user_name'], 0, 1)); ?></div>
                        <div>
                            <h4 style="font-size: 1rem; color: var(--secondary-color);"><?php echo htmlspecialchars($rev['user_name']); ?></h4>
                            <span style="font-size: 0.8rem; color: var(--text-light); font-weight: 600;">Traveler</span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA -->
    <section class="section" style="padding-top: 0;">
        <div class="container">
            <div class="cta-banner">
                <h2>Start Your Journey Today</h2>
                <p>Join thousands of travelers exploring the world with curated trips and communities.</p>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="popular_trips.php" class="btn btn-primary" style="background: var(--white); color: var(--primary-color);">Explore Expeditions</a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-primary" style="background: var(--white); color: var(--primary-color);">Create Travel Account</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <a href="index.php" class="logo" style="margin-bottom: 20px;">
                        <i class="ri-compass-3-fill"></i> Trip<span>Mate</span>
                    </a>
                    <p style="color: var(--text-light); font-size: 0.95rem; margin-bottom: 20px;">We transform your travel dreams into reality. Secure booking, trusted guides, and unforgettable shared experiences.</p>
                    <div class="social-icons">
                        <a href="#"><i class="ri-twitter-x-line"></i></a>
                        <a href="#"><i class="ri-instagram-line"></i></a>
                        <a href="#"><i class="ri-facebook-circle-fill"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Company</h4>
                    <ul class="footer-links">
                        <li><a href="#home">About Us</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Travel Blog</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Support</h4>
                    <ul class="footer-links">
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Safety Guidelines</a></li>
                        <li><a href="#">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Legal</h4>
                    <ul class="footer-links">
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                &copy; <?php echo date('Y'); ?> TripMate Inc. All rights reserved. Designed for Adventurers.
            </div>
        </div>
    </footer>

    <script>
    // Sticky Nav effect
    window.addEventListener('scroll', () => {
        const nav = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            nav.style.boxShadow = '0 4px 20px rgba(0,0,0,0.05)';
            nav.style.padding = '15px 0';
        } else {
            nav.style.boxShadow = 'none';
            nav.style.padding = '20px 0';
        }
    });

    function toggleAdminDropdown(event) {
        event.stopPropagation();
        const dd = document.getElementById('adminLoginDropdown');
        dd.classList.toggle('show');
    }

    // Auto-open if error exists
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($show_admin_error)): ?>
            document.getElementById('adminLoginDropdown').classList.add('show');
        <?php endif; ?>
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const adminDd = document.getElementById('adminLoginDropdown');
        if (adminDd && adminDd.classList.contains('show')) {
            adminDd.classList.remove('show');
        }
    });
    
    // Prevent closing when clicking inside dropdown
    document.getElementById('adminLoginDropdown')?.addEventListener('click', function(e) { e.stopPropagation(); });
    </script>
</body>
</html>
