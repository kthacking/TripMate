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
if ($search) $where .= " AND (name LIKE '%$search%' OR email LIKE '%$search%')";
if ($role_filter) $where .= " AND role = '$role_filter'";

$users = $conn->query("SELECT * FROM users WHERE $where ORDER BY created_at DESC");
?>

<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 25px; gap: 15px; flex-wrap: wrap;">
    <div style="flex: 1; min-width: 300px;">
        <form method="GET" style="display: flex; gap: 12px; align-items: center;">
            <div style="position: relative; flex: 1;">
                <i class="ri-search-2-line" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 1rem;"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or email..." class="form-control" style="padding-left: 40px; height: 42px; border-radius: 12px; border: 1px solid var(--admin-border); background: white; width: 100%; font-weight: 500; font-size: 0.9rem;">
            </div>
            <select name="role" class="form-select" style="width: 150px; height: 42px; border-radius: 12px; border: 1px solid var(--admin-border); background: white; font-weight: 600; color: #475569; font-size: 0.85rem;" onchange="this.form.submit()">
                <option value="">All Roles</option>
                <option value="admin" <?php if($role_filter == 'admin') echo 'selected'; ?>>Admin</option>
                <option value="tripmaker" <?php if($role_filter == 'tripmaker') echo 'selected'; ?>>TripMaker</option>
                <option value="student" <?php if($role_filter == 'student') echo 'selected'; ?>>Student</option>
            </select>
            <button type="submit" class="btn-premium" style="height: 42px; padding: 0 20px; font-size: 0.85rem;">
                <i class="ri-filter-3-line"></i> Filter
            </button>
        </form>
    </div>
    <a href="admin_reg.php" class="btn-premium" style="background: white; color: var(--admin-primary); border: 2px solid var(--admin-primary); box-shadow: none; height: 42px; padding: 0 20px; font-size: 0.85rem;">
        <i class="ri-user-add-line"></i> New Admin
    </a>
</div>

<div class="admin-card" style="padding: 0; overflow: hidden;">
    <table class="admin-table" style="width: 100%;">
        <thead>
            <tr>
                <th style="width: 50px;"><input type="checkbox" onchange="toggleSelectAll(this)" style="width: 18px; height: 18px;"></th>
                <th>Users Details</th>
                <th>Account Role</th>
                <th>Status</th>
                <th>Joined Date</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($u = $users->fetch_assoc()): ?>
            <tr style="transition: background 0.2s;">
                <td><input type="checkbox" class="admin-checkbox" onchange="updateBulkBar()" style="width: 18px; height: 18px;"></td>
                <td>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 36px; height: 36px; border-radius: 10px; background: #F1F5F9; color: var(--admin-primary); display: flex; align-items: center; justify-content: center; font-weight: 800; border: 1px solid #E2E8F0; font-size: 0.85rem;">
                            <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                        </div>
                        <div>
                            <div style="font-weight: 800; color: var(--admin-text-main); font-size: 0.92rem;"><?php echo htmlspecialchars($u['name']); ?></div>
                            <div style="font-size: 0.78rem; color: var(--admin-text-muted); font-weight: 500;"><?php echo htmlspecialchars($u['email']); ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="dropdown" style="position: relative;">
                        <button class="admin-badge" style="background: #F1F5F9; color: #475569; border: 1px solid #E2E8F0; cursor: pointer; display: flex; align-items: center; gap: 6px;" onclick="this.nextElementSibling.classList.toggle('show')">
                            <?php echo strtoupper($u['role']); ?> <i class="ri-arrow-down-s-line"></i>
                        </button>
                        <div class="dropdown-menu" style="display: none; position: absolute; background: white; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-radius: 12px; z-index: 100; min-width: 150px; padding: 8px; border: 1px solid #E2E8F0; left: 0; top: 100%; margin-top: 5px;">
                            <a href="?action=change_role&uid=<?php echo $u['id']; ?>&role=admin" class="dropdown-item" style="display: flex; align-items: center; gap: 8px; padding: 10px 12px; font-size: 0.85rem; text-decoration: none; color: #475569; font-weight: 600; border-radius: 8px;">
                                <i class="ri-shield-user-line"></i> Admin
                            </a>
                            <a href="?action=change_role&uid=<?php echo $u['id']; ?>&role=tripmaker" class="dropdown-item" style="display: flex; align-items: center; gap: 8px; padding: 10px 12px; font-size: 0.85rem; text-decoration: none; color: #475569; font-weight: 600; border-radius: 8px;">
                                <i class="ri-briefcase-line"></i> TripMaker
                            </a>
                            <a href="?action=change_role&uid=<?php echo $u['id']; ?>&role=student" class="dropdown-item" style="display: flex; align-items: center; gap: 8px; padding: 10px 12px; font-size: 0.85rem; text-decoration: none; color: #475569; font-weight: 600; border-radius: 8px;">
                                <i class="ri-graduation-cap-line"></i> Student
                            </a>
                        </div>
                    </div>
                </td>
                <td>
                    <?php if($u['is_active']): ?>
                        <span class="admin-badge" style="background: #DCFCE7; color: #166534; border: 1px solid #BBF7D0;">Active</span>
                    <?php else: ?>
                        <span class="admin-badge" style="background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA;">Suspended</span>
                    <?php endif; ?>
                </td>
                <td><span style="font-size: 0.9rem; color: #64748B; font-weight: 600;"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></span></td>
                <td>
                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                        <a href="?action=toggle_status&uid=<?php echo $u['id']; ?>" class="btn-icon" title="<?php echo $u['is_active'] ? 'Suspend User' : 'Restore User'; ?>" style="background: #F8FAFC; color: #475569; border: 1px solid #E2E8F0; width: 38px; height: 38px;">
                            <i class="<?php echo $u['is_active'] ? 'ri-user-forbid-line' : 'ri-user-follow-line'; ?>" style="font-size: 1.1rem;"></i>
                        </a>
                        <a href="?action=delete&uid=<?php echo $u['id']; ?>" class="btn-icon" onclick="return confirm('Danger: Table records for this user will be purged. Proceed?')" style="background: #FFF1F2; color: #E11D48; border: 1px solid #FECDD3; width: 38px; height: 38px;">
                            <i class="ri-delete-bin-6-line" style="font-size: 1.1rem;"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="bulkBar" class="bulk-actions" style="display: none; position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); z-index: 2000; box-shadow: var(--shadow-lg);">
    <span id="selectedCount" style="margin-right: 20px; font-weight: 600;">0 items selected</span>
    <button class="btn btn-mini btn-mini-reject" style="background: #EF4444; color: white; border: none; padding: 8px 16px; border-radius: 8px;">Suspend Selected</button>
    <button class="btn btn-mini" style="background: #F3F4F6; color: #374151; padding: 8px 16px; border-radius: 8px;">Change Role</button>
    <button class="btn btn-mini" onclick="this.parentElement.style.display='none'" style="background: transparent; color: #6B7280; padding: 8px; border: none;"><i class="ri-close-line"></i></button>
</div>

<script>
// Toggle Dropdowns
document.querySelectorAll('.dropdown button').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        document.querySelectorAll('.dropdown-menu').forEach(m => {
            if(m !== this.nextElementSibling) m.classList.remove('show');
        });
    });
});
document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
});
</script>
<style>
.dropdown-menu.show { display: block !important; }
.btn-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s;
}
.btn-icon:hover { opacity: 0.8; transform: scale(1.1); }
</style>

<?php require_once 'admin_footer.php'; ?>
