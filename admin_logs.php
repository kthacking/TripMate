<?php
require_once 'admin_header.php';

// Action badge helper
function getLogBadge($action) {
    $action = strtolower($action);
    if (strpos($action, 'approve') !== false || strpos($action, 'create') !== false || strpos($action, 'add') !== false) return 'badge-green';
    if (strpos($action, 'delete') !== false || strpos($action, 'remove') !== false || strpos($action, 'clear') !== false) return 'badge-red';
    if (strpos($action, 'update') !== false || strpos($action, 'modify') !== false || strpos($action, 'edit') !== false) return 'badge-orange';
    return 'badge-gray';
}

// Export Logic (Simplistic CSV)
if (isset($_GET['action']) && $_GET['action'] == 'export') {
    // Clear the output buffer to remove any HTML sent by admin_header.php
    if (ob_get_length()) ob_end_clean();
    
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

<style>
    /* Hide Default Navbar and Header */
    .admin-nav, .admin-content > h2, .admin-content > div:first-child {
        display: none !important;
    }

    body {
        background-color: #f8fafc;
        position: relative;
        overflow-x: hidden;
        color: #1e293b;
    }

    /* Neutral Geometric Background */
    .geo-bg {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; overflow: hidden;
        background: radial-gradient(circle at 10% 20%, rgba(203, 213, 225, 0.3) 0%, transparent 40%),
                    radial-gradient(circle at 90% 80%, rgba(203, 213, 225, 0.3) 0%, transparent 40%),
                    radial-gradient(circle at 50% 50%, rgba(203, 213, 225, 0.2) 0%, transparent 60%);
    }

    .geo-bg::before {
        content: ''; position: absolute; width: 100%; height: 100%;
        background-image: linear-gradient(rgba(0,0,0,0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(0,0,0,0.02) 1px, transparent 1px);
        background-size: 40px 40px;
    }

    .logs-container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }

    .header-section { margin-bottom: 35px; }
    .header-section h1 { font-size: 2.6rem; font-weight: 900; letter-spacing: -1.2px; color: #1e293b; margin: 0; }
    .header-section p { font-size: 1.1rem; color: #64748b; margin: 6px 0 0 0; font-weight: 500; }
    
    .live-badge {
        background: #dcfce7; color: #16a34a; padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 800;
        display: inline-flex; align-items: center; gap: 6px; text-transform: uppercase; margin-bottom: 12px; vertical-align: middle;
    }
    .live-badge::before { content: ''; width: 6px; height: 6px; background: #16a34a; border-radius: 50%; display: inline-block; animation: pulse 2s infinite; }
    @keyframes pulse { 0% { opacity: 1; transform: scale(1); } 50% { opacity: 0.4; transform: scale(1.2); } 100% { opacity: 1; transform: scale(1); } }

    /* Search & Actions */
    .action-bar { display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
    .search-box { position: relative; flex: 1; min-width: 300px; }
    .search-box input {
        width: 100%; height: 50px; padding-left: 48px; border-radius: 12px; border: 1px solid #e2e8f0;
        background: white; font-weight: 500; font-size: 0.95rem; transition: all 0.3s ease;
    }
    .search-box input:focus { border-color: #94a3b8; outline: none; box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.1); }
    .search-box i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.1rem; }

    .btn-search { background: #1e293b; color: white; border: none; padding: 0 28px; height: 50px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; }
    .btn-search:hover { background: #0f172a; transform: translateY(-1px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); }

    .btn-neutral { background: white; color: #475569; border: 1px solid #e2e8f0; padding: 0 24px; height: 50px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .btn-neutral:hover { background: #f8fafc; border-color: #cbd5e1; }

    .btn-danger-outline { background: transparent; color: #ef4444; border: 2px solid #fee2e2; padding: 0 24px; height: 50px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .btn-danger-outline:hover { background: #fef2f2; border-color: #ef4444; }

    /* Table Design */
    .logs-list-header {
        display: grid; grid-template-columns: 180px 200px 250px 1fr; padding: 0 20px 15px;
        font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.5px;
    }

    .log-card {
        display: grid; grid-template-columns: 180px 200px 250px 1fr; align-items: center;
        background: white; border-radius: 12px; padding: 18px 20px; margin-bottom: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid transparent; text-decoration: none;
    }
    .log-card:hover {
        transform: translateY(-2px); background: #f1f5f9; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
        border-color: #e2e8f0;
    }

    .log-time { font-size: 0.85rem; color: #64748b; font-weight: 600; display: flex; align-items: center; gap: 10px; }
    .log-time i { font-size: 1.1rem; color: #cbd5e1; }

    .log-subject { display: flex; align-items: center; gap: 12px; }
    .log-avatar {
        width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
        background: #f1f5f9; color: #475569; font-weight: 800; font-size: 0.85rem; border: 1px solid #e2e8f0;
    }
    .log-username { font-weight: 750; font-size: 0.95rem; color: #1e293b; }

    .log-badge {
        padding: 6px 14px; border-radius: 10px; font-size: 0.75rem; font-weight: 800; display: inline-block;
        text-transform: capitalize; width: fit-content;
    }
    .badge-green { background: #dcfce7; color: #15803d; }
    .badge-red { background: #fee2e2; color: #b91c1c; }
    .badge-orange { background: #fef3c7; color: #b45309; }
    .badge-gray { background: #f1f5f9; color: #475569; }

    .log-details {
        font-size: 0.85rem; color: #64748b; font-weight: 500;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        font-family: 'JetBrains Mono', 'Courier New', monospace;
    }

    @media (max-width: 900px) {
        .logs-list-header { display: none; }
        .log-card { grid-template-columns: 1fr; gap: 15px; padding: 20px; }
        .log-time { order: 1; }
        .log-subject { order: 2; }
        .log-badge { order: 3; }
        .log-details { order: 4; white-space: normal; }
    }

    .no-logs {
        text-align: center; padding: 80px 0; background: white; border-radius: 20px;
        border: 2px dashed #e2e8f0; color: #94a3b8;
    }
    .no-logs i { font-size: 3.5rem; display: block; margin-bottom: 20px; }
    .no-logs p { font-size: 1.1rem; font-weight: 700; }
</style>

<div class="geo-bg"></div>

<div class="logs-container">
    <div class="header-section">
        <span class="live-badge">Live Monitoring</span>
        <h1>System Audit Trails</h1>
        <p>Monitor all system activity logs</p>
    </div>

    <div class="action-bar">
        <form method="GET" style="display: flex; gap: 12px; flex: 1;">
            <div class="search-box">
                <i class="ri-search-eye-line"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search system audit trails...">
            </div>
            <button type="submit" class="btn-search">
                <i class="ri-zoom-in-line"></i> Search
            </button>
        </form>
        <div style="display: flex; gap: 12px;">
            <a href="?action=export" class="btn-neutral">
                <i class="ri-download-cloud-2-line"></i> Export Data
            </a>
            <a href="?action=clear" class="btn-danger-outline" onclick="return confirm('Security Protocol: Absolute wipe of all system audit logs? This is irreversible.')">
                <i class="ri-delete-bin-7-line"></i> Purge History
            </a>
        </div>
    </div>

    <div class="logs-list">
        <div class="logs-list-header">
            <span>Chronology</span>
            <span>Subject</span>
            <span>Operation</span>
            <span>Full Context</span>
        </div>

        <?php while($l = $logs->fetch_assoc()): ?>
            <div class="log-card">
                <div class="log-time">
                    <i class="ri-time-line"></i>
                    <?php echo date('M d, H:i:s', strtotime($l['created_at'])); ?>
                </div>
                <div class="log-subject">
                    <div class="log-avatar">
                        <?php echo $l['user_name'] ? strtoupper(substr($l['user_name'], 0, 1)) : 'S'; ?>
                    </div>
                    <span class="log-username"><?php echo htmlspecialchars($l['user_name'] ?? 'SYSTEM'); ?></span>
                </div>
                <div>
                    <span class="log-badge <?php echo getLogBadge($l['action']); ?>">
                        <?php echo htmlspecialchars($l['action']); ?>
                    </span>
                </div>
                <div class="log-details" title="<?php echo htmlspecialchars($l['details']); ?>">
                    <?php echo htmlspecialchars($l['details']); ?>
                </div>
            </div>
        <?php endwhile; if($logs->num_rows == 0): ?>
            <div class="no-logs">
                <i class="ri-folder-history-line"></i>
                <p>No audit trails detected.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

