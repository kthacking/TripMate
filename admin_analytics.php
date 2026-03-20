<?php
require_once 'admin_header.php';

// Stats aggregation
$most_popular_trips = $conn->query("SELECT t.title, COUNT(e.id) as enrolls 
                                    FROM trips t 
                                    LEFT JOIN enrollments e ON t.id = e.trip_id AND e.status = 'approved' 
                                    GROUP BY t.id ORDER BY enrolls DESC LIMIT 5");

$most_active_tm = $conn->query("SELECT u.name, COUNT(t.id) as trip_count 
                                FROM users u 
                                JOIN trips t ON u.id = t.created_by 
                                GROUP BY u.id ORDER BY trip_count DESC LIMIT 5");

// Request success rate
$all_req = $conn->query("SELECT COUNT(*) as c FROM enrollments")->fetch_assoc()['c'] ?: 1;
$app_req = $conn->query("SELECT COUNT(*) as c FROM enrollments WHERE status='approved'")->fetch_assoc()['c'];
$success_rate = round(($app_req / $all_req) * 100, 1);

// Media trends (monthly count)
$media_trend = $conn->query("SELECT DATE_FORMAT(uploaded_at, '%Y-%m') as month, COUNT(*) as count 
                             FROM media GROUP BY month ORDER BY month DESC LIMIT 6");

// Trips by type
$type_stats = $conn->query("SELECT trip_type, COUNT(*) as c FROM trips GROUP BY trip_type");
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
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; overflow: hidden;
        background: radial-gradient(circle at 10% 20%, rgba(243, 232, 255, 0.5) 0%, transparent 40%),
                    radial-gradient(circle at 90% 80%, rgba(220, 252, 231, 0.5) 0%, transparent 40%),
                    radial-gradient(circle at 50% 50%, rgba(255, 237, 213, 0.4) 0%, transparent 60%);
    }

    .geo-bg::before {
        content: ''; position: absolute; width: 100%; height: 100%;
        background-image: linear-gradient(rgba(0,0,0,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0,0,0,0.03) 1px, transparent 1px);
        background-size: 50px 50px;
    }

    .dashboard-container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }

    .header-section { margin-bottom: 40px; display: flex; justify-content: space-between; align-items: flex-end; }
    .header-text h1 { font-size: 2.8rem; font-weight: 900; letter-spacing: -1.5px; color: #1e3b37ff; margin: 0; }
    .header-text p { font-size: 1.1rem; color: #64748B; margin: 5px 0 0 0; font-weight: 500; }
    
    .live-badge {
        background: #DCFCE7; color: #16A34A; padding: 6px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 800;
        display: inline-flex; align-items: center; gap: 8px; text-transform: uppercase; margin-bottom: 12px;
    }
    .live-badge::before { content: ''; width: 8px; height: 8px; background: #16A34A; border-radius: 50%; display: inline-block; animation: pulse 2s infinite; }
    @keyframes pulse { 0% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.5); opacity: 0.5; } 100% { transform: scale(1); opacity: 1; } }

    .analytics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 24px; }

    .analytics-card {
        background: white; border-radius: 24px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.04);
        border: 1px solid rgba(255, 255, 255, 0.5); transition: all 0.4s ease;
        position: relative; overflow: hidden;
    }
    .analytics-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.08); }
    .analytics-card h3 { font-size: 1.4rem; font-weight: 850; color: #1E293B; margin: 0 0 30px 0; letter-spacing: -0.5px; }

    /* Progress Styles */
    .progress-item { margin-bottom: 25px; }
    .progress-info { display: flex; justify-content: space-between; margin-bottom: 10px; font-weight: 700; color: #475569; font-size: 0.95rem; }
    .progress-bar-bg { height: 12px; background: #F1F5F9; border-radius: 20px; overflow: hidden; }
    .progress-bar-fill { height: 100%; border-radius: 20px; transition: width 1.5s cubic-bezier(0.1, 0, 0.2, 1); }

    /* TM Card Styles */
    .tm-item {
        display: flex; align-items: center; justify-content: space-between; padding: 18px; border-radius: 20px;
        background: #F8FAFC; border: 1px solid #F1F5F9; margin-bottom: 15px; transition: all 0.3s ease;
    }
    .tm-item:hover { background: white; border-color: #E2E8F0; transform: scale(1.02); }
    .tm-info { display: flex; align-items: center; gap: 12px; }
    .tm-avatar {
        width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #6366F1, #5cf6d0ff); color: white; font-weight: 800; font-size: 1rem;
    }
    .tm-name { font-weight: 700; color: #1E293B; }
    .tm-badge { background: linear-gradient(135deg, #c084fc, #a78bfa); color: white; padding: 5px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 800; }

    /* Gradient Variants */
    .grad-purple { background: linear-gradient(135deg, #c084fc, #a78bfa); }
    .grad-green { background: linear-gradient(135deg, #86efac, #4ade80); }
    .grad-orange { background: linear-gradient(135deg, #fdba74, #fb923c); }

    /* Success Rate Ring */
    .success-container { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px 0; }
    .percentage-ring { position: relative; width: 160px; height: 160px; }
    .percentage-ring span { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 2.2rem; font-weight: 900; color: #1E293B; }
    
    .rate-desc { font-size: 0.9rem; color: #64748B; margin-top: 20px; text-align: center; line-height: 1.5; font-weight: 500; }

    /* Chart Area */
    .chart-container { height: 260px; display: flex; align-items: flex-end; justify-content: space-around; padding: 40px 10px 10px 10px; border-bottom: 2px solid #F1F5F9; }
    .chart-bar-wrap { display: flex; flex-direction: column; align-items: center; width: 12%; height: 100%; justify-content: flex-end; }
    .chart-bar { width: 100%; border-radius: 10px 10px 0 0; position: relative; min-height: 5px; transition: height 1s ease; }
    .chart-label { margin-top: 15px; font-size: 0.75rem; font-weight: 700; color: #94A3B8; text-transform: uppercase; }
    .bar-tooltip { position: absolute; top: -30px; left: 0; right: 0; text-align: center; font-size: 0.85rem; font-weight: 900; color: #4338CA; }

    @media (max-width: 1024px) {
        .analytics-grid { grid-template-columns: 1fr; }
        .wide-card { grid-column: span 1 !important; }
    }

    @media (max-width: 768px) {
        .header-section { flex-direction: column; align-items: flex-start; gap: 20px; }
    }
    
    /* Extra Card Visuals */
    .analytics-card::after {
        content: ''; position: absolute; width: 150px; height: 150px; right: -30px; bottom: -30px;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.05) 0%, transparent 70%); border-radius: 50%; z-index: 0;
    }
</style>

<div class="geo-bg"></div>

<div class="dashboard-container">
    <div class="header-section">
        <div class="header-text">
            <span class="live-badge">Live Monitoring</span>
            <h1>Global Platform Analytics</h1>
            <p>Monitor platform performance and insights</p>
        </div>
    </div>

    <div class="analytics-grid">

        <!-- Most Popular Trips -->
        <div class="analytics-card">
            <h3>Most Popular Trips</h3>
            <div class="progress-list">
                <?php while ($t = $most_popular_trips->fetch_assoc()): ?>
                    <div class="progress-item">
                        <div class="progress-info">
                            <span><?php echo htmlspecialchars($t['title']); ?></span>
                            <span><?php echo $t['enrolls']; ?> Explorers</span>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill grad-purple" style="width: <?php echo min(100, $t['enrolls'] * 10); ?>%;"></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Top TripMakers -->
        <div class="analytics-card">
            <h3>Top TripMakers</h3>
            <div class="tm-list">
                <?php while ($tm = $most_active_tm->fetch_assoc()): ?>
                    <div class="tm-item">
                        <div class="tm-info">
                            <div class="tm-avatar"><?php echo strtoupper(substr($tm['name'], 0, 1)); ?></div>
                            <span class="tm-name"><?php echo htmlspecialchars($tm['name']); ?></span>
                        </div>
                        <span class="tm-badge"><?php echo $tm['trip_count']; ?> Trips</span>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Approval Rate -->
        <div class="analytics-card">
            <h3>Approval Rate</h3>
            <div class="success-container">
                <div class="percentage-ring">
                    <svg viewBox="0 0 36 36" style="width: 100%; height: 100%;">
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none"
                            stroke="#F1F5F9" stroke-width="3" />
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none"
                            stroke="url(#grad-green-svg)" stroke-width="3" stroke-linecap="round" stroke-dasharray="<?php echo $success_rate; ?>, 100" />
                        <defs>
                            <linearGradient id="grad-green-svg" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#86efac;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#4ade80;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                    </svg>
                    <span><?php echo $success_rate; ?>%</span>
                </div>
                <p class="rate-desc">Percentage of join requests that are approved system-wide.</p>
            </div>
        </div>

        <!-- Media Upload Trends -->
        <div class="analytics-card wide-card" style="grid-column: span 2;">
            <h3>Media Upload Trends</h3>
            <div class="chart-container">
                <?php while ($row = $media_trend->fetch_assoc()): ?>
                    <div class="chart-bar-wrap">
                        <div class="chart-bar grad-orange" style="height: <?php echo min(100, $row['count'] * 10); ?>%;">
                            <div class="bar-tooltip"><?php echo $row['count']; ?></div>
                        </div>
                        <div class="chart-label"><?php echo $row['month']; ?></div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once 'admin_footer.php'; ?>