<?php
require_once 'db.php';
require_once 'auth.php';
session_start();
checkLogin();
include 'header.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch Notifications
$notifs = $conn->query("SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 5");
?>

<div class="container dashboard-container">
    
    <!-- DASHBOARD HEADER & NOTIFICATIONS -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px;">
        <div>
            <h1 style="color: var(--secondary-color);">Dashboard</h1>
            <p style="color: var(--text-light);">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>.</p>
        </div>
        
        <div style="display: flex; gap: 20px; align-items: center;">
            <!-- Notifications Dropdown (Simplified as a list for now) -->
            <div style="position: relative;">
                <div style="background: white; padding: 10px; border-radius: 50%; box-shadow: var(--shadow-sm); cursor: pointer;" onclick="document.getElementById('notif-box').classList.toggle('hidden');">
                    <i class="ri-notification-3-line" style="font-size: 1.2rem;"></i>
                    <?php 
                    $unread = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE user_id=$user_id AND is_read=0")->fetch_assoc()['c'];
                    if($unread > 0) echo '<div class="notification-dot"></div>';
                    ?>
                </div>

                <div id="notif-box" class="hidden" style="position: absolute; right: 0; top: 50px; width: 300px; background: white; border-radius: 12px; box-shadow: var(--shadow-lg); z-index: 10; border: 1px solid #edf2f7; display: none;">
                    <div style="padding: 12px; border-bottom: 1px solid #edf2f7; font-weight: bold;">Notifications</div>
                    <div class="notification-list">
                        <?php if($notifs->num_rows > 0): while($n = $notifs->fetch_assoc()): ?>
                            <a href="actions.php?action=read_notif&notif_id=<?php echo $n['id']; ?>&link=<?php echo urlencode($n['link']); ?>" class="notification-item <?php echo $n['is_read'] ? '' : 'unread'; ?>" style="display: block; color: inherit;">
                                <?php echo htmlspecialchars($n['message']); ?>
                                <div style="font-size: 0.75rem; color: var(--text-light); margin-top: 4px;"><?php echo date('M d, H:i', strtotime($n['created_at'])); ?></div>
                            </a>
                        <?php endwhile; else: ?>
                            <div style="padding: 20px; text-align: center; color: var(--text-light);">No notifications</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($role == 'tripmaker' || $role == 'admin'): ?>
                <a href="create_trip.php" class="btn btn-primary">+ Create New Trip</a>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Simple toggle for notification
        document.querySelector('[onclick*="notif-box"]').onclick = function() {
            var box = document.getElementById('notif-box');
            box.style.display = box.style.display === 'block' ? 'none' : 'block';
        }
    </script>


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
                <div class="trip-card">
                    <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="Trip" class="trip-image">
                    <div class="trip-content">
                        <h3 class="trip-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                        <div class="trip-meta">
                            <span><i class="ri-map-pin-line"></i> <?php echo htmlspecialchars($row['destination']); ?></span>
                        </div>
                        <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: center;">
                            <span style="padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; background: <?php echo $row['enroll_status'] == 'approved' ? '#dbfce1' : '#feebc8'; ?>; color: <?php echo $row['enroll_status'] == 'approved' ? '#2f855a' : '#c05621'; ?>;">
                                <?php echo ucfirst($row['enroll_status']); ?>
                            </span>
                            <a href="trip.php?id=<?php echo $row['id']; ?>" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.9rem;">View</a>
                        </div>
                    </div>
                </div>
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
                    ORDER BY created_at DESC LIMIT 12";
            $result = $conn->query($sql);
            if ($result->num_rows > 0):
                while($row = $result->fetch_assoc()):
                     // Status Logic
                    $status = 'OPEN';
                    $status_class = 'status-open';
                    $percent = 0;
                    if($row['max_participants'] > 0) {
                        $percent = min(100, ($row['joined_count'] / $row['max_participants']) * 100);
                        if($row['joined_count'] >= $row['max_participants']) {
                            $status = 'FULL';
                            $status_class = 'status-full';
                        }
                    }
                    if($row['registration_deadline'] && strtotime($row['registration_deadline']) < time()) {
                        $status = 'CLOSED';
                        $status_class = 'status-closed';
                    }
            ?>
                <div class="trip-card" style="position: relative;">
                    <div class="status-badge <?php echo $status_class; ?>"><?php echo $status; ?></div>
                    <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="Trip" class="trip-image">
                    
                    <div class="trip-content">
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-light); margin-bottom: 4px;">
                             <span>#<?php echo $row['id']; ?></span>
                             <span>📅 <?php echo date('d M', strtotime($row['start_date'])); ?> - <?php echo date('d M', strtotime($row['end_date'])); ?></span>
                        </div>
                        
                        <h3 class="trip-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                        
                        <div style="margin: 10px 0;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 4px;">
                                <span>Participants</span>
                                <span><?php echo $row['joined_count']; ?> / <?php echo $row['max_participants'] > 0 ? $row['max_participants'] : '∞'; ?></span>
                            </div>
                            <div class="progress-container">
                                <div class="progress-bar" style="width: <?php echo $percent; ?>%;"></div>
                            </div>
                        </div>

                        <div class="trip-price">$<?php echo number_format($row['cost'], 0); ?></div>
                        
                        <div class="trip-footer">
                            <a href="trip.php?id=<?php echo $row['id']; ?>" class="btn btn-outline" style="width: 100%; text-align: center;">View Details</a>
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
        
        <!-- Pending Requests -->
        <!-- Reuse existing request table, simplified for brevity here -->

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
            ?>
                <div class="trip-card">
                    <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="Trip" class="trip-image">
                    
                    <div class="trip-content">
                        <h3 class="trip-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                        
                        <!-- Earnings Snapshot -->
                        <div style="background: #f7fafc; padding: 10px; border-radius: 8px; margin: 10px 0; font-size: 0.9rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                <span style="color: var(--text-light);">Joined:</span>
                                <strong><?php echo $row['joined_count']; ?> / <?php echo $row['max_participants'] ?: '∞'; ?></strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-light);">Earnings:</span>
                                <strong style="color: var(--primary-color);">$<?php echo number_format($earnings); ?></strong>
                            </div>
                        </div>

                        <div class="trip-footer">
                            <a href="trip.php?id=<?php echo $row['id']; ?>" class="btn btn-outline" style="flex: 1; text-align: center; margin-right: 8px;">Manage</a>
                            <a href="edit_trip.php?id=<?php echo $row['id']; ?>" style="color: var(--primary-color); padding: 8px; margin-right: 8px;" title="Edit"><i class="ri-edit-2-line"></i></a>
                            <a href="actions.php?action=delete_trip&trip_id=<?php echo $row['id']; ?>" onclick="return confirm('Delete this trip?');" style="color: #cbd5e0; padding: 8px;"><i class="ri-delete-bin-line"></i></a>
                        </div>
                    </div>
                </div>
            <?php endwhile; endif; ?>
        </div>

    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>
