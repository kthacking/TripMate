<?php 
require_once 'admin_header.php'; 

// Fetch Stats
$total_users = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$admin_count = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='admin'")->fetch_assoc()['c'];
$tm_count = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='tripmaker'")->fetch_assoc()['c'];
$st_count = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student'")->fetch_assoc()['c'];

$active_trips = $conn->query("SELECT COUNT(*) as c FROM trips WHERE status='active'")->fetch_assoc()['c'];
$completed_trips = $conn->query("SELECT COUNT(*) as c FROM trips WHERE status='completed'")->fetch_assoc()['c'];
$total_trips = $conn->query("SELECT COUNT(*) as c FROM trips")->fetch_assoc()['c'];

$pending_reqs = $conn->query("SELECT COUNT(*) as c FROM enrollments WHERE status='pending'")->fetch_assoc()['c'];
$total_media = $conn->query("SELECT COUNT(*) as c FROM media")->fetch_assoc()['c'];

// Recent activity for the dashboard table
$recent_logs = $conn->query("SELECT l.*, u.name as user_name FROM activity_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 5");
?>

<!-- Stat Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px; margin-bottom: 40px;">
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #E0E7FF; color: #4338CA;"><i class="ri-group-line"></i></div>
        <div>
            <div style="font-size: 0.85rem; color: #6B7280; font-weight: 600;">Total Users</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #111827;"><?php echo $total_users; ?></div>
            <div style="font-size: 0.7rem; color: #6B7280; margin-top: 4px;">Admin: <?php echo $admin_count; ?> | TM: <?php echo $tm_count; ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: #ECFDF5; color: #059669;"><i class="ri-plane-line"></i></div>
        <div>
            <div style="font-size: 0.85rem; color: #6B7280; font-weight: 600;">Active Trips</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #111827;"><?php echo $active_trips; ?></div>
            <div style="font-size: 0.7rem; color: #6B7280; margin-top: 4px;"><?php echo $completed_trips; ?> Completed</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: #FFFBEB; color: #D97706;"><i class="ri-user-follow-line"></i></div>
        <div>
            <div style="font-size: 0.85rem; color: #6B7280; font-weight: 600;">Pending Requests</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #111827;"><?php echo $pending_reqs; ?></div>
            <div style="font-size: 0.7rem; color: #6B7280; margin-top: 4px;">Needs attention</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: #FDF2F8; color: #DB2777;"><i class="ri-image-2-line"></i></div>
        <div>
            <div style="font-size: 0.85rem; color: #6B7280; font-weight: 600;">Media Assets</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #111827;"><?php echo $total_media; ?></div>
            <div style="font-size: 0.7rem; color: #6B7280; margin-top: 4px;">Shared memories</div>
        </div>
    </div>

</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
    
    <!-- Recent Logs -->
    <div style="background: white; border-radius: 16px; padding: 25px; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="font-size: 1.1rem; color: #111827;">System Audit Logs</h3>
            <a href="admin_logs.php" style="font-size: 0.85rem; color: var(--admin-primary); font-weight: 600;">View All</a>
        </div>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Action</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if($recent_logs->num_rows > 0): while($log = $recent_logs->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 32px; height: 32px; border-radius: 8px; background: #EEE; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700; color: #555;">
                                <?php echo $log['user_name'] ? strtoupper(substr($log['user_name'], 0, 1)) : 'S'; ?>
                            </div>
                            <span><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></span>
                        </div>
                    </td>
                    <td><span style="font-size: 0.85rem;"><?php echo htmlspecialchars($log['action']); ?></span></td>
                    <td><span style="font-size: 0.75rem; color: #9CA3AF;"><?php echo date('M d, H:i', strtotime($log['created_at'])); ?></span></td>
                </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="3" style="text-align: center; color: #9CA3AF;">No recent activity</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Quick Actions / Status -->
    <div style="display: flex; flex-direction: column; gap: 25px;">
        <div style="background: #111827; color: white; border-radius: 16px; padding: 25px; box-shadow: var(--shadow-lg);">
            <h3 style="font-size: 1rem; margin-bottom: 20px;">System Health</h3>
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                <div style="width: 12px; height: 12px; border-radius: 50%; background: #10B981; box-shadow: 0 0 10px #10B981;"></div>
                <span style="font-weight: 600;">Status: Operational</span>
            </div>
            <div style="font-size: 0.85rem; color: #9CA3AF; margin-bottom: 25px;">All systems are running normally. No reported issues in the last 24 hours.</div>
            <button onclick="location.href='admin_settings.php'" class="btn btn-primary" style="width: 100%; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);">System Settings</button>
        </div>

        <div style="background: white; border-radius: 16px; padding: 25px; box-shadow: var(--shadow-sm); border: 1px solid #E5E7EB;">
            <h3 style="font-size: 1rem; color: #111827; margin-bottom: 15px;">Quick Links</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <a href="admin_users.php" style="background: #F3F4F6; padding: 15px; border-radius: 12px; text-decoration: none; text-align: center; color: #374151; font-weight: 600; font-size: 0.85rem; transition: background 0.2s;">Add Admin</a>
                <a href="admin_requests.php" style="background: #F3F4F6; padding: 15px; border-radius: 12px; text-decoration: none; text-align: center; color: #374151; font-weight: 600; font-size: 0.85rem; transition: background 0.2s;">Requests</a>
                <a href="admin_media.php" style="background: #F3F4F6; padding: 15px; border-radius: 12px; text-decoration: none; text-align: center; color: #374151; font-weight: 600; font-size: 0.85rem; transition: background 0.2s;">Media</a>
                <a href="admin_settings.php" style="background: #F3F4F6; padding: 15px; border-radius: 12px; text-decoration: none; text-align: center; color: #374151; font-weight: 600; font-size: 0.85rem; transition: background 0.2s;">Settings</a>
            </div>
        </div>
    </div>

</div>

<?php require_once 'admin_footer.php'; ?>
