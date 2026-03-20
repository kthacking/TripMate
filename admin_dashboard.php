<?php
require_once 'admin_header.php';

// Fetch Stats (Keep as requested, though focus is on nav cards)
$total_users = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$admin_count = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='admin'")->fetch_assoc()['c'];
$tm_count = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='tripmaker'")->fetch_assoc()['c'];
$st_count = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student'")->fetch_assoc()['c'];

$active_trips = $conn->query("SELECT COUNT(*) as c FROM trips WHERE status='active'")->fetch_assoc()['c'];
$completed_trips = $conn->query("SELECT COUNT(*) as c FROM trips WHERE status='completed'")->fetch_assoc()['c'];
$total_trips = $conn->query("SELECT COUNT(*) as c FROM trips")->fetch_assoc()['c'];

$pending_reqs = $conn->query("SELECT COUNT(*) as c FROM enrollments WHERE status='pending'")->fetch_assoc()['c'];
$total_media = $conn->query("SELECT COUNT(*) as c FROM media")->fetch_assoc()['c'];
?>

<style>
    /* Hide Default Navbar and Header */
    .admin-nav, .admin-content > h2, .admin-content > div:first-child {
        display: none !important;
    }

    body {
        background-color: #F8F9FA;
        position: relative;
        overflow-x: hidden;
    }

    /* Geometric Background */
    .geo-bg {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
        overflow: hidden;
        background: radial-gradient(circle at 10% 20%, rgba(243, 232, 255, 0.5) 0%, transparent 40%),
                    radial-gradient(circle at 90% 80%, rgba(220, 252, 231, 0.5) 0%, transparent 40%),
                    radial-gradient(circle at 50% 50%, rgba(255, 237, 213, 0.4) 0%, transparent 60%);
    }

    .geo-bg::before {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        background-image: 
            linear-gradient(rgba(0,0,0,0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0,0,0,0.03) 1px, transparent 1px);
        background-size: 50px 50px;
    }

    .dashboard-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    .welcome-section {
        margin-bottom: 50px;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
    }

    .welcome-text h1 {
        font-size: 2.8rem;
        font-weight: 900;
        letter-spacing: -1.5px;
        color: #1E293B;
        margin: 0 0 10px 0;
    }

    .welcome-text p {
        font-size: 1.1rem;
        color: #64748B;
        margin: 0;
        font-weight: 500;
    }

    .logout-box-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        background: white;
        padding: 12px 24px;
        border-radius: 12px;
        text-decoration: none;
        color: #EF4444;
        font-weight: 700;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: 1px solid #F1F5F9;
        transition: all 0.3s ease;
    }

    .logout-box-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        background: #FEF2F2;
    }

    /* Card Grid */
    .nav-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        margin-bottom: 60px;
    }

    .nav-card {
        border-radius: 24px;
        padding: 40px;
        text-decoration: none;
        position: relative;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 280px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.5);
    }

    .nav-card::before {
        content: '';
        position: absolute;
        width: 200px;
        height: 200px;
        right: -50px;
        top: -50px;
        border-radius: 50%;
        filter: blur(40px);
        opacity: 0.5;
        transition: all 0.4s ease;
        z-index: 0;
    }

    .nav-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
    }

    .nav-card:hover::before {
        transform: scale(1.2);
        opacity: 0.8;
    }

    .card-badge {
        font-size: 0.75rem;
        font-weight: 800;
        padding: 6px 14px;
        border-radius: 20px;
        background: white;
        display: inline-block;
        margin-bottom: 25px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        width: fit-content;
        position: relative;
        z-index: 1;
    }

    .card-content {
        position: relative;
        z-index: 1;
    }

    .card-content h3 {
        font-size: 1.8rem;
        font-weight: 850;
        color: #1E293B;
        margin: 0 0 12px 0;
        letter-spacing: -0.5px;
    }

    .card-content p {
        font-size: 1rem;
        color: #475569;
        line-height: 1.6;
        margin: 0;
        font-weight: 500;
        opacity: 0.8;
    }

    .card-action {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 30px;
        font-weight: 700;
        font-size: 1rem;
        color: #1E293B;
        position: relative;
        z-index: 1;
    }

    .card-action i {
        transition: transform 0.3s ease;
    }

    .nav-card:hover .card-action i {
        transform: translateX(5px);
    }

    /* Individual Card Styles */
    .card-users { background: #F3E8FF; } .card-users::before { background: #C084FC; } .card-users span { color: #9333EA; }
    .card-trips { background: #DCFCE7; } .card-trips::before { background: #4ADE80; } .card-trips span { color: #16A34A; }
    .card-requests { background: #FFEDD5; } .card-requests::before { background: #FB923C; } .card-requests span { color: #EA580C; }
    .card-media { background: #F1F5F9; } .card-media::before { background: #94A3B8; } .card-media span { color: #475569; }
    .card-analytics { background: #FEF3C7; } .card-analytics::before { background: #FBBF24; } .card-analytics span { color: #D97706; }
    .card-logs { background: #FFE4E6; } .card-logs::before { background: #FB7185; } .card-logs span { color: #E11D48; }
    .card-settings { background: #ECFEFF; } .card-settings::before { background: #22D3EE; } .card-settings span { color: #0891B2; }

    /* Compact Stats Redesign */
    .compact-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 24px;
        margin-bottom: 40px;
    }

    .mini-stat {
        border-radius: 20px;
        padding: 24px;
        color: white;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        position: relative;
        overflow: hidden;
    }

    .mini-stat:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 30px rgba(0, 0, 0, 0.1);
    }

    .stat-content {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .stat-visual {
        position: relative;
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .stat-visual i {
        font-size: 1.4rem;
        z-index: 1;
        opacity: 0.9;
    }

    .stat-circular-bg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        transform: rotate(-90deg);
    }

    .stat-circular-bg circle {
        fill: none;
        stroke: rgba(255, 255, 255, 0.2);
        stroke-width: 3.5;
    }

    .stat-circular-progress {
        fill: none;
        stroke: white;
        stroke-width: 3.5;
        stroke-linecap: round;
        stroke-dasharray: 100;
        transition: stroke-dashoffset 1.5s ease-out;
    }

    .stat-text label {
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        opacity: 0.8;
        margin-bottom: 2px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-text .value {
        font-size: 1.8rem;
        font-weight: 900;
        letter-spacing: -1px;
        line-height: 1.2;
    }

    .stat-text .trend {
        font-size: 0.7rem;
        font-weight: 700;
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 4px;
        opacity: 0.85;
    }

    .stat-action {
        width: 38px;
        height: 38px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        flex-shrink: 0;
    }

    .mini-stat:hover .stat-action {
        background: white;
        color: #1E293B;
    }

    /* Gradient Variants */
    .stat-users { background: linear-gradient(135deg, #6366F1, #8B5CF6); }
    .stat-trips { background: linear-gradient(135deg, #0EA5E9, #3B82F6); }
    .stat-requests { background: linear-gradient(135deg, #10B981, #059669); }
    .stat-media { background: linear-gradient(135deg, #F59E0B, #EA580C); }

    @media (max-width: 1200px) {
        .compact-stats { grid-template-columns: repeat(2, 1fr); }
    }

    /* Fix for 100% zoom and overflow */
    .admin-content {
        padding: 0 !important;
        max-width: none !important;
    }
    
    .admin-main {
        overflow-x: hidden;
    }
</style>

<div class="geo-bg"></div>

<div class="dashboard-container">
    
    <div class="welcome-section">
        <div class="welcome-text">
            <h1>Command Center</h1>
            <p>Welcome back, <?php echo explode(' ', $_SESSION['name'])[0]; ?>. Everything is looking good today.</p>
        </div>
        <a href="logout.php" class="logout-box-btn">
            <i class="ri-logout-box-r-line"></i> Secure Logout
        </a>
    </div>
    <!-- Compact Stats Grid -->
    <div class="compact-stats">
        <!-- Users Card -->
        <div class="mini-stat stat-users">
            <div class="stat-content">
                <div class="stat-visual">
                    <svg class="stat-circular-bg" viewBox="0 0 36 36">
                        <circle cx="18" cy="18" r="16"></circle>
                        <circle class="stat-circular-progress" cx="18" cy="18" r="16" style="stroke-dashoffset: 35;"></circle>
                    </svg>
                    <i class="ri-group-line"></i>
                </div>
                <div class="stat-text">
                    <label>Platform Users</label>
                    <div class="value"><?php echo $total_users; ?></div>
                    <div class="trend"><i class="ri-arrow-right-up-line"></i> +12% growth</div>
                </div>
            </div>
            <div class="stat-action"><i class="ri-user-add-line"></i></div>
        </div>

        <!-- Expeditions Card -->
        <div class="mini-stat stat-trips">
            <div class="stat-content">
                <div class="stat-visual">
                    <svg class="stat-circular-bg" viewBox="0 0 36 36">
                        <circle cx="18" cy="18" r="16"></circle>
                        <circle class="stat-circular-progress" cx="18" cy="18" r="16" style="stroke-dashoffset: 15;"></circle>
                    </svg>
                    <i class="ri-earth-line"></i>
                </div>
                <div class="stat-text">
                    <label>Active Trips</label>
                    <div class="value"><?php echo $active_trips; ?></div>
                    <div class="trend"><i class="ri-increase-decrease-line"></i> Live Now</div>
                </div>
            </div>
            <div class="stat-action"><i class="ri-road-map-line"></i></div>
        </div>

        <!-- Pending Card -->
        <div class="mini-stat stat-requests">
            <div class="stat-content">
                <div class="stat-visual">
                    <svg class="stat-circular-bg" viewBox="0 0 36 36">
                        <circle cx="18" cy="18" r="16"></circle>
                        <circle class="stat-circular-progress" cx="18" cy="18" r="16" style="stroke-dashoffset: 55;"></circle>
                    </svg>
                    <i class="ri-mail-star-line"></i>
                </div>
                <div class="stat-text">
                    <label>Pending Reviews</label>
                    <div class="value"><?php echo $pending_reqs; ?></div>
                    <div class="trend"><i class="ri-time-line"></i> Urgent Action</div>
                </div>
            </div>
            <div class="stat-action"><i class="ri-checkbox-multiple-line"></i></div>
        </div>

        <!-- Media Card -->
        <div class="mini-stat stat-media">
            <div class="stat-content">
                <div class="stat-visual">
                    <svg class="stat-circular-bg" viewBox="0 0 36 36">
                        <circle cx="18" cy="18" r="16"></circle>
                        <circle class="stat-circular-progress" cx="18" cy="18" r="16" style="stroke-dashoffset: 25;"></circle>
                    </svg>
                    <i class="ri-image-line"></i>
                </div>
                <div class="stat-text">
                    <label>Media Assets</label>
                    <div class="value"><?php echo $total_media; ?></div>
                    <div class="trend"><i class="ri-cloud-line"></i> Synced</div>
                </div>
            </div>
            <div class="stat-action"><i class="ri-gallery-upload-line"></i></div>
        </div>
    </div>

    <div class="nav-grid">
        <!-- Users -->
        <a href="admin_users.php" class="nav-card card-users">
            <div>
                <span class="card-badge">Total Users: <?php echo $total_users; ?></span>
                <div class="card-content">
                    <h3>Users</h3>
                    <p>Manage user accounts, adjust roles (Admin, TM, Student), and manage permissions.</p>
                </div>
            </div>
            <div class="card-action">Manage Directory <i class="ri-arrow-right-line"></i></div>
        </a>

        <!-- Trips -->
        <a href="admin_trips.php" class="nav-card card-trips">
            <div>
                <span class="card-badge"><?php echo $active_trips; ?> Active Trips</span>
                <div class="card-content">
                    <h3>Trips</h3>
                    <p>Create new travel experiences, oversee active itineraries, and manage bookings.</p>
                </div>
            </div>
            <div class="card-action">View Manifest <i class="ri-arrow-right-line"></i></div>
        </a>

      

        <!-- Media -->
        <a href="admin_media.php" class="nav-card card-media">
            <div>
                <span class="card-badge"><?php echo $total_media; ?> Assets</span>
                <div class="card-content">
                    <h3>Media</h3>
                    <p>Moderate user-uploaded trip photos and manage the platform's visual gallery.</p>
                </div>
            </div>
            <div class="card-action">Library <i class="ri-arrow-right-line"></i></div>
        </a>

        <!-- Analytics -->
        <a href="admin_analytics.php" class="nav-card card-analytics">
            <div>
                <span class="card-badge">LIVE INSIGHTS</span>
                <div class="card-content">
                    <h3>Analytics</h3>
                    <p>Track platform performance, user engagement metrics, and growth statistics.</p>
                </div>
            </div>
            <div class="card-action">View Reports <i class="ri-arrow-right-line"></i></div>
        </a>

        <!-- Logs -->
        <a href="admin_logs.php" class="nav-card card-logs">
            <div>
                <span class="card-badge">SYSTEM HEALTH</span>
                <div class="card-content">
                    <h3>Audit Logs</h3>
                    <p>Monitor system activities, security events, and administrative action history.</p>
                </div>
            </div>
            <div class="card-action">Monitor <i class="ri-arrow-right-line"></i></div>
        </a>
      <!-- Requests -->
        <a href="admin_requests.php" class="nav-card card-requests">
            <div>
                <span class="card-badge"><?php echo $pending_reqs; ?> Pending</span>
                <div class="card-content">
                    <h3>Requests</h3>
                    <p>Review and approve trip enrollment applications from students and participants.</p>
                </div>
            </div>
            <div class="card-action">Open Inbox <i class="ri-arrow-right-line"></i></div>
        </a>
        <!-- Settings -->
        <a href="admin_settings.php" class="nav-card card-settings">
            <div>
                <span class="card-badge">CONFIGURATION</span>
                <div class="card-content">
                    <h3>Settings</h3>
                    <p>Configure platform preferences, site maintenance mode, and global metadata.</p>
                </div>
            </div>
            <div class="card-action">Configure <i class="ri-arrow-right-line"></i></div>
        </a>
    </div>

    <!-- Stats row at bottom for continuity -->
    
</div>

<?php 
// No changes to admin_footer.php or logic
require_once 'admin_footer.php'; 
?>