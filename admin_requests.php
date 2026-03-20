<?php
require_once 'admin_header.php';

// Handle Actions
if (isset($_GET['action'])) {
    $rid = intval($_GET['rid']);
    $action = $_GET['action'];

    if ($action == 'approve') {
        // Approve
        $conn->query("UPDATE enrollments SET status = 'approved' WHERE id = $rid");

        // Notify user
        $req = $conn->query("SELECT student_id, trip_id FROM enrollments WHERE id = $rid")->fetch_assoc();
        $trip = $conn->query("SELECT title FROM trips WHERE id = " . $req['trip_id'])->fetch_assoc();
        $conn->query("INSERT INTO notifications (user_id, message, link) VALUES (" . $req['student_id'] . ", 'Your request to join \"" . $trip['title'] . "\" was approved by Admin!', 'trip.php?id=" . $req['trip_id'] . "')");

        logActivity($conn, $_SESSION['user_id'], "Approved request", "Request ID: $rid");
    } elseif ($action == 'reject') {
        $conn->query("UPDATE enrollments SET status = 'rejected' WHERE id = $rid");
        logActivity($conn, $_SESSION['user_id'], "Rejected request", "Request ID: $rid");
    }
    header("Location: admin_requests.php?msg=success");
    exit();
}

// Bulk Actions
if (isset($_POST['bulk_action'])) {
    $rids = $_POST['selected_requests'] ?? [];
    $action = $_POST['bulk_action'];
    if (!empty($rids)) {
        $ids = implode(',', array_map('intval', $rids));
        if ($action == 'approve') {
            $conn->query("UPDATE enrollments SET status = 'approved' WHERE id IN ($ids)");
            logActivity($conn, $_SESSION['user_id'], "Bulk approved requests", "IDs: $ids");
        } elseif ($action == 'reject') {
            $conn->query("UPDATE enrollments SET status = 'rejected' WHERE id IN ($ids)");
            logActivity($conn, $_SESSION['user_id'], "Bulk rejected requests", "IDs: $ids");
        }
    }
    header("Location: admin_requests.php?msg=bulk_success");
    exit();
}

$requests = $conn->query("SELECT e.*, u.name as student_name, u.email as student_email, t.title as trip_title 
                         FROM enrollments e 
                         JOIN users u ON e.student_id = u.id 
                         JOIN trips t ON e.trip_id = t.id 
                         ORDER BY e.request_date DESC");
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
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; overflow: hidden;
        background: radial-gradient(circle at 10% 20%, rgba(243, 232, 255, 0.5) 0%, transparent 40%),
                    radial-gradient(circle at 90% 80%, rgba(220, 252, 231, 0.5) 0%, transparent 40%),
                    radial-gradient(circle at 50% 50%, rgba(255, 237, 213, 0.4) 0%, transparent 60%);
    }

    .geo-bg::before {
        content: ''; position: absolute; width: 100%; height: 100%;
        background-image: linear-gradient(rgba(0,0,0,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0,0,0,0.03) 1px, transparent 1px);
        background-size: 50px 50px;
    }

    .dashboard-container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }

    .header-section { margin-bottom: 40px; display: flex; justify-content: space-between; align-items: flex-end; }
    .header-text h1 { font-size: 2.8rem; font-weight: 900; letter-spacing: -1.5px; color: #1E293B; margin: 0; }
    .header-text p { font-size: 1.1rem; color: #64748B; margin: 5px 0 0 0; font-weight: 500; }
    
    .live-badge {
        background: #DCFCE7; color: #16A34A; padding: 6px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 800;
        display: inline-flex; align-items: center; gap: 8px; text-transform: uppercase; margin-bottom: 12px;
    }
    .live-badge::before { content: ''; width: 8px; height: 8px; background: #16A34A; border-radius: 50%; display: inline-block; animation: pulse 2s infinite; }
    @keyframes pulse { 0% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.5); opacity: 0.5; } 100% { transform: scale(1); opacity: 1; } }

    /* Bulk Actions Bar */
    .bulk-bar {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(12px);
        padding: 15px 30px;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        display: none;
        align-items: center;
        gap: 20px;
        margin-bottom: 30px;
        border: 1px solid rgba(255, 255, 255, 0.6);
        position: sticky;
        top: 20px;
        z-index: 1000;
        animation: slideDown 0.4s cubic-bezier(0.1, 0.9, 0.2, 1);
    }

    .selected-indicator { font-weight: 800; color: #1E293B; font-size: 0.95rem; }

    .btn-bulk-action {
        padding: 10px 25px; border-radius: 12px; font-size: 0.9rem; font-weight: 800; border: none; cursor: pointer;
        transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-bulk-approve { background: #10B981; color: white; }
    .btn-bulk-approve:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3); }
    .btn-bulk-reject { background: #EF4444; color: white; }
    .btn-bulk-reject:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3); }

    /* Request Cards - Compact Version */
    .request-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 18px; }

    .request-card {
        background: white; border-radius: 20px; padding: 18px; border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 8px 25px rgba(0,0,0,0.03); transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        display: grid; grid-template-columns: auto 1fr; gap: 15px; position: relative;
    }
    .request-card:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(0,0,0,0.06); }

    .card-checkbox-area { display: flex; align-items: flex-start; padding-top: 3px; }
    .modern-checkbox { width: 18px; height: 18px; cursor: pointer; accent-color: #6366F1; border-radius: 4px; }

    .card-body { display: flex; flex-direction: column; gap: 15px; }

    .student-info { display: flex; align-items: center; gap: 12px; }
    .student-avatar {
        width: 38px; height: 38px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #6366f1, #a855f7); color: white; font-weight: 800; font-size: 1rem;
        box-shadow: 0 5px 15px rgba(99, 102, 241, 0.15);
    }
    .student-name { font-size: 0.95rem; font-weight: 850; color: #1E293B; margin-bottom: 0; display: block; line-height: 1.2; }
    .student-email { font-size: 0.75rem; color: #64748B; font-weight: 500; }

    .trip-info { padding: 10px 14px; background: #f0f9ff; border-radius: 14px; border: 1px solid #e0f2fe; }
    .trip-label { font-size: 0.65rem; font-weight: 800; color: #0ea5e9; text-transform: uppercase; margin-bottom: 4px; display: block; opacity: 0.8; }
    .trip-title { font-size: 0.85rem; font-weight: 750; color: #1e3a8a; display: block; margin-bottom: 2px; }
    .req-date { font-size: 0.7rem; color: #64748B; font-weight: 600; display: flex; align-items: center; gap: 4px; }

    .card-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 2px; }

    .status-badge {
        padding: 5px 12px; border-radius: 10px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .status-pending { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
    .status-approved { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }
    .status-rejected { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }

    .action-group { display: flex; gap: 8px; }
    .action-circle-btn {
        width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-size: 1rem; transition: all 0.25s ease; text-decoration: none; border: none;
    }
    .btn-approve { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }
    .btn-approve:hover { background: #16a34a; color: white; transform: scale(1.1); }
    .btn-reject { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }
    .btn-reject:hover { background: #dc2626; color: white; transform: scale(1.1); }
    .btn-reapprove {
        background: #f8fafc; color: #475569; padding: 0 12px; border-radius: 8px; height: 32px; font-size: 0.65rem; font-weight: 800;
        display: flex; align-items: center; border: 1px solid #e2e8f0;
    }
    .btn-reapprove:hover { background: #1e293b; color: white; border-color: #1e293b; }

    @keyframes slideDown { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    @media (max-width: 768px) {
        .header-section { flex-direction: column; align-items: flex-start; gap: 20px; }
        .request-grid { grid-template-columns: 1fr; }
        .request-card { grid-template-columns: 1fr; gap: 12px; }
    }
</style>

<div class="geo-bg"></div>

<div class="dashboard-container">
    <div class="header-section">
        <div class="header-text">
            <span class="live-badge">Live Monitoring</span>
            <h1>Enrollment Requests</h1>
            <p>Manage and process student applications</p>
        </div>
        <div style="background: #eff6ff; padding: 12px 24px; border-radius: 18px; border: 1px solid #dbeafe; display: flex; flex-direction: column; align-items: flex-end;">
            <span style="font-size: 0.65rem; font-weight: 800; color: #3b82f6; text-transform: uppercase; letter-spacing: 0.5px;">Live Queue</span>
            <div style="font-size: 2rem; font-weight: 900; color: #1e3a8a; line-height: 1;"><?php echo $requests->num_rows; ?> <span style="font-size: 0.8rem; font-weight: 700; color: #60a5fa; margin-left: -2px;">Requests</span></div>
        </div>
    </div>

    <form method="POST" id="requestForm">
        <!-- Floating Bulk Bar -->
        <div id="bulkBar" class="bulk-bar">
            <div style="display: flex; align-items: center; gap: 15px;">
                <input type="checkbox" onchange="toggleSelectAll(this)" class="modern-checkbox">
                <span id="selectedCount" class="selected-indicator">0 items selected</span>
            </div>
            <div style="flex: 1;"></div>
            <button type="submit" name="bulk_action" value="approve" class="btn-bulk-action btn-bulk-approve"><i class="ri-check-double-line"></i> Bulk Approve</button>
            <button type="submit" name="bulk_action" value="reject" class="btn-bulk-action btn-bulk-reject"><i class="ri-close-circle-line"></i> Bulk Reject</button>
        </div>

        <div class="request-grid">
            <?php if ($requests->num_rows > 0): while ($r = $requests->fetch_assoc()): 
                $status_class = 'status-' . $r['status'];
            ?>
                <div class="request-card">
                    <div class="card-checkbox-area">
                        <input type="checkbox" name="selected_requests[]" value="<?php echo $r['id']; ?>"
                                class="admin-checkbox modern-checkbox" onchange="updateBulkBar()">
                    </div>

                    <div class="card-body">
                        <div class="student-info">
                            <div class="student-avatar"><?php echo strtoupper(substr($r['student_name'], 0, 1)); ?></div>
                            <div>
                                <span class="student-name"><?php echo htmlspecialchars($r['student_name']); ?></span>
                                <span class="student-email"><?php echo htmlspecialchars($r['student_email']); ?></span>
                            </div>
                        </div>

                        <div class="trip-info">
                            <span class="trip-label">Requested Expedition</span>
                            <span class="trip-title"><?php echo htmlspecialchars($r['trip_title']); ?></span>
                            <span class="req-date"><i class="ri-time-line"></i> <?php echo date('M d, Y', strtotime($r['request_date'])); ?></span>
                        </div>

                        <div class="card-footer">
                            <span class="status-badge <?php echo $status_class; ?>"><?php echo $r['status']; ?></span>

                            <div class="action-group">
                                <?php if ($r['status'] == 'pending'): ?>
                                    <a href="?action=approve&rid=<?php echo $r['id']; ?>" class="action-circle-btn btn-approve" title="Approve Request">
                                        <i class="ri-check-line"></i>
                                    </a>
                                    <a href="?action=reject&rid=<?php echo $r['id']; ?>" class="action-circle-btn btn-reject" title="Reject Request">
                                        <i class="ri-close-line"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="?action=approve&rid=<?php echo $r['id']; ?>" class="btn-reapprove" title="Change Status to Approved">
                                        RE-APPROVE
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 120px; color: #94A3B8; background: white; border-radius: 30px; border: 2px dashed #E2E8F0;">
                    <i class="ri-inbox-archive-line" style="font-size: 4rem; display: block; margin-bottom: 25px; opacity: 0.3;"></i>
                    <h2 style="font-weight: 850; font-size: 1.4rem; color: #1E293B; margin-bottom: 10px;">Queue is Empty</h2>
                    <p style="font-size: 1rem; font-weight: 500;">No student applications pending at this moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
    function toggleSelectAll(master) {
        const checkboxes = document.querySelectorAll('.admin-checkbox');
        checkboxes.forEach(cb => cb.checked = master.checked);
        updateBulkBar();
    }

    function updateBulkBar() {
        const checkboxes = document.querySelectorAll('.admin-checkbox');
        const bulkBar = document.getElementById('bulkBar');
        const selectedCount = document.getElementById('selectedCount');
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;

        if (checkedCount > 0) {
            bulkBar.style.display = 'flex';
            selectedCount.textContent = `${checkedCount} items selected`;
        } else {
            bulkBar.style.display = 'none';
        }
    }
</script>

<?php require_once 'admin_footer.php'; ?>