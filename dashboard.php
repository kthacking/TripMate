<?php
require_once 'db.php';
require_once 'auth.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
checkLogin();
include 'header.php';
 
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch Notifications
$notifs = $conn->query("SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 5");
?>

<style>
/* ── Theme Definitions ── */
:root {
    --primary-color: #ea580c;
    --primary-hover: #ff5e5eff;
    --secondary-color: #1e293b;
    --text-color: #334155;
    --text-light: #64748b;
    --bg-light: #f8fafc;
    --white: #ffffff;
    --shadow-sm: 0 4px 6px rgba(0,0,0,0.05);
    --shadow-md: 0 10px 25px rgba(0,0,0,0.08);
    --radius-sm: 12px;
    --radius-md: 20px;
    --radius-lg: 30px;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.dashboard-container {
    padding: 60px 20px 100px;
    background: linear-gradient(135deg, #fffaf5 0%, #ffffff 100%);
    min-height: calc(100vh - 80px);
}

.btn-primary {
    background: var(--primary-color);
    color: var(--white);
    padding: 12px 24px;
    border-radius: 50px;
    font-weight: 800;
    font-size: 0.95rem;
    border: none;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: 0 4px 14px rgba(234, 88, 12, 0.25);
    text-decoration: none;
    display: inline-block;
}

.btn-primary:hover {
    background: var(--primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(234, 88, 12, 0.4);
    color: white;
}

.trip-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 30px;
}

/* Card Style */
.trip-card {
    background: var(--white); border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-sm); transition: var(--transition); border: 1px solid #f1f5f9; display: flex; flex-direction: column; 
}
.trip-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-md); border-color: #ffedd5; }
.trip-img-wrap { width: 100%; height: 220px; position: relative; overflow: hidden; }
.trip-image { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; }
.trip-card:hover .trip-image { transform: scale(1.05); }

.trip-img-badges { position: absolute; top: 18px; left: 18px; display: flex; gap: 8px; flex-wrap: wrap; z-index: 2; }
.trip-type-tag, .trip-status-pill { background: rgba(255,255,255,0.95); padding: 6px 14px; border-radius: 20px; font-weight: 800; font-size: 0.8rem; color: var(--primary-color); backdrop-filter: blur(4px); letter-spacing: 0.5px; text-transform: uppercase; display: flex; align-items: center; gap: 6px;}
.trip-status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }

.trip-price-badge { position: absolute; bottom: 18px; right: 18px; background: var(--secondary-color); color: white; padding: 8px 16px; border-radius: 20px; font-weight: 900; font-size: 1.1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.2); z-index: 2; }

.trip-content { padding: 25px; flex-grow: 1; display: flex; flex-direction: column; }
.trip-title { font-size: 1.4rem; font-weight: 800; margin-bottom: 8px; color: var(--secondary-color); line-height: 1.3; letter-spacing: -0.5px; }
.trip-meta { color: var(--text-light); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 6px; margin-bottom: 12px; }
.trip-meta i { color: var(--primary-color); font-size: 1.1rem; }

.trip-footer { display: flex; margin-top: auto; padding-top: 20px; align-items: center; }
.trip-cta-btn { background: var(--primary-color); color: white; padding: 12px 24px; border-radius: 50px; font-weight: 800; font-size: 0.95rem; text-decoration: none; transition: var(--transition); text-align: center; display: block; width: 100%; border: none; }
.trip-cta-btn:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 6px 18px rgba(234, 88, 12, 0.3); color: white; }

.trip-cta-outline { border: 2px solid #e2e8f0; color: var(--secondary-color); padding: 10px; border-radius: 14px; font-weight: 800; text-align: center; text-decoration: none; transition: var(--transition); display: inline-flex; align-items: center; justify-content: center; background: white;}
.trip-cta-outline:hover { border-color: var(--primary-color); color: var(--primary-color); background: #fffaf5; transform: translateY(-2px); box-shadow: var(--shadow-sm); }

/* Overlay Card Style */
.trip-card-overlay { position: relative; border-radius: var(--radius-md); overflow: hidden; height: 280px; display: block; text-decoration: none; box-shadow: var(--shadow-sm); transition: var(--transition); border: 1px solid #f1f5f9;}
.trip-card-overlay:hover { transform: translateY(-8px); box-shadow: var(--shadow-md); border-color: #ffedd5;}
.overlay-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; }
.trip-card-overlay:hover .overlay-img { transform: scale(1.08); }
.overlay-gradient { position: absolute; inset: 0; background: linear-gradient(to top, rgba(15,23,42,0.95) 0%, rgba(15,23,42,0.3) 50%, rgba(15,23,42,0) 100%); }
.overlay-content { position: absolute; bottom: 0; left: 0; right: 0; padding: 25px; color: white; z-index: 2; }
.overlay-title { font-size: 1.6rem; font-weight: 800; margin-bottom: 6px; text-shadow: 0 2px 4px rgba(0,0,0,0.3); letter-spacing: -0.5px; line-height: 1.2;}
.overlay-location { font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #cbd5e1; display: flex; align-items: center; gap: 5px; margin-bottom: 15px; }
.overlay-location i { color: var(--primary-color); font-size: 1rem;}
.overlay-bottom { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 15px; }
.overlay-see-more { font-weight: 800; font-size: 0.9rem; letter-spacing: 0.5px; }
.overlay-arrow { width: 36px; height: 36px; border-radius: 50%; background: var(--primary-color); display: flex; align-items: center; justify-content: center; color: white; transition: var(--transition); box-shadow: 0 4px 10px rgba(234, 88, 12, 0.4);}
.trip-card-overlay:hover .overlay-arrow { background: white; color: var(--primary-color); transform: translateX(5px); box-shadow: 0 4px 15px rgba(255,255,255,0.3);}
.overlay-badges { position: absolute; top: 20px; left: 20px; z-index: 2; }
.overlay-status-badge { padding: 8px 16px; border-radius: 20px; font-weight: 800; font-size: 0.85rem; backdrop-filter: blur(8px); display: inline-flex; align-items: center; gap: 8px; color: white; background: rgba(0,0,0,0.5); letter-spacing: 0.5px;}
</style>

<div class="container dashboard-container">
    
    <!-- DASHBOARD HEADER -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 50px; flex-wrap: wrap; gap: 20px;">
        <div>
            <h1 style="color: var(--secondary-color); font-size: 2.8rem; font-weight: 800; letter-spacing: -1px; margin-bottom: 5px;">Dashboard</h1>
            <p style="color: var(--text-light); font-size: 1.1rem;">Welcome back, <strong style="color: var(--primary-color);"><?php echo htmlspecialchars($_SESSION['name']); ?></strong>.</p>
            
            <?php if(($_SESSION['role'] == 'tripmaker') && isset($conn)): 
                $tm_id = $_SESSION['user_id'];
                $avg_r = 0; $count_r = 0;
                try {
                    $tm_query = $conn->query("SELECT AVG(rating) as avg_r, COUNT(*) as c FROM reviews WHERE tripmaker_id=$tm_id");
                    if ($tm_query) {
                        $tm_stats = $tm_query->fetch_assoc();
                        $avg_r = $tm_stats['avg_r'];
                        $count_r = $tm_stats['c'];
                    }
                } catch (Exception $e) { }

                if($count_r > 0):
            ?>
                <div style="display: inline-flex; align-items: center; gap: 8px; background: #fffaf5; color: var(--secondary-color); padding: 8px 16px; border-radius: 50px; font-size: 0.95rem; font-weight: 700; margin-top: 15px; border: 1px solid #ffedd5;">
                    <i class="ri-star-fill" style="color: #fbbf24; font-size: 1.1rem;"></i> 
                    <?php echo round($avg_r, 1); ?> <span style="color: var(--text-light); font-weight: 600;">/ 5 (<?php echo $count_r; ?> reviews)</span>
                </div>
            <?php endif; endif; ?>
        </div>
        
        <div style="display: flex; gap: 20px; align-items: center;">
            <?php if ($role == 'tripmaker' || $role == 'admin'): ?>
                <a href="create_trip.php" class="btn-primary"><i class="ri-add-line" style="vertical-align: middle; margin-right: 4px;"></i> Create New Trip</a>
            <?php endif; ?>
        </div>
    </div>


    <!-- STUDENT VIEW -->
    <?php if ($role == 'student'): ?>
        
        <!-- My Trips Section -->
        <h2 style="margin-bottom: 24px; font-size: 1.6rem; font-weight: 800; color: var(--secondary-color);">Your Adventures</h2>
        <div class="trip-grid" style="margin-top: 20px;">
            <?php
            $sql = "SELECT t.*, e.status as enroll_status FROM trips t 
                    JOIN enrollments e ON t.id = e.trip_id 
                    WHERE e.student_id = $user_id ORDER BY e.request_date DESC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0):
                while($row = $result->fetch_assoc()):
            ?>
                <a href="trip.php?id=<?php echo $row['id']; ?>" class="trip-card-overlay">
                    <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="Trip" class="overlay-img">
                    <div class="overlay-gradient"></div>
                    <div class="overlay-badges">
                        <span class="overlay-status-badge" style="background: <?php echo $row['enroll_status'] == 'approved' ? 'rgba(22,163,74,0.7)' : 'rgba(234,88,12,0.7)'; ?>;">
                            <span style="width:8px;height:8px;border-radius:50%;background:<?php echo $row['enroll_status'] == 'approved' ? '#4ade80' : '#fb923c'; ?>;display:inline-block; box-shadow: 0 0 8px <?php echo $row['enroll_status'] == 'approved' ? '#4ade80' : '#fb923c'; ?>;"></span>
                            <?php echo ucfirst($row['enroll_status']); ?>
                        </span>
                    </div>
                    <div class="overlay-content">
                        <h3 class="overlay-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                        <div class="overlay-location">
                            <i class="ri-map-pin-2-fill"></i> <?php echo htmlspecialchars($row['destination']); ?>
                        </div>
                        <div class="overlay-bottom">
                            <span class="overlay-see-more">View Itinerary</span>
                            <div class="overlay-arrow">
                                <i class="ri-arrow-right-s-line" style="font-size: 1.2rem;"></i>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endwhile; else: ?>
                <div style="grid-column: 1/-1; background: var(--white); border-radius: var(--radius-md); padding: 40px; text-align: center; border: 1px dashed #cbd5e1;">
                    <i class="ri-compass-3-line" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                    <p style="color: var(--text-light); font-weight: 600; font-size: 1.1rem;">You haven't joined any trips yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Explore Section -->
        <h2 style="margin-top: 70px; margin-bottom: 24px; font-size: 1.6rem; font-weight: 800; color: var(--secondary-color);">Explore New Trips</h2>
        <div class="trip-grid">
            <?php
            $sql = "SELECT t.*, 
                    (SELECT COUNT(*) FROM enrollments WHERE trip_id=t.id AND status='approved') as joined_count 
                    FROM trips t 
                    WHERE status='active' AND t.id NOT IN (SELECT trip_id FROM enrollments WHERE student_id = $user_id)
                    ORDER BY created_at DESC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0):
                while($row = $result->fetch_assoc()):
                     // Status Logic
                    $status = 'Open';
                    $status_dot = '#22c55e';
                    $status_bg = '#f0fdf4';
                    $status_color = '#16a34a';
                    $percent = 0;
                    if($row['max_participants'] > 0) {
                        $percent = min(100, ($row['joined_count'] / $row['max_participants']) * 100);
                        if($row['joined_count'] >= $row['max_participants']) {
                            $status = 'Full';
                            $status_dot = '#ef4444';
                            $status_bg = '#fef2f2';
                            $status_color = '#dc2626';
                        }
                    }
                    if($row['registration_deadline'] && strtotime($row['registration_deadline']) < time()) {
                        $status = 'Closed';
                        $status_dot = '#94a3b8';
                        $status_bg = '#f8fafc';
                        $status_color = '#64748b';
                    }
            ?>
                <div class="trip-card">
                    <!-- Image with badges -->
                    <div class="trip-img-wrap">
                        <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="Trip" class="trip-image">
                        <div class="trip-img-badges">
                            <span class="trip-type-tag">
                                <i class="ri-calendar-line" style="font-size:0.9rem;"></i> <?php echo date('d M', strtotime($row['start_date'])); ?> – <?php echo date('d M', strtotime($row['end_date'])); ?>
                            </span>
                            <span class="trip-status-pill" style="background:<?php echo $status_bg; ?>; color:<?php echo $status_color; ?>;">
                                <span class="trip-status-dot" style="background:<?php echo $status_dot; ?>;"></span>
                                <?php echo $status; ?>
                            </span>
                        </div>
                        <div class="trip-price-badge">$<?php echo number_format($row['cost'], 0); ?></div>
                    </div>
                    
                    <div class="trip-content">
                        <div class="trip-meta">
                            <span><i class="ri-map-pin-2-fill"></i> <?php echo htmlspecialchars($row['destination'] ?? ''); ?></span>
                        </div>
                        
                        <h3 class="trip-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                        
                        <!-- Participants bar -->
                        <div style="margin: 15px 0 0;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 8px;">
                                <span style="color:var(--text-light); font-weight: 700;"><i class="ri-group-fill" style="color:var(--primary-color); position:relative; top:2px;"></i> Participants</span>
                                <span style="font-weight:800; color:var(--secondary-color);"><?php echo $row['joined_count']; ?> <span style="color: var(--text-light); font-weight: 600;">/ <?php echo $row['max_participants'] > 0 ? $row['max_participants'] : '∞'; ?></span></span>
                            </div>
                            <div style="height:6px; background:#f1f5f9; border-radius:10px; overflow:hidden;">
                                <div style="height:100%; width:<?php echo $percent; ?>%; background:linear-gradient(90deg, var(--primary-color), var(--primary-hover)); border-radius:10px; transition:width 0.8s ease;"></div>
                            </div>
                        </div>

                        <div class="trip-footer">
                            <a href="trip.php?id=<?php echo $row['id']; ?>" class="trip-cta-btn">View Details <i class="ri-arrow-right-line" style="vertical-align: middle;"></i></a>
                        </div>
                    </div>
                </div>
            <?php endwhile; else: ?>
                <p style="color: var(--text-light); grid-column: 1/-1; padding: 20px;">No new trips found.</p>
            <?php endif; ?>
        </div>


    <?php endif; ?>

    <!-- TRIPMAKER / ADMIN VIEW -->
    <?php if ($role == 'tripmaker' || $role == 'admin'): ?>
        
        <!-- Managed Trips with Stats -->
        <h2 style="margin-bottom: 24px; font-size: 1.6rem; font-weight: 800; color: var(--secondary-color); margin-top: 40px;">Managed Trips</h2>
        <div class="trip-grid">
            <?php
            // Calculate logic for TripMaker
            $where = ($role == 'admin') ? "1=1" : "created_by = $user_id";
            $sql = "SELECT t.*, 
                    (SELECT COUNT(*) FROM enrollments WHERE trip_id=t.id AND status='approved') as joined_count 
                    FROM trips t WHERE $where";
            $result = $conn->query($sql);
            if ($result->num_rows > 0):
                while($row = $result->fetch_assoc()):
                    $earnings = $row['cost'] * $row['joined_count'];
                    // Managed trip status
                    $m_status = ucfirst($row['status']);
                    $m_dot = '#22c55e'; $m_bg = '#f0fdf4'; $m_color = '#16a34a';
                    if ($row['status'] === 'completed') { $m_dot = '#6366f1'; $m_bg = '#eef2ff'; $m_color = '#4f46e5'; }
                    if ($row['status'] === 'cancelled') { $m_dot = '#ef4444'; $m_bg = '#fef2f2'; $m_color = '#dc2626'; }
            ?>
                <div class="trip-card">
                    <div class="trip-img-wrap">
                        <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="Trip" class="trip-image">
                        <div class="trip-img-badges">
                            <span class="trip-type-tag"><i class="ri-briefcase-4-line" style="font-size:0.9rem;"></i> Managed</span>
                            <span class="trip-status-pill" style="background:<?php echo $m_bg; ?>; color:<?php echo $m_color; ?>;">
                                <span class="trip-status-dot" style="background:<?php echo $m_dot; ?>;"></span>
                                <?php echo $m_status; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="trip-content">
                        <h3 class="trip-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                        
                        <!-- Earnings Snapshot -->
                        <div style="background: rgba(234, 88, 12, 0.04); padding: 16px; border-radius: 16px; margin: 12px 0 0; font-size: 0.9rem; border: 1px solid rgba(234, 88, 12, 0.08);">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span style="color: var(--text-light); display:flex; align-items:center; gap:6px; font-weight: 600;"><i class="ri-group-fill" style="color:var(--text-light); font-size:1rem;"></i> Joined</span>
                                <strong style="color:var(--secondary-color); font-size: 1rem;"><?php echo $row['joined_count']; ?> / <?php echo $row['max_participants'] ?: '∞'; ?></strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-light); display:flex; align-items:center; gap:6px; font-weight: 600;"><i class="ri-money-dollar-circle-fill" style="color:var(--primary-color); font-size:1rem;"></i> Earnings</span>
                                <strong style="color: #16a34a; font-size: 1.1rem; font-weight: 800;">$<?php echo number_format($earnings); ?></strong>
                            </div>
                        </div>

                        <div class="trip-footer" style="gap: 12px; margin-top: auto; padding-top: 20px;">
                            <a href="trip.php?id=<?php echo $row['id']; ?>" class="trip-cta-outline" style="flex: 1;">Manage Details</a>
                            <a href="edit_trip.php?id=<?php echo $row['id']; ?>" class="trip-cta-outline" style="padding: 10px 14px; background: #f8fafc;" title="Edit"><i class="ri-edit-2-line" style="font-size: 1.1rem;"></i></a>
                            <a href="actions.php?action=delete_trip&trip_id=<?php echo $row['id']; ?>" onclick="return confirm('Delete this trip?');" class="trip-cta-outline" style="padding: 10px 14px; color:#ef4444; border-color:#fee2e2; background:#fef2f2;" title="Delete"><i class="ri-delete-bin-line" style="font-size: 1.1rem;"></i></a>
                        </div>
                    </div>
                </div>
            <?php endwhile; endif; ?>
        </div>

    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>
