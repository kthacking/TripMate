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

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div style="display: flex; gap: 15px;">
        <form method="GET" style="display: flex; gap: 10px;">
            <div style="position: relative;">
                <i class="ri-search-line" style="position: absolute; left: 12px; top: 11px; color: #9CA3AF;"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search users..." class="form-control" style="padding-left: 35px; width: 300px; border-radius: 10px;">
            </div>
            <select name="role" class="form-select" style="width: 150px; border-radius: 10px;" onchange="this.form.submit()">
                <option value="">All Roles</option>
                <option value="admin" <?php if($role_filter == 'admin') echo 'selected'; ?>>Admin</option>
                <option value="tripmaker" <?php if($role_filter == 'tripmaker') echo 'selected'; ?>>TripMaker</option>
                <option value="student" <?php if($role_filter == 'student') echo 'selected'; ?>>Student</option>
            </select>
            <button type="submit" class="btn btn-primary" style="border-radius: 10px;">Filter</button>
        </form>
    </div>
    <a href="admin_reg.php" class="btn btn-outline" style="border-radius: 10px;"><i class="ri-user-add-line"></i> New Admin</a>
</div>

<div style="background: white; border-radius: 16px; box-shadow: var(--shadow-sm); overflow: hidden;">
    <table class="admin-table">
        <thead>
            <tr>
                <th><input type="checkbox" onchange="toggleSelectAll(this)"></th>
                <th>User</th>
                <th>Role</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($u = $users->fetch_assoc()): ?>
            <tr>
                <td><input type="checkbox" class="admin-checkbox" onchange="updateBulkBar()"></td>
                <td>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: #EEF2FF; color: #4338CA; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                            <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                        </div>
                        <div>
                            <div style="font-weight: 700; color: #111827;"><?php echo htmlspecialchars($u['name']); ?></div>
                            <div style="font-size: 0.8rem; color: #6B7280;"><?php echo htmlspecialchars($u['email']); ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="dropdown" style="position: relative;">
                        <button class="admin-badge" style="background: #E0E7FF; color: #4338CA; border: none; cursor: pointer;" onclick="this.nextElementSibling.classList.toggle('show')">
                            <?php echo strtoupper($u['role']); ?> <i class="ri-arrow-down-s-line"></i>
                        </button>
                        <div class="dropdown-menu" style="display: none; position: absolute; background: white; box-shadow: var(--shadow-lg); border-radius: 8px; z-index: 100; min-width: 120px;">
                            <a href="?action=change_role&uid=<?php echo $u['id']; ?>&role=admin" class="dropdown-item" style="display: block; padding: 10px; font-size: 0.8rem; text-decoration: none; color: #333;">Admin</a>
                            <a href="?action=change_role&uid=<?php echo $u['id']; ?>&role=tripmaker" class="dropdown-item" style="display: block; padding: 10px; font-size: 0.8rem; text-decoration: none; color: #333;">TripMaker</a>
                            <a href="?action=change_role&uid=<?php echo $u['id']; ?>&role=student" class="dropdown-item" style="display: block; padding: 10px; font-size: 0.8rem; text-decoration: none; color: #333;">Student</a>
                        </div>
                    </div>
                </td>
                <td>
                    <?php if($u['is_active']): ?>
                        <span class="admin-badge" style="background: #D1FAE5; color: #065F46;">Active</span>
                    <?php else: ?>
                        <span class="admin-badge" style="background: #FEE2E2; color: #991B1B;">Suspended</span>
                    <?php endif; ?>
                </td>
                <td><span style="font-size: 0.85rem; color: #6B7280;"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></span></td>
                <td>
                    <div style="display: flex; gap: 8px;">
                        <a href="?action=toggle_status&uid=<?php echo $u['id']; ?>" class="btn-icon" title="<?php echo $u['is_active'] ? 'Suspend' : 'Activate'; ?>" style="background: #F3F4F6; color: #374151;">
                            <i class="<?php echo $u['is_active'] ? 'ri-user-forbid-line' : 'ri-user-received-line'; ?>"></i>
                        </a>
                        <a href="?action=delete&uid=<?php echo $u['id']; ?>" class="btn-icon danger" onclick="return confirm('Permanently delete this user?')" style="background: #FEE2E2; color: #EF4444;">
                            <i class="ri-delete-bin-line"></i>
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
