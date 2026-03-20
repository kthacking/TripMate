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

<div class="container dashboard-container">
    
    <!-- DASHBOARD HEADER -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px;">
        <div>
            <h1 style="color: var(--secondary-color);">Dashboard</h1>
            <p style="color: var(--text-light);">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>.</p>
            
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
                <div style="display: inline-flex; align-items: center; gap: 8px; background: #FFFBEB; color: #744210; padding: 6px 12px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; margin-top: 8px;">
                    <i class="ri-star-fill" style="color: #F59E0B;"></i> <?php echo round($avg_r, 1); ?> / 5 (<?php echo $count_r; ?> reviews)
                </div>
            <?php endif; endif; ?>
        </div>
        
        <div style="display: flex; gap: 20px; align-items: center;">
            <?php if ($role == 'tripmaker' || $role == 'admin'): ?>
                <a href="create_trip.php" class="btn btn-primary">+ Create New Trip</a>
            <?php endif; ?>
        </div>
    </div>


    <!-- STUDENT VIEW -->
    <?php if ($role == 'student'): ?>
        
        <!-- My Trips Section -->
        <h2 style="margin-bottom: 24px; font-size: 1.5rem;">Your Adventures</h2>
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
                        <span class="overlay-status-badge" style="background: <?php echo $row['enroll_status'] == 'approved' ? 'rgba(72,187,120,0.35)' : 'rgba(237,137,54,0.35)'; ?>;">
                            <span style="width:6px;height:6px;border-radius:50%;background:<?php echo $row['enroll_status'] == 'approved' ? '#48bb78' : '#ed8936'; ?>;display:inline-block;"></span>
                            <?php echo ucfirst($row['enroll_status']); ?>
                        </span>
                    </div>
                    <div class="overlay-content">
                        <h3 class="overlay-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                        <div class="overlay-location">
                            <i class="ri-map-pin-2-fill"></i> <?php echo htmlspecialchars($row['destination']); ?>
                        </div>
                        <div class="overlay-bottom">
                            <span class="overlay-see-more">See more</span>
                            <div class="overlay-arrow">
                                <i class="ri-arrow-right-s-line"></i>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endwhile; else: ?>
                <p style="color: var(--text-light); grid-column: 1/-1;">You haven't joined any trips yet.</p>
            <?php endif; ?>
        </div>

        <!-- Explore Section -->
        <h2 style="margin-top: 60px; margin-bottom: 24px; font-size: 1.5rem;">Explore New Trips</h2>
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
                    $status_dot = '#48bb78';
                    $status_bg = 'rgba(72,187,120,0.12)';
                    $status_color = '#276749';
                    $percent = 0;
                    if($row['max_participants'] > 0) {
                        $percent = min(100, ($row['joined_count'] / $row['max_participants']) * 100);
                        if($row['joined_count'] >= $row['max_participants']) {
                            $status = 'Full';
                            $status_dot = '#e53e3e';
                            $status_bg = 'rgba(229,62,62,0.12)';
                            $status_color = '#9b2c2c';
                        }
                    }
                    if($row['registration_deadline'] && strtotime($row['registration_deadline']) < time()) {
                        $status = 'Closed';
                        $status_dot = '#a0aec0';
                        $status_bg = 'rgba(160,174,192,0.15)';
                        $status_color = '#4a5568';
                    }
            ?>
                <div class="trip-card">
                    <!-- Image with badges -->
                    <div class="trip-img-wrap">
                        <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="Trip" class="trip-image">
                        <div class="trip-img-badges">
                            <span class="trip-type-tag">
                                <i class="ri-calendar-line" style="font-size:0.65rem;"></i> <?php echo date('d M', strtotime($row['start_date'])); ?> – <?php echo date('d M', strtotime($row['end_date'])); ?>
                            </span>
                            <span class="trip-status-pill" style="background:<?php echo $status_bg; ?>; color:<?php echo $status_color; ?>;">
                                <span class="trip-status-dot" style="background:<?php echo $status_dot; ?>;"></span>
                                <?php echo $status; ?>
                            </span>
                        </div>
                        <div class="trip-price-badge">$<?php echo number_format($row['cost'], 0); ?></div>
                    </div>
                    
                    <div class="trip-content">
                        <h3 class="trip-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                        <div class="trip-meta">
                            <span><i class="ri-map-pin-2-fill"></i> <?php echo htmlspecialchars($row['destination'] ?? ''); ?></span>
                        </div>
                        
                        <!-- Participants bar -->
                        <div style="margin: 8px 0 0;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 5px;">
                                <span style="color:var(--text-light);"><i class="ri-group-line" style="font-size:0.82rem; margin-right:3px; color:var(--primary-color);"></i>Participants</span>
                                <span style="font-weight:600; color:var(--secondary-color);"><?php echo $row['joined_count']; ?> / <?php echo $row['max_participants'] > 0 ? $row['max_participants'] : '∞'; ?></span>
                            </div>
                            <div style="height:5px; background:#edf2f7; border-radius:5px; overflow:hidden;">
                                <div style="height:100%; width:<?php echo $percent; ?>%; background:linear-gradient(90deg, var(--primary-color), var(--primary-hover)); border-radius:5px; transition:width 0.5s ease;"></div>
                            </div>
                        </div>

                        <div class="trip-footer">
                            <a href="trip.php?id=<?php echo $row['id']; ?>" class="trip-cta-btn">View Details <i class="ri-arrow-right-line"></i></a>
                        </div>
                    </div>
                </div>
            <?php endwhile; else: ?>
                <p style="color: var(--text-light); grid-column: 1/-1;">No new trips found.</p>
            <?php endif; ?>
        </div>


    <?php endif; ?>

    <!-- TRIPMAKER / ADMIN VIEW -->
    <?php if ($role == 'tripmaker' || $role == 'admin'): ?>
        
        <!-- Managed Trips with Stats -->
        <h2 style="margin-bottom: 24px; font-size: 1.5rem; margin-top: 40px;">Managed Trips</h2>
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
                    $m_dot = '#48bb78'; $m_bg = 'rgba(72,187,120,0.12)'; $m_color = '#276749';
                    if ($row['status'] === 'completed') { $m_dot = '#6C63FF'; $m_bg = 'rgba(108,99,255,0.12)'; $m_color = '#4c46b6'; }
                    if ($row['status'] === 'cancelled') { $m_dot = '#e53e3e'; $m_bg = 'rgba(229,62,62,0.12)'; $m_color = '#9b2c2c'; }
            ?>
                <div class="trip-card">
                    <div class="trip-img-wrap">
                        <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="Trip" class="trip-image">
                        <div class="trip-img-badges">
                            <span class="trip-type-tag"><i class="ri-briefcase-4-line" style="font-size:0.65rem;"></i> Managed</span>
                            <span class="trip-status-pill" style="background:<?php echo $m_bg; ?>; color:<?php echo $m_color; ?>;">
                                <span class="trip-status-dot" style="background:<?php echo $m_dot; ?>;"></span>
                                <?php echo $m_status; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="trip-content">
                        <h3 class="trip-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                        
                        <!-- Earnings Snapshot -->
                        <div style="background: linear-gradient(135deg, #f8f9ff, #f0f0ff); padding: 14px 16px; border-radius: 14px; margin: 8px 0 0; font-size: 0.88rem; border: 1px solid rgba(108,99,255,0.08);">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                <span style="color: var(--text-light); display:flex; align-items:center; gap:5px;"><i class="ri-group-line" style="color:var(--primary-color); font-size:0.9rem;"></i> Joined</span>
                                <strong style="color:var(--secondary-color);"><?php echo $row['joined_count']; ?> / <?php echo $row['max_participants'] ?: '∞'; ?></strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-light); display:flex; align-items:center; gap:5px;"><i class="ri-money-dollar-circle-line" style="color:#48bb78; font-size:0.9rem;"></i> Earnings</span>
                                <strong style="color: var(--primary-color);">$<?php echo number_format($earnings); ?></strong>
                            </div>
                        </div>

                        <div class="trip-footer" style="gap: 8px;">
                            <a href="trip.php?id=<?php echo $row['id']; ?>" class="trip-cta-outline" style="flex: 1;">Manage</a>
                            <a href="edit_trip.php?id=<?php echo $row['id']; ?>" class="trip-cta-outline" style="padding: 10px 14px;" title="Edit"><i class="ri-edit-2-line"></i></a>
                            <a href="actions.php?action=delete_trip&trip_id=<?php echo $row['id']; ?>" onclick="return confirm('Delete this trip?');" class="trip-cta-outline" style="padding: 10px 14px; color:#e53e3e; border-color:rgba(229,62,62,0.15); background:rgba(229,62,62,0.04);" title="Delete"><i class="ri-delete-bin-line"></i></a>
                        </div>
                    </div>
                </div>
            <?php endwhile; endif; ?>
        </div>

    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>
