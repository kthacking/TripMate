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

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <form method="GET" style="display: flex; gap: 10px;">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search logs..." class="form-control" style="border-radius: 10px; width: 300px;">
        <button type="submit" class="btn btn-primary" style="border-radius: 10px;">Search</button>
    </form>
    <div style="display: flex; gap: 12px;">
        <a href="?action=export" class="btn btn-outline" style="border-radius: 10px;"><i class="ri-download-line"></i> Export CSV</a>
        <a href="?action=clear" class="btn btn-outline" style="color: #ef4444; border-color: #fecaca; border-radius: 10px;" onclick="return confirm('WARNING: This will permanently delete ALL logs. Proceed?')"><i class="ri-delete-bin-line"></i> Clear Logs</a>
    </div>
</div>

<div style="background: white; border-radius: 16px; box-shadow: var(--shadow-sm); overflow: hidden;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Time</th>
                <th>User / Origin</th>
                <th>Action Performed</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            <?php while($l = $logs->fetch_assoc()): ?>
            <tr>
                <td><span style="font-size: 0.8rem; color: #9CA3AF; white-space: nowrap;"><?php echo date('M d, H:i:s', strtotime($l['created_at'])); ?></span></td>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 28px; height: 28px; background: #F3F4F6; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #6B7280; font-size: 0.7rem;">
                            <?php echo $l['user_name'] ? strtoupper(substr($l['user_name'], 0, 1)) : 'S'; ?>
                        </div>
                        <span style="font-weight: 600; font-size: 0.85rem;"><?php echo htmlspecialchars($l['user_name'] ?? 'SYSTEM'); ?></span>
                    </div>
                </td>
                <td>
                    <span style="font-weight: 700; color: #111827; font-size: 0.85rem;"><?php echo htmlspecialchars($l['action']); ?></span>
                </td>
                <td>
                    <div style="font-size: 0.8rem; color: #6B7280; max-width: 400px; line-height: 1.4; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($l['details']); ?>">
                        <?php echo htmlspecialchars($l['details']); ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; if($logs->num_rows == 0): ?>
                <tr><td colspan="4" style="text-align: center; padding: 40px; color: #9CA3AF;">No log entries found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'admin_footer.php'; ?>
