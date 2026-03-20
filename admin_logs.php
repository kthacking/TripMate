<?php
require_once 'admin_header.php';

// Export Logic (Simplistic CSV)
if (isset($_GET['action']) && $_GET['action'] == 'export') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="tripmate_logs_'.date('Ymd').'.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'User', 'Action', 'Details', 'Date']);
    $logs_res = $conn->query("SELECT l.*, u.name FROM activity_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC");
    while($row = $logs_res->fetch_assoc()) fputcsv($output, [$row['id'], $row['name'] ?? 'System', $row['action'], $row['details'], $row['created_at']]);
    fclose($output);
    exit();
}

// Clear Logs (DANGEROUS but user asked for absolute power)
if (isset($_GET['action']) && $_GET['action'] == 'clear' && $_SESSION['role'] == 'admin') {
    $conn->query("TRUNCATE TABLE activity_logs");
    logActivity($conn, $_SESSION['user_id'], "Cleared activity logs", "Permanent deletion of log history");
    header("Location: admin_logs.php?msg=cleared");
    exit();
}

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where = "1=1";
if ($search) $where .= " AND (l.action LIKE '%$search%' OR u.name LIKE '%$search%' OR l.details LIKE '%$search%')";

$logs = $conn->query("SELECT l.*, u.name as user_name FROM activity_logs l 
                    LEFT JOIN users u ON l.user_id = u.id 
                    WHERE $where 
                    ORDER BY l.created_at DESC LIMIT 100");
?>

<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; gap: 20px; flex-wrap: wrap;">
    <div style="flex: 1; min-width: 300px;">
        <form method="GET" style="display: flex; gap: 15px; align-items: center;">
            <div style="position: relative; flex: 1;">
                <i class="ri-search-eye-line" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 1.1rem;"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search system audit trails..." class="form-control" style="padding-left: 48px; height: 50px; border-radius: 14px; border: 1px solid var(--admin-border); background: white; width: 100%; font-weight: 500;">
            </div>
            <button type="submit" class="btn-premium" style="height: 50px; padding: 0 25px;">
                <i class="ri-search-2-line"></i> Search
            </button>
        </form>
    </div>
    <div style="display: flex; gap: 12px;">
        <a href="?action=export" class="btn-premium" style="background: white; color: #475569; border: 2px solid #E2E8F0; box-shadow: none;">
            <i class="ri-download-cloud-2-line"></i> Export Data
        </a>
        <a href="?action=clear" class="btn-premium" style="background: #FFF1F2; color: #E11D48; border: 2px solid #FECDD3; box-shadow: none;" onclick="return confirm('Security Protocol: Absolute wipe of all system audit logs? This is irreversible.')">
            <i class="ri-delete-bin-7-line"></i> Purge History
        </a>
    </div>
</div>

<div class="admin-card" style="padding: 0; overflow: hidden;">
    <table class="admin-table" style="width: 100%;">
        <thead>
            <tr>
                <th style="width: 180px;">Chronology</th>
                <th>Subject</th>
                <th>Operation</th>
                <th>Full Context</th>
            </tr>
        </thead>
        <tbody>
            <?php while($l = $logs->fetch_assoc()): ?>
            <tr style="transition: background 0.2s;">
                <td>
                    <div style="font-size: 0.85rem; color: #64748B; font-weight: 700; white-space: nowrap; display: flex; align-items: center; gap: 8px;">
                        <i class="ri-time-line" style="color: #94A3B8;"></i>
                        <?php echo date('M d, H:i:s', strtotime($l['created_at'])); ?>
                    </div>
                </td>
                <td>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 32px; height: 32px; background: #F1F5F9; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #475569; font-size: 0.8rem; border: 1px solid #E2E8F0;">
                            <?php echo $l['user_name'] ? strtoupper(substr($l['user_name'], 0, 1)) : 'S'; ?>
                        </div>
                        <span style="font-weight: 750; font-size: 0.95rem; color: var(--admin-text-main);"><?php echo htmlspecialchars($l['user_name'] ?? 'SYSTEM'); ?></span>
                    </div>
                </td>
                <td>
                    <span style="font-weight: 800; color: var(--admin-text-main); font-size: 0.9rem;"><?php echo htmlspecialchars($l['action']); ?></span>
                </td>
                <td>
                    <div style="font-size: 0.85rem; color: #64748B; max-width: 500px; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-family: 'JetBrains Mono', 'Courier New', monospace;" title="<?php echo htmlspecialchars($l['details']); ?>">
                        <?php echo htmlspecialchars($l['details']); ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; if($logs->num_rows == 0): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 100px 0;">
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 15px; opacity: 0.5;">
                            <i class="ri-folder-history-line" style="font-size: 3rem;"></i>
                            <span style="font-weight: 700; font-size: 1.1rem;">No audit trails detected.</span>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'admin_footer.php'; ?>
