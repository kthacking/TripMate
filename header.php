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
                    <?php if($_SESSION['role'] == 'admin'): ?>
                        <li><a href="admin_dashboard.php" class="nav-link" style="color: var(--primary-color); font-weight: 700;"><i class="ri-shield-user-fill"></i> Admin Center</a></li>
                    <?php endif; ?>
                    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'tripmaker'): ?>
                        <li><a href="create_trip.php" class="nav-link">Create Trip</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            <div class="auth-buttons" style="display: flex; align-items: center;">
                <?php if(isset($_SESSION['user_id'])): 
                    // Notification Logic
                    $n_user_id = $_SESSION['user_id'];
                    $n_role = $_SESSION['role'];
                    $pending_reqs = [];
                    $notifs = [];
                    $total_count = 0;

                    if (isset($conn)) {
                        // 1. Fetch Pending Requests (for TripMaker/Admin)
                        if ($n_role == 'tripmaker' || $n_role == 'admin') {
                            $r_where = ($n_role == 'admin') ? "1=1" : "t.created_by = $n_user_id";
                            $req_sql = "SELECT e.id as req_id, u.name, u.email, t.title 
                                        FROM enrollments e 
                                        JOIN users u ON e.student_id = u.id 
                                        JOIN trips t ON e.trip_id = t.id 
                                        WHERE e.status = 'pending' AND $r_where 
                                        ORDER BY e.request_date ASC";
                            $r_res = $conn->query($req_sql);
                            if ($r_res) {
                                while($r = $r_res->fetch_assoc()) $pending_reqs[] = $r;
                            }
                        }

                        // 2. Fetch Notifications (for everyone)
                        $n_sql = "SELECT * FROM notifications WHERE user_id=$n_user_id AND is_read=0 ORDER BY created_at DESC LIMIT 5";
                        $n_res = $conn->query($n_sql);
                        if ($n_res) {
                            while($n = $n_res->fetch_assoc()) $notifs[] = $n;
                        }
                        
                        $total_count = count($pending_reqs) + count($notifs);
                    }
                ?>
                    <!-- Notification Bell -->
                    <div class="nav-icon-btn" onclick="toggleNotifDropdown(event)">
                        <i class="ri-notification-3-line"></i>
                        <?php if($total_count > 0): ?>
                            <span class="notification-badge"><?php echo $total_count; ?></span>
                        <?php endif; ?>
                        
                        <!-- Dropdown -->
                        <div class="notif-dropdown" id="notifDropdown">
                            <div class="notif-header">
                                <span>Notifications</span>
                                <span style="font-size: 0.8rem; font-weight: 500; color: var(--primary-color);"><?php echo $total_count; ?> New</span>
                            </div>
                            <div class="notif-body">
                                <!-- Pending Requests Section -->
                                <?php if(count($pending_reqs) > 0): ?>
                                    <div style="padding: 8px 16px; background: #ebf8ff; font-size: 0.75rem; font-weight: 700; color: #2c5282;">JOIN REQUESTS</div>
                                    <?php foreach($pending_reqs as $pr): ?>
                                        <div class="req-card-mini">
                                            <div class="req-info">
                                                <div class="mini-avatar"><?php echo strtoupper(substr($pr['name'], 0, 1)); ?></div>
                                                <div class="req-details">
                                                    <span class="req-name"><?php echo htmlspecialchars($pr['name']); ?></span>
                                                    <span class="req-email"><?php echo htmlspecialchars($pr['email']); ?></span>
                                                    <span class="req-trip"><i class="ri-map-pin-line"></i> <?php echo htmlspecialchars($pr['title']); ?></span>
                                                </div>
                                            </div>
                                            <div class="req-actions-mini">
                                                <a href="actions.php?action=approve_request&req_id=<?php echo $pr['req_id']; ?>" class="btn-mini btn-mini-approve">Approve</a>
                                                <a href="actions.php?action=reject_request&req_id=<?php echo $pr['req_id']; ?>" class="btn-mini btn-mini-reject">Reject</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <!-- Regular Notifications -->
                                <?php if(count($notifs) > 0): ?>
                                    <div style="padding: 8px 16px; background: #f7fafc; font-size: 0.75rem; font-weight: 700; color: var(--text-light); border-top: 1px solid #edf2f7; border-bottom: 1px solid #edf2f7;">ALERTS</div>
                                    <?php foreach($notifs as $nt): ?>
                                        <a href="actions.php?action=read_notif&notif_id=<?php echo $nt['id']; ?>&link=<?php echo urlencode($nt['link']); ?>" style="display: block; padding: 12px 16px; border-bottom: 1px solid #edf2f7; color: inherit; transition: bg 0.2s;" onmouseover="this.style.background='#f9f9f9'" onmouseout="this.style.background='transparent'">
                                            <div style="font-size: 0.9rem;"><?php echo htmlspecialchars($nt['message']); ?></div>
                                            <div style="font-size: 0.7rem; color: var(--text-light); margin-top: 4px;"><?php echo date('M d, H:i', strtotime($nt['created_at'])); ?></div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <?php if($total_count == 0): ?>
                                    <div style="padding: 40px; text-align: center; color: var(--text-light);">
                                        <i class="ri-notification-off-line" style="font-size: 2rem; margin-bottom: 8px; display: block;"></i>
                                        No new notifications
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <span style="margin-right: 15px; font-weight: 500; color: var(--text-color);">Hi, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <a href="logout.php" class="btn btn-outline" style="border:none; padding: 8px 16px;">Logout</a>
                
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline" style="margin-right: 10px;">Log In</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>

            <script>
            function toggleNotifDropdown(event) {
                event.stopPropagation();
                const dd = document.getElementById('notifDropdown');
                dd.classList.toggle('show');
            }

            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                const dd = document.getElementById('notifDropdown');
                if (dd && dd.classList.contains('show')) {
                    dd.classList.remove('show');
                }
            });
            
            // Prevent closing when clicking inside dropdown
            document.getElementById('notifDropdown')?.addEventListener('click', function(e) {
                e.stopPropagation();
            });
            </script>
        </div>
    </nav>
    <!-- Content padding for fixed navbar -->
    <div style="height: 65px;"></div>
