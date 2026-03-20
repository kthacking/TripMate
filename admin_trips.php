<?php
require_once 'admin_header.php';

// Handle Actions
if (isset($_GET['action'])) {
    $tid = intval($_GET['tid']);
    $action = $_GET['action'];

    if ($action == 'toggle_status') {
        $new_status = $_GET['status'];
        $conn->query("UPDATE trips SET status = '$new_status' WHERE id = $tid");
        logActivity($conn, $_SESSION['user_id'], "Changed trip status", "Trip ID: $tid to $new_status");
    } elseif ($action == 'delete') {
        $conn->query("DELETE FROM trips WHERE id = $tid");
        logActivity($conn, $_SESSION['user_id'], "Deleted trip", "Trip ID: $tid");
    } elseif ($action == 'clone') {
        $trip = $conn->query("SELECT * FROM trips WHERE id = $tid")->fetch_assoc();
        unset($trip['id']);
        $trip['title'] .= " (Clone)";
        $keys = array_keys($trip);
        $values = array_map(function ($v) use ($conn) {
            return "'" . $conn->real_escape_string($v) . "'"; }, array_values($trip));
        $conn->query("INSERT INTO trips (" . implode(",", $keys) . ") VALUES (" . implode(",", $values) . ")");
        logActivity($conn, $_SESSION['user_id'], "Cloned trip", "Trip ID: $tid");
    }
    header("Location: admin_trips.php?msg=success");
    exit();
}

$trips = $conn->query("SELECT t.*, u.name as organizer FROM trips t JOIN users u ON t.created_by = u.id ORDER BY t.created_at DESC");
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

    .page-header {
        margin-bottom: 40px;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        animation: fadeInDown 0.8s ease-out;
    }

    .header-text h1 {
        font-size: 2.8rem;
        font-weight: 900;
        letter-spacing: -1.5px;
        color: #1E293B;
        margin: 0 0 10px 0;
    }

    .header-text p {
        font-size: 1.1rem;
        color: #64748B;
        margin: 0;
        font-weight: 500;
    }

    .btn-launch {
        display: flex;
        align-items: center;
        gap: 10px;
        background: linear-gradient(135deg, var(--admin-primary), #6366F1);
        padding: 14px 28px;
        border-radius: 16px;
        text-decoration: none;
        color: white;
        font-weight: 800;
        box-shadow: 0 10px 20px -5px rgba(var(--admin-primary-rgb), 0.3);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
    }

    .btn-launch:hover {
        transform: translateY(-3px) scale(1.02);
        box-shadow: 0 15px 30px -8px rgba(var(--admin-primary-rgb), 0.4);
        filter: brightness(1.1);
    }

    /* Trip Grid */
    .trip-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(500px, 1fr));
        gap: 30px;
        animation: fadeIn 0.8s ease-out 0.2s both;
    }

    .trip-card {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-radius: 24px;
        padding: 25px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        display: grid;
        grid-template-columns: 140px 1fr 160px;
        gap: 25px;
        align-items: center;
        position: relative;
        overflow: visible !important;
    }

    .trip-card:hover {
        transform: translateY(-10px);
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.4);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        z-index: 50;
    }

    .trip-image-container {
        width: 140px;
        height: 140px;
        position: relative;
    }

    .trip-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 20px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        border: 3px solid white;
    }

    .trip-badge-mini {
        position: absolute;
        bottom: -10px;
        right: -10px;
        width: 40px;
        height: 40px;
        background: white;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        font-size: 1.2rem;
        color: var(--admin-primary);
    }

    .trip-info-main h3 {
        font-size: 1.4rem;
        font-weight: 850;
        color: #1E293B;
        margin: 0 0 6px 0;
        letter-spacing: -0.6px;
    }

    .location-text {
        font-size: 0.95rem;
        color: #64748B;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 15px;
    }

    .organizer-box {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
    }

    .organizer-avatar {
        width: 32px;
        height: 32px;
        background: #EEF2FF;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 850;
        font-size: 0.8rem;
        color: var(--admin-primary);
        border: 1px solid #E0E7FF;
    }

    .organizer-name {
        font-size: 0.9rem;
        font-weight: 700;
        color: #475569;
    }

    .investment-tag {
        font-size: 1.2rem;
        font-weight: 900;
        color: var(--admin-primary);
        letter-spacing: -0.5px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .status-section {
        display: flex;
        flex-direction: column;
        gap: 15px;
        align-items: flex-end;
    }

    .badge-pill {
        padding: 6px 14px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .badge-active { background: #DCFCE7; color: #16A34A; border: 1px solid #BBF7D0; }
    .badge-completed { background: #F0FDF4; color: #15803D; border: 1px solid #DCFCE7; }
    .badge-cancelled { background: #FEF2F2; color: #DC2626; border: 1px solid #FEE2E2; }

    .progress-container {
        width: 100%;
    }

    .progress-labels {
        display: flex;
        justify-content: space-between;
        font-size: 0.75rem;
        font-weight: 800;
        color: #94A3B8;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .progress-bar-bg {
        height: 8px;
        background: #F1F5F9;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.03);
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--admin-primary), #6366F1);
        border-radius: 10px;
        transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .card-actions {
        display: flex;
        gap: 8px;
        margin-top: 15px;
    }

    .action-btn {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        color: #64748B;
        background: white;
        border: 1px solid #E2E8F0;
        transition: all 0.3s ease;
        font-size: 1.1rem;
    }

    .action-btn:hover {
        background: #F8FAFC;
        color: var(--admin-primary);
        transform: translateY(-2px);
        border-color: var(--admin-primary);
        box-shadow: 0 5px 15px rgba(var(--admin-primary-rgb), 0.1);
    }

    .action-btn.more-btn {
        cursor: pointer;
    }

    /* Decorative element */
    .trip-card-deco {
        position: absolute;
        bottom: -20px;
        left: -20px;
        opacity: 0.03;
        font-size: 8rem;
        pointer-events: none;
        transform: rotate(-15deg);
    }

    /* Dropdown UI */
    .dropdown-menu {
        display: none;
        position: absolute;
        background: white;
        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        border-radius: 16px;
        z-index: 100;
        min-width: 200px;
        padding: 10px;
        border: 1px solid rgba(0,0,0,0.05);
        right: 0;
        bottom: 100%;
        margin-bottom: 15px;
        animation: slideInUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .dropdown-menu.show { display: block !important; }

    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 15px;
        font-size: 0.9rem;
        text-decoration: none;
        color: #475569;
        font-weight: 600;
        border-radius: 10px;
        transition: all 0.2s ease;
    }

    .dropdown-item:hover {
        background: #F1F5F9;
        color: var(--admin-primary);
        transform: translateX(5px);
    }

    .dropdown-item.text-danger:hover {
        background: #FEF2F2;
        color: #EF4444;
    }

    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeInDown {
        from { opacity: 0; transform: translateY(-30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes slideInUp {
        from { opacity: 0; transform: translateY(10px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @media (max-width: 1024px) {
        .trip-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 768px) {
        .page-header { flex-direction: column; align-items: flex-start; gap: 20px; }
        .header-text h1 { font-size: 2.2rem; }
        .trip-card { grid-template-columns: 100px 1fr; }
        .status-section { grid-column: 1 / -1; align-items: stretch; margin-top: 10px; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 20px; }
        .trip-image-container { width: 100px; height: 100px; }
    }

    .admin-content { padding: 0 !important; max-width: none !important; }
</style>

<div class="geo-bg"></div>

<div class="dashboard-container">
    <div class="page-header">
        <div class="header-text">
            <h1>Trip Manifest</h1>
            <p>Manage and monitor all platform expeditions and travel logistics</p>
        </div>
        <a href="create_trip.php" class="btn-launch">
            <i class="ri-add-circle-line"></i> Launch New Expedition
        </a>
    </div>

    <!-- Trip Grid -->
    <div class="trip-grid">
        <?php if ($trips->num_rows > 0): while ($t = $trips->fetch_assoc()): 
            $tid = $t['id'];
            $p_count = $conn->query("SELECT COUNT(*) as c FROM enrollments WHERE trip_id = $tid AND status = 'approved'")->fetch_assoc()['c'];
            $max = $t['max_participants'] ?: '∞';
            $perc = (is_numeric($max) && $max > 0) ? ($p_count / $max) * 100 : 0;
            
            $status_class = 'badge-' . $t['status'];
            $status_icon = [
                'active' => 'ri-flashlight-line',
                'completed' => 'ri-checkbox-circle-line',
                'cancelled' => 'ri-close-circle-line'
            ][$t['status']] ?? 'ri-question-line';
        ?>
            <div class="trip-card">
                <i class="ri-map-2-line trip-card-deco"></i>
                
                <!-- Left Section -->
                <div class="trip-image-container">
                    <img src="<?php echo htmlspecialchars($t['image_url']); ?>" class="trip-image" alt="Trip Image">
                    <div class="trip-badge-mini">
                        <i class="ri-earth-line"></i>
                    </div>
                </div>

                <!-- Center Section -->
                <div class="trip-info-main">
                    <h3><?php echo htmlspecialchars($t['title']); ?></h3>
                    <div class="location-text">
                        <i class="ri-map-pin-2-fill" style="color: #6366F1;"></i>
                        <?php echo htmlspecialchars($t['destination']); ?>
                    </div>
                    
                    <div class="organizer-box">
                        <div class="organizer-avatar">
                            <?php echo strtoupper(substr($t['organizer'], 0, 1)); ?>
                        </div>
                        <span class="organizer-name"><?php echo htmlspecialchars($t['organizer']); ?></span>
                    </div>

                    <div class="investment-tag">
                        <span style="font-size: 0.8rem; font-weight: 700; color: #94A3B8; text-transform: uppercase;">Investment:</span>
                        $<?php echo number_format($t['cost']); ?>
                    </div>
                </div>

                <!-- Right Section -->
                <div class="status-section">
                    <span class="badge-pill <?php echo $status_class; ?>">
                        <i class="<?php echo $status_icon; ?>"></i>
                        <?php echo strtoupper($t['status']); ?>
                    </span>

                    <div class="progress-container">
                        <div class="progress-labels">
                            <span>Manifest</span>
                            <span><?php echo $p_count; ?> / <?php echo $max; ?></span>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-fill" style="width: <?php echo $perc; ?>%;"></div>
                        </div>
                    </div>

                    <div class="card-actions">
                        <a href="trip.php?id=<?php echo $t['id']; ?>" class="action-btn" title="View Preview" target="_blank">
                            <i class="ri-eye-line"></i>
                        </a>
                        <a href="edit_trip.php?id=<?php echo $t['id']; ?>" class="action-btn" title="Edit Content">
                            <i class="ri-edit-2-line"></i>
                        </a>
                        <div class="dropdown" style="position: relative;">
                            <div class="action-btn more-btn" title="More Options" onclick="toggleDropdown(this, event)">
                                <i class="ri-more-fill"></i>
                            </div>
                            <div class="dropdown-menu">
                                <a href="?action=clone&tid=<?php echo $t['id']; ?>" class="dropdown-item">
                                    <i class="ri-file-copy-2-line"></i> Duplicate Trip
                                </a>
                                <div style="height: 1px; background: #F1F5F9; margin: 8px 0;"></div>
                                <a href="?action=toggle_status&tid=<?php echo $t['id']; ?>&status=active" class="dropdown-item">
                                    <i class="ri-flashlight-line"></i> Mark as Active
                                </a>
                                <a href="?action=toggle_status&tid=<?php echo $t['id']; ?>&status=completed" class="dropdown-item">
                                    <i class="ri-checkbox-circle-line"></i> Mark as Completed
                                </a>
                                <a href="?action=toggle_status&tid=<?php echo $t['id']; ?>&status=cancelled" class="dropdown-item text-danger">
                                    <i class="ri-close-circle-line"></i> Cancel Operation
                                </a>
                                <div style="height: 1px; background: #F1F5F9; margin: 8px 0;"></div>
                                <a href="?action=delete&tid=<?php echo $t['id']; ?>" class="dropdown-item text-danger" 
                                   onclick="return confirm('Security Check: Permanent purge? This cannot be undone.')">
                                    <i class="ri-delete-bin-line"></i> Flush Record
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; else: ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 100px; color: #94A3B8;">
                <i class="ri-road-map-line" style="font-size: 4rem; display: block; margin-bottom: 20px;"></i>
                <h2 style="font-weight: 800; color: #64748B;">No Expeditions Found</h2>
                <p>Launch a new expedition to populate the manifest.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleDropdown(el, e) {
        e.stopPropagation();
        const menu = el.nextElementSibling;
        document.querySelectorAll('.dropdown-menu').forEach(m => {
            if (m !== menu) m.classList.remove('show');
        });
        menu.classList.toggle('show');
    }

    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
    });
</script>

<?php require_once 'admin_footer.php'; ?>