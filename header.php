<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
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
                <li><a href="popular_trips.php" class="nav-link">Popular Trips</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <li><a href="admin_dashboard.php" class="nav-link" style="color: var(--primary-color); font-weight: 700;"><i class="ri-shield-user-fill"></i> Admin Center</a></li>
                    <?php
    endif; ?>
                    <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'tripmaker'): ?>
                        <li><a href="create_trip.php" class="nav-link">Create Trip</a></li>
                    <?php
    endif; ?>
                <?php
endif; ?>
            </ul>
            <div class="auth-buttons" style="display: flex; align-items: center; gap: 15px;">
                <?php if (isset($_SESSION['user_id'])):
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
                while ($r = $r_res->fetch_assoc())
                    $pending_reqs[] = $r;
            }
        }

        // 2. Fetch Notifications (for everyone)
        $n_sql = "SELECT * FROM notifications WHERE user_id=$n_user_id AND is_read=0 ORDER BY created_at DESC LIMIT 5";
        $n_res = $conn->query($n_sql);
        if ($n_res) {
            while ($n = $n_res->fetch_assoc())
                $notifs[] = $n;
        }

        $total_count = count($pending_reqs) + count($notifs);
    }
?>
                    <!-- Notification Bell -->
                    <div class="nav-icon-btn" onclick="toggleNotifDropdown(event)">
                        <i class="ri-notification-3-line"></i>
                        <?php if ($total_count > 0): ?>
                            <span class="notification-badge"><?php echo $total_count; ?></span>
                        <?php
    endif; ?>
                        
                        <!-- Dropdown -->
                        <div class="notif-dropdown" id="notifDropdown">
                            <div class="notif-header">
                                <span>Notifications</span>
                                <span style="font-size: 0.8rem; font-weight: 500; color: var(--primary-color);"><?php echo $total_count; ?> New</span>
                            </div>
                            <div class="notif-body">
                                <!-- Pending Requests Section -->
                                <?php if (count($pending_reqs) > 0): ?>
                                    <div style="padding: 8px 16px; background: #fffaf5; font-size: 0.75rem; font-weight: 800; color: var(--primary-color);">JOIN REQUESTS</div>
                                    <?php foreach ($pending_reqs as $pr): ?>
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
                                    <?php
        endforeach; ?>
                                <?php
    endif; ?>

                                <!-- Regular Notifications -->
                                <?php if (count($notifs) > 0): ?>
                                    <div style="padding: 8px 16px; background: #f8fafc; font-size: 0.75rem; font-weight: 800; color: var(--text-light); border-top: 1px solid #edf2f7; border-bottom: 1px solid #edf2f7;">ALERTS</div>
                                    <?php foreach ($notifs as $nt): ?>
                                        <a href="actions.php?action=read_notif&notif_id=<?php echo $nt['id']; ?>&link=<?php echo urlencode($nt['link']); ?>" style="display: block; padding: 12px 16px; border-bottom: 1px solid #edf2f7; color: inherit; transition: bg 0.2s;" onmouseover="this.style.background='#fffaf5'" onmouseout="this.style.background='transparent'">
                                            <div style="font-size: 0.9rem; font-weight: 500; color: var(--secondary-color);"><?php echo htmlspecialchars($nt['message']); ?></div>
                                            <div style="font-size: 0.7rem; color: var(--text-light); margin-top: 4px;"><i class="ri-time-line" style="vertical-align: middle;"></i> <?php echo date('M d, H:i', strtotime($nt['created_at'])); ?></div>
                                        </a>
                                    <?php
        endforeach; ?>
                                <?php
    endif; ?>

                                <?php if ($total_count == 0): ?>
                                    <div style="padding: 40px; text-align: center; color: var(--text-light);">
                                        <i class="ri-notification-off-line" style="font-size: 2.5rem; margin-bottom: 8px; display: block; opacity: 0.5;"></i>
                                        No new notifications
                                    </div>
                                <?php
    endif; ?>
                            </div>
                        </div>
                    </div>

                    <span style="font-weight: 700; color: var(--text-color);">Hi, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <a href="logout.php" class="btn" style="border: 2px solid #e2e8f0; padding: 8px 16px; border-radius: 12px; background: white; font-weight: 700;">Logout</a>
                
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
                                    <div style="background: #fef2f2; color: #dc2626; padding: 10px; border-radius: 8px; font-size: 0.8rem; margin-bottom: 15px; border: 1px solid #fecaca; display: flex; align-items: center; gap: 8px;">
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
                                    <button type="submit" class="btn" style="width: 100%; padding: 10px; font-size: 0.85rem; border-radius: 8px; background: var(--secondary-color); color: white; border: none; font-weight: 700;">Authenticate</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div style="width: 1px; height: 20px; background: #e2e8f0; margin: 0 5px;"></div>
                    <a href="login.php" class="btn" style="border: 2px solid #e2e8f0; padding: 8px 16px; border-radius: 12px; background: white; font-weight: 700;">Log In</a>
                    <a href="register.php" class="btn btn-primary" style="padding: 10px 20px; border-radius: 12px; box-shadow: 0 4px 10px rgba(234,88,12,0.3);">Register</a>
                <?php
endif; ?>
            </div>

            <script>
            function toggleAdminDropdown(event) {
                event.stopPropagation();
                const dd = document.getElementById('adminLoginDropdown');
                const notifDd = document.getElementById('notifDropdown');
                if (notifDd) notifDd.classList.remove('show');
                dd.classList.toggle('show');
            }

            // Auto-open if error exists
            document.addEventListener('DOMContentLoaded', function() {
                <?php if (isset($show_admin_error))
    echo "document.getElementById('adminLoginDropdown').classList.add('show');"; ?>
            });

            function toggleNotifDropdown(event) {
                event.stopPropagation();
                const dd = document.getElementById('notifDropdown');
                const adminDd = document.getElementById('adminLoginDropdown');
                if (adminDd) adminDd.classList.remove('show');
                dd.classList.toggle('show');
            }

            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                const dd = document.getElementById('notifDropdown');
                const adminDd = document.getElementById('adminLoginDropdown');
                if (dd && dd.classList.contains('show')) { dd.classList.remove('show'); }
                if (adminDd && adminDd.classList.contains('show')) { adminDd.classList.remove('show'); }
            });
            
            // Prevent closing when clicking inside dropdown
            document.getElementById('notifDropdown')?.addEventListener('click', function(e) { e.stopPropagation(); });
            document.getElementById('adminLoginDropdown')?.addEventListener('click', function(e) { e.stopPropagation(); });
            </script>
        </div>
    </nav>
    <!-- Content padding for fixed navbar -->
    <div style="height: 65px;"></div>
