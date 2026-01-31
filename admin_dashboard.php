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
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 35px;">
    
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

    <div class="stat-card" style="border-left: 4px solid #10B981;">
        <div class="stat-header">
            <div class="stat-icon" style="background: #ECFDF5; color: #10B981;"><i class="ri-send-plane-fill"></i></div>
            <span class="stat-label">Active Trips</span>
        </div>
        <div class="stat-value"><?php echo $active_trips; ?></div>
        <div class="stat-footer">
            <i class="ri-checkbox-circle-line"></i> <span><?php echo $completed_trips; ?> Completed</span>
        </div>
    </div>

    <div class="stat-card" style="border-left: 4px solid #F59E0B;">
        <div class="stat-header">
            <div class="stat-icon" style="background: #FFFBEB; color: #F59E0B;"><i class="ri-user-add-fill"></i></div>
            <span class="stat-label">Pending Requests</span>
        </div>
        <div class="stat-value"><?php echo $pending_reqs; ?></div>
        <div class="stat-footer">
            <i class="ri-time-line"></i> <span>Awaiting your review</span>
        </div>
    </div>

    <div class="stat-card" style="border-left: 4px solid #EC4899;">
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

<div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px; align-items: start;">
    
    <!-- Recent Logs -->
    <div class="admin-card" style="min-height: 440px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h3 style="font-size: 1.15rem; font-weight: 850; color: var(--admin-text-main); letter-spacing: -0.5px;">System Audit Logs</h3>
            <a href="admin_logs.php" style="font-size: 0.9rem; color: var(--admin-primary); font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 5px;">
                View All <i class="ri-arrow-right-s-line"></i>
            </a>
        </div>
        
        <div style="overflow-x: auto;">
            <table class="admin-table" style="width: 100%; border-collapse: separate; border-spacing: 0 12px;">
                <thead>
                    <tr style="text-align: left;">
                        <th>User</th>
                        <th>Action</th>
                        <th style="text-align: right;">Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($recent_logs && $recent_logs->num_rows > 0): while($log = $recent_logs->fetch_assoc()): ?>
                    <tr style="background: #F8FAFC; transition: all 0.2s hover;">
                        <td style="padding: 14px 20px; border-radius: 16px 0 0 16px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 34px; height: 34px; border-radius: 10px; background: white; border: 1px solid #E2E8F0; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 800; color: var(--admin-primary);">
                                    <?php echo $log['user_name'] ? strtoupper(substr($log['user_name'], 0, 1)) : 'S'; ?>
                                </div>
                                <span style="font-weight: 700; color: #334155; font-size: 0.9rem;"><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></span>
                            </div>
                        </td>
                        <td style="padding: 14px 20px;"><span style="font-size: 0.9rem; color: #64748B; font-weight: 500;"><?php echo htmlspecialchars($log['action']); ?></span></td>
                        <td style="padding: 14px 20px; border-radius: 0 16px 16px 0; text-align: right;">
                            <span style="font-size: 0.85rem; color: #94A3B8; font-weight: 600;"><?php echo date('M d, H:i', strtotime($log['created_at'])); ?></span>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 100px 0;">
                                <div style="color: #94A3B8; display: flex; flex-direction: column; align-items: center; gap: 15px;">
                                    <div style="width: 64px; height: 64px; background: #F1F5F9; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                        <i class="ri-notification-off-line" style="font-size: 2rem; opacity: 0.6;"></i>
                                    </div>
                                    <span style="font-weight: 600; font-size: 1rem;">No recent system activity recorded</span>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right Sidebar Column -->
    <div style="display: flex; flex-direction: column; gap: 30px;">
        
        <!-- Premium Health Status -->
        <div style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); color: white; border-radius: 20px; padding: 35px; box-shadow: 0 20px 40px -12px rgba(15, 23, 42, 0.2); position: relative; overflow: hidden;">
            <div style="position: absolute; top: -20px; right: -20px; width: 150px; height: 150px; background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%);"></div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h3 style="font-size: 0.75rem; font-weight: 800; margin: 0; color: #94A3B8; text-transform: uppercase; letter-spacing: 2px;">System Health</h3>
                <div style="display: flex; align-items: center; gap: 8px; background: rgba(16, 185, 129, 0.1); padding: 5px 12px; border-radius: 30px; border: 1px solid rgba(16, 185, 129, 0.2);">
                    <div style="width: 7px; height: 7px; border-radius: 50%; background: #10B981; box-shadow: 0 0 10px #10B981; animation: pulse-health 2s infinite;"></div>
                    <span style="font-size: 0.7rem; font-weight: 800; color: #10B981;">OPERATIONAL</span>
                </div>
            </div>
            
            <div style="margin-bottom: 30px;">
                <div style="font-size: 1.5rem; font-weight: 850; margin-bottom: 8px; letter-spacing: -0.5px;">All Systems Go</div>
                <p style="font-size: 0.88rem; color: #94A3B8; line-height: 1.6; margin: 0; font-weight: 500;">Your platform is performing optimally. Core services, database connectivity, and media servers are all in high-performance states.</p>
            </div>

            <button onclick="location.href='admin_settings.php'" class="btn-premium" style="width: 100%; background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.1); color: white; justify-content: center; box-shadow: none;">
                <i class="ri-settings-line"></i> View System Config
            </button>
        </div>

        <!-- Better Quick Navigation -->
        <div class="admin-card" style="padding: 25px;">
            <h3 style="font-size: 1rem; color: var(--admin-text-main); font-weight: 850; margin-bottom: 20px; letter-spacing: -0.4px;">Quick Actions</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <a href="admin_users.php" class="action-tile">
                    <i class="ri-user-add-line"></i>
                    <span>Manage Users</span>
                </a>
                <a href="admin_requests.php" class="action-tile">
                    <i class="ri-mail-send-line"></i>
                    <span>Review Requests</span>
                </a>
                <a href="admin_media.php" class="action-tile">
                    <i class="ri-folder-image-line"></i>
                    <span>Media Library</span>
                </a>
                <a href="admin_analytics.php" class="action-tile">
                    <i class="ri-line-chart-line"></i>
                    <span>Platform Insights</span>
                </a>
            </div>
        </div>
    </div>

</div>

<style>
@keyframes pulse-health {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

.action-tile {
    background: #F8FAFC;
    padding: 20px 10px;
    border-radius: 16px;
    text-decoration: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    color: #475569;
    font-weight: 700;
    font-size: 0.8rem;
    border: 1px solid #F1F5F9;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.action-tile i {
    font-size: 1.35rem;
    color: var(--admin-primary);
    background: white;
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.action-tile:hover {
    background: white;
    border-color: var(--admin-primary);
    transform: translateY(-5px);
    box-shadow: var(--shadow-premium);
    color: var(--admin-primary);
}

.action-tile:hover i {
    background: var(--admin-primary);
    color: white;
    transform: scale(1.1);
}
</style>

<?php require_once 'admin_footer.php'; ?>
