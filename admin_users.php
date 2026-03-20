<?php
require_once 'admin_header.php';

// Handle Actions
if (isset($_GET['action'])) {
    $uid = intval($_GET['uid']);
    $action = $_GET['action'];

    if ($action == 'toggle_status') {
        $conn->query("UPDATE users SET is_active = !is_active WHERE id = $uid");
        logActivity($conn, $_SESSION['user_id'], "Toggled user status", "User ID: $uid");
    } elseif ($action == 'delete' && $uid != $_SESSION['user_id']) {
        $conn->query("DELETE FROM users WHERE id = $uid");
        logActivity($conn, $_SESSION['user_id'], "Deleted user", "User ID: $uid");
    } elseif ($action == 'change_role') {
        $new_role = $_GET['role'];
        if (in_array($new_role, ['admin', 'tripmaker', 'student'])) {
            $conn->query("UPDATE users SET role = '$new_role' WHERE id = $uid");
            logActivity($conn, $_SESSION['user_id'], "Changed user role", "User ID: $uid to $new_role");
        }
    }
    header("Location: admin_users.php?msg=success");
    exit();
}

// Search & Filter
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';

$where = "role != 'superadmin'"; // Placeholder if needed
if ($search)
    $where .= " AND (name LIKE '%$search%' OR email LIKE '%$search%')";
if ($role_filter)
    $where .= " AND role = '$role_filter'";

$users = $conn->query("SELECT * FROM users WHERE $where ORDER BY created_at DESC");
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

    .btn-modern {
        display: flex;
        align-items: center;
        gap: 8px;
        background: white;
        padding: 12px 24px;
        border-radius: 12px;
        text-decoration: none;
        color: var(--admin-primary);
        font-weight: 700;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: 1px solid #F1F5F9;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        background: #F8FAFC;
    }

    .btn-modern::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 5px;
        height: 5px;
        background: rgba(var(--admin-primary-rgb), 0.2);
        opacity: 0;
        border-radius: 100%;
        transform: scale(1, 1) translate(-50%);
        transform-origin: 50% 50%;
    }

    @keyframes ripple {
        0% { transform: scale(0, 0); opacity: 1; }
        20% { transform: scale(25, 25); opacity: 1; }
        100% { opacity: 0; transform: scale(40, 40); }
    }

    .btn-modern:focus:not(:active)::after {
        animation: ripple 1s ease-out;
    }

    /* Filter Card */
    .filter-section {
        background: rgba(255, 255, 255, 0.6);
        backdrop-filter: blur(10px);
        padding: 25px;
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.5);
        margin-bottom: 40px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        animation: fadeIn 0.8s ease-out 0.2s both;
    }

    .search-row {
        display: flex;
        gap: 15px;
        align-items: center;
        flex-wrap: wrap;
    }

    .search-input-wrapper {
        position: relative;
        flex: 1;
        min-width: 250px;
    }

    .search-input-wrapper i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #94A3B8;
        font-size: 1.1rem;
    }

    .modern-input {
        width: 100%;
        height: 48px;
        padding-left: 48px;
        padding-right: 20px;
        border-radius: 14px;
        border: 1px solid #E2E8F0;
        background: white;
        font-weight: 500;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        color: #1E293B;
    }

    .modern-input:focus {
        border-color: var(--admin-primary);
        box-shadow: 0 0 0 4px rgba(var(--admin-primary-rgb), 0.1);
        outline: none;
    }

    .modern-select {
        height: 48px;
        padding: 0 20px;
        border-radius: 14px;
        border: 1px solid #E2E8F0;
        background: white;
        font-weight: 600;
        color: #475569;
        font-size: 0.9rem;
        min-width: 160px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .modern-select:focus {
        border-color: var(--admin-primary);
        box-shadow: 0 0 0 4px rgba(var(--admin-primary-rgb), 0.1);
        outline: none;
    }

    .btn-filter {
        height: 48px;
        padding: 0 25px;
        border-radius: 14px;
        background: var(--admin-primary);
        color: white;
        border: none;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-filter:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(var(--admin-primary-rgb), 0.3);
        filter: brightness(1.1);
    }

    /* User Grid */
    .user-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 25px;
        animation: fadeIn 0.8s ease-out 0.4s both;
    }

    .user-card {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border-radius: 24px;
        padding: 30px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .user-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.4);
        z-index: 50;
    }

    .user-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 6px;
        background: transparent;
        transition: all 0.3s ease;
    }

    .card-role-admin::before { background: linear-gradient(90deg, #9333EA, #C084FC); }
    .card-role-tripmaker::before { background: linear-gradient(90deg, #EA580C, #FB923C); }
    .card-role-student::before { background: linear-gradient(90deg, #2563EB, #60A5FA); }

    .user-info-section {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 25px;
    }

    .user-avatar {
        width: 65px;
        height: 65px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        font-weight: 900;
        color: white;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .avatar-admin { background: linear-gradient(135deg, #9333EA, #7C3AED); }
    .avatar-tripmaker { background: linear-gradient(135deg, #EA580C, #D97706); }
    .avatar-student { background: linear-gradient(135deg, #2563EB, #1D4ED8); }

    .user-main-info {
        flex: 1;
    }

    .user-name {
        font-size: 1.25rem;
        font-weight: 850;
        color: #1E293B;
        margin: 0;
        letter-spacing: -0.5px;
    }

    .user-email {
        font-size: 0.9rem;
        color: #64748B;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .card-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 20px;
        border-top: 1px solid rgba(0,0,0,0.05);
        margin-top: auto;
    }

    .meta-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .meta-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 800;
        color: #94A3B8;
    }

    .meta-value {
        font-size: 0.9rem;
        font-weight: 700;
        color: #334155;
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

    .badge-admin { background: #F3E8FF; color: #9333EA; border: 1px solid #E9D5FF; }
    .badge-tripmaker { background: #FFEDD5; color: #EA580C; border: 1px solid #FED7AA; }
    .badge-student { background: #DBEAFE; color: #2563EB; border: 1px solid #BFDBFE; }
    .badge-active { background: #DCFCE7; color: #16A34A; border: 1px solid #BBF7D0; }
    .badge-suspended { background: #FEE2E2; color: #DC2626; border: 1px solid #FECACA; }

    .card-actions {
        display: flex;
        gap: 10px;
        margin-top: 25px;
    }

    .action-btn {
        flex: 1;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.85rem;
        transition: all 0.3s ease;
        border: 1px solid transparent;
        gap: 8px;
    }

    .btn-suspend { background: #F8FAFC; color: #475569; border-color: #E2E8F0; }
    .btn-suspend:hover { background: #F1F5F9; border-color: #CBD5E1; color: #1E293B; }
    .btn-delete { background: #FEF2F2; color: #EF4444; border-color: #FEE2E2; }
    .btn-delete:hover { background: #FEE2E2; border-color: #FECACA; transform: scale(1.02); }

    .role-trigger {
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .role-trigger:hover {
        transform: scale(1.05);
        filter: brightness(0.95);
    }

    /* Dropdown UI */
    .dropdown-menu {
        display: none;
        position: absolute;
        background: white;
        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        border-radius: 16px;
        z-index: 100;
        min-width: 180px;
        padding: 10px;
        border: 1px solid rgba(0,0,0,0.05);
        left: 0;
        top: 100%;
        margin-top: 10px;
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

    @media (max-width: 768px) {
        .page-header { flex-direction: column; align-items: flex-start; gap: 20px; }
        .header-text h1 { font-size: 2.2rem; }
        .user-grid { grid-template-columns: 1fr; }
        .search-row { flex-direction: column; align-items: stretch; }
    }

    .admin-content { padding: 0 !important; max-width: none !important; }
</style>

<div class="geo-bg"></div>

<div class="dashboard-container">
    <div class="page-header">
        <div class="header-text">
            <h1>User Directory</h1>
            <p>Manage community members, roles, and platform permissions</p>
        </div>
        <a href="admin_reg.php" class="btn-modern">
            <i class="ri-user-add-line"></i> Provision New Admin
        </a>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" class="search-row">
            <div class="search-input-wrapper">
                <i class="ri-search-2-line"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Search by name, email, or ID..." class="modern-input">
            </div>
            <select name="role" class="modern-select" onchange="this.form.submit()">
                <option value="">All Account Roles</option>
                <option value="admin" <?php if ($role_filter == 'admin') echo 'selected'; ?>>Administrators</option>
                <option value="tripmaker" <?php if ($role_filter == 'tripmaker') echo 'selected'; ?>>TripMakers</option>
                <option value="student" <?php if ($role_filter == 'student') echo 'selected'; ?>>Students</option>
            </select>
            <button type="submit" class="btn-filter">
                <i class="ri-equalizer-line"></i> Sync View
            </button>
        </form>
    </div>

    <!-- User Grid -->
    <div class="user-grid">
        <?php if ($users->num_rows > 0): while ($u = $users->fetch_assoc()): 
            $role_class = 'card-role-' . $u['role'];
            $avatar_class = 'avatar-' . $u['role'];
            $badge_class = 'badge-' . $u['role'];
            $status_class = $u['is_active'] ? 'badge-active' : 'badge-suspended';
        ?>
            <div class="user-card <?php echo $role_class; ?>">
                <div>
                    <div class="user-info-section">
                        <div class="user-avatar <?php echo $avatar_class; ?>">
                            <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                        </div>
                        <div class="user-main-info">
                            <h3 class="user-name"><?php echo htmlspecialchars($u['name']); ?></h3>
                            <div class="user-email">
                                <i class="ri-mail-line"></i> <?php echo htmlspecialchars($u['email']); ?>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                        <div class="dropdown" style="position: relative;">
                            <span class="badge-pill <?php echo $badge_class; ?> role-trigger" onclick="toggleDropdown(this, event)">
                                <i class="<?php 
                                    echo $u['role'] == 'admin' ? 'ri-shield-user-line' : 
                                        ($u['role'] == 'tripmaker' ? 'ri-briefcase-line' : 'ri-graduation-cap-line'); 
                                ?>"></i>
                                <?php echo strtoupper($u['role']); ?>
                                <i class="ri-arrow-down-s-line"></i>
                            </span>
                            <div class="dropdown-menu">
                                <a href="?action=change_role&uid=<?php echo $u['id']; ?>&role=admin" class="dropdown-item">
                                    <i class="ri-shield-user-line"></i> Promote to Admin
                                </a>
                                <a href="?action=change_role&uid=<?php echo $u['id']; ?>&role=tripmaker" class="dropdown-item">
                                    <i class="ri-briefcase-line"></i> Set as TripMaker
                                </a>
                                <a href="?action=change_role&uid=<?php echo $u['id']; ?>&role=student" class="dropdown-item">
                                    <i class="ri-graduation-cap-line"></i> Demote to Student
                                </a>
                            </div>
                        </div>
                        <span class="badge-pill <?php echo $status_class; ?>">
                            <i class="ri-checkbox-circle-line"></i>
                            <?php echo $u['is_active'] ? 'Active' : 'Suspended'; ?>
                        </span>
                    </div>
                </div>

                <div class="card-meta">
                    <div class="meta-item">
                        <span class="meta-label">Joined</span>
                        <span class="meta-value"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></span>
                    </div>
                    <div class="meta-item" style="text-align: right;">
                        <span class="meta-label">Identifier</span>
                        <span class="meta-value">#USR-<?php echo str_pad($u['id'], 4, '0', STR_PAD_LEFT); ?></span>
                    </div>
                </div>

                <div class="card-actions">
                    <a href="?action=toggle_status&uid=<?php echo $u['id']; ?>" class="action-btn btn-suspend" 
                       title="<?php echo $u['is_active'] ? 'Suspend access' : 'Restore access'; ?>">
                        <i class="<?php echo $u['is_active'] ? 'ri-user-forbid-line' : 'ri-user-follow-line'; ?>"></i>
                        <?php echo $u['is_active'] ? 'Suspend' : 'Activate'; ?>
                    </a>
                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                    <a href="?action=delete&uid=<?php echo $u['id']; ?>" class="action-btn btn-delete" 
                       onclick="return confirm('Security Check: This will permanently purge all user data. Continue?')">
                        <i class="ri-delete-bin-6-line"></i> Delete
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; else: ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 100px; color: #94A3B8;">
                <i class="ri-user-search-line" style="font-size: 4rem; display: block; margin-bottom: 20px;"></i>
                <h2 style="font-weight: 800; color: #64748B;">No Users Found</h2>
                <p>Try adjusting your search or filters to find what you're looking for.</p>
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