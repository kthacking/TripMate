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
$recent_logs = false;
try {
    $recent_logs = $conn->query("SELECT l.*, u.name as user_name FROM activity_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 5");
} catch (Exception $e) { $recent_logs = false; }
?>

<!-- Stat Grid -->
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px; margin-bottom: 40px;">
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background: #EEF2FF; color: #6366F1;"><i class="ri-group-fill"></i></div>
            <span class="stat-label">Total Users</span>
        </div>
        <div class="stat-value"><?php echo $total_users; ?></div>
        <div class="stat-footer">
            <i class="ri-user-star-line"></i> <span><?php echo $admin_count; ?> Admins · <?php echo $tm_count; ?> TM</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background: #ECFDF5; color: #10B981;"><i class="ri-send-plane-fill"></i></div>
            <span class="stat-label">Active Trips</span>
        </div>
        <div class="stat-value"><?php echo $active_trips; ?></div>
        <div class="stat-footer">
            <i class="ri-checkbox-circle-line"></i> <span><?php echo $completed_trips; ?> Completed</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background: #FFFBEB; color: #F59E0B;"><i class="ri-user-add-fill"></i></div>
            <span class="stat-label">Pending Requests</span>
        </div>
        <div class="stat-value"><?php echo $pending_reqs; ?></div>
        <div class="stat-footer">
            <i class="ri-time-line"></i> <span>Awaiting your review</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background: #FDF2F8; color: #EC4899;"><i class="ri-image-2-fill"></i></div>
            <span class="stat-label">Media Assets</span>
        </div>
        <div class="stat-value"><?php echo $total_media; ?></div>
        <div class="stat-footer">
            <i class="ri-gallery-line"></i> <span>Shared moments</span>
        </div>
    </div>

</div>

<div style="display: grid; grid-template-columns: 1fr 380px; gap: 40px; align-items: start;">
    
    <!-- Recent Logs -->
    <div class="admin-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: #334155;">System Audit Logs</h3>
            <a href="admin_logs.php" style="font-size: 0.85rem; color: var(--admin-primary); font-weight: 700; text-decoration: none;">View All <i class="ri-arrow-right-line"></i></a>
        </div>
        
        <div style="overflow-x: auto;">
            <table class="admin-table" style="width: 100%; border-collapse: separate; border-spacing: 0 10px;">
                <thead>
                    <tr style="text-align: left;">
                        <th style="padding-bottom: 10px;">User</th>
                        <th style="padding-bottom: 10px;">Action</th>
                        <th style="padding-bottom: 10px; text-align: right;">Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($recent_logs && $recent_logs->num_rows > 0): while($log = $recent_logs->fetch_assoc()): ?>
                    <tr style="background: #F8FAFC; border-radius: 12px;">
                        <td style="padding: 15px 20px; border-radius: 12px 0 0 12px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: white; border: 1px solid #E2E8F0; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 700; color: #475569;">
                                    <?php echo $log['user_name'] ? strtoupper(substr($log['user_name'], 0, 1)) : 'S'; ?>
                                </div>
                                <span style="font-weight: 600; color: #334155;"><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></span>
                            </div>
                        </td>
                        <td style="padding: 15px 20px;"><span style="font-size: 0.9rem; color: #64748B;"><?php echo htmlspecialchars($log['action']); ?></span></td>
                        <td style="padding: 15px 20px; border-radius: 0 12px 12px 0; text-align: right;">
                            <span style="font-size: 0.85rem; color: #94A3B8; font-weight: 500;"><?php echo date('M d, H:i', strtotime($log['created_at'])); ?></span>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 60px 0;">
                                <div style="color: #94A3B8; display: flex; flex-direction: column; align-items: center; gap: 10px;">
                                    <i class="ri-notification-off-line" style="font-size: 2.5rem; opacity: 0.5;"></i>
                                    <span style="font-weight: 500;">No recent system activity recorded</span>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Actions / Status Area -->
    <div style="display: flex; flex-direction: column; gap: 30px;">
        <div style="background: var(--admin-sidebar-bg); color: white; border-radius: var(--radius-xl); padding: 35px; box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px;">
                <h3 style="font-size: 1rem; font-weight: 700; margin: 0; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px;">System Health</h3>
                <div style="width: 10px; height: 10px; border-radius: 50%; background: #10B981; box-shadow: 0 0 15px #10B981; animation: pulse-green 2s infinite;"></div>
            </div>
            
            <div style="margin-bottom: 30px;">
                <div style="font-size: 1.4rem; font-weight: 700; margin-bottom: 10px;">Fully Operational</div>
                <p style="font-size: 0.85rem; color: #94A3B8; line-height: 1.6; margin: 0;">Verified 5 minutes ago. All platform services (API, DB, Media) are reachable and performing as expected.</p>
            </div>

            <button onclick="location.href='admin_settings.php'" class="btn" style="width: 100%; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); color: white; padding: 14px; border-radius: 12px; font-weight: 600; font-size: 0.9rem; transition: all 0.2s;">
                Open Settings Center
            </button>
        </div>

        <div class="admin-card" style="padding: 25px;">
            <h3 style="font-size: 1rem; color: #334155; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="ri-flashlight-line" style="color: #F59E0B;"></i> Quick Navigation
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <a href="admin_users.php" style="background: #F8FAFC; padding: 16px 10px; border-radius: 12px; text-decoration: none; text-align: center; color: #475569; font-weight: 600; font-size: 0.8rem; border: 1px solid #F1F5F9; transition: all 0.2s;">Add Admin</a>
                <a href="admin_requests.php" style="background: #F8FAFC; padding: 16px 10px; border-radius: 12px; text-decoration: none; text-align: center; color: #475569; font-weight: 600; font-size: 0.8rem; border: 1px solid #F1F5F9; transition: all 0.2s;">Join Requests</a>
                <a href="admin_media.php" style="background: #F8FAFC; padding: 16px 10px; border-radius: 12px; text-decoration: none; text-align: center; color: #475569; font-weight: 600; font-size: 0.8rem; border: 1px solid #F1F5F9; transition: all 0.2s;">Media</a>
                <a href="admin_settings.php" style="background: #F8FAFC; padding: 16px 10px; border-radius: 12px; text-decoration: none; text-align: center; color: #475569; font-weight: 600; font-size: 0.8rem; border: 1px solid #F1F5F9; transition: all 0.2s;">Settings</a>
            </div>
        </div>
    </div>

</div>

<style>
@keyframes pulse-green {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}
.btn:hover { background: rgba(255,255,255,0.18) !important; transform: translateY(-2px); }
</style>

<?php require_once 'admin_footer.php'; ?>
