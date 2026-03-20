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
        background-color: #F8FAFC;
        color: #0F172A;
    }

    .dashboard-container {
        max-width: 1100px;
        margin: 0 auto;
        padding: 60px 20px;
    }

    .page-header {
        margin-bottom: 48px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .header-text h1 {
        font-size: 2rem;
        font-weight: 700;
        letter-spacing: -0.025em;
        color: #0F172A;
        margin: 0 0 8px 0;
    }

    .header-text p {
        font-size: 1.05rem;
        color: #64748B;
        margin: 0;
    }

    .total-managed-badge {
        background: #F1F5F9;
        color: #475569;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    /* Bulk Actions Bar (Minimal) */
    .bulk-bar {
        background: white;
        padding: 12px 20px;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        display: none;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;
        border: 1px solid #E2E8F0;
        position: sticky;
        top: 20px;
        z-index: 100;
        animation: slideDown 0.3s ease;
    }

    .bulk-label {
        font-weight: 600;
        color: #334155;
        font-size: 0.875rem;
    }

    .btn-bulk {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 0.8125rem;
        font-weight: 600;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .btn-bulk-approve { background: #0F172A; color: white; }
    .btn-bulk-approve:hover { background: #1E293B; }
    .btn-bulk-reject { background: #FFFFFF; color: #DC2626; border-color: #FECACA; }
    .btn-bulk-reject:hover { background: #FEF2F2; }

    /* Request Grid */
    .request-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(480px, 1fr));
        gap: 20px;
    }

    .request-card {
        background: #FFFFFF;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #E2E8F0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        transition: border-color 0.2s, box-shadow 0.2s;
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 20px;
        align-items: center;
    }

    .request-card:hover {
        border-color: #CBD5E1;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .checkbox-wrapper {
        display: flex;
        align-items: center;
    }

    .modern-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #0F172A;
    }

    .student-info-box {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .student-initial {
        width: 42px;
        height: 42px;
        background: #F8FAFC;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1rem;
        color: #475569;
        border: 1px solid #E2E8F0;
    }

    .student-details h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #0F172A;
        margin: 0 0 2px 0;
    }

    .student-details p {
        font-size: 0.8125rem;
        color: #64748B;
        margin: 0;
    }

    .trip-info-box {
        border-left: 1px solid #F1F5F9;
        padding-left: 20px;
    }

    .trip-name {
        font-size: 0.9375rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 4px;
        display: block;
    }

    .requested-on {
        font-size: 0.75rem;
        color: #94A3B8;
        font-weight: 500;
    }

    .actions-and-status {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 12px;
    }

    .status-badge {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    .status-pending { background: #FFF7ED; color: #C2410C; }
    .status-approved { background: #F0FDF4; color: #15803D; }
    .status-rejected { background: #FEF2F2; color: #B91C1C; }

    .btn-group {
        display: flex;
        gap: 8px;
    }

    .action-btn-minimal {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 8px;
        text-decoration: none;
        transition: background 0.2s;
        font-size: 1.1rem;
    }

    .btn-approve-min { color: #10B981; background: #F0FDF4; border: 1px solid #DCFCE7; }
    .btn-approve-min:hover { background: #10B981; color: white; border-color: #10B981; }

    .btn-reject-min { color: #EF4444; background: #FEF2F2; border: 1px solid #FEE2E2; }
    .btn-reject-min:hover { background: #EF4444; color: white; border-color: #EF4444; }

    .btn-reapprove-min {
        width: auto;
        padding: 0 10px;
        font-size: 0.7rem;
        font-weight: 700;
        background: #F8FAFC;
        color: #64748B;
        border: 1px solid #E2E8F0;
    }

    .btn-reapprove-min:hover {
        background: #0F172A;
        color: white;
        border-color: #0F172A;
    }

    @keyframes slideDown {
        from { transform: translateY(-10px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    @media (max-width: 1024px) {
        .request-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 768px) {
        .dashboard-container { padding: 40px 16px; }
        .page-header { flex-direction: column; align-items: flex-start; gap: 16px; margin-bottom: 32px; }
        .request-card { grid-template-columns: 1fr; gap: 16px; }
        .trip-info-box { border-left: none; padding-left: 0; margin-top: -8px; }
        .actions-and-status { flex-direction: row; justify-content: space-between; align-items: center; border-top: 1px solid #F1F5F9; padding-top: 16px; }
    }
</style>

<div class="dashboard-container">
    <div class="page-header">
        <div class="header-text">
            <h1>Enrollment Requests</h1>
            <p>Manage and process student applications</p>
        </div>
        <div class="total-managed-badge">
            <i class="ri-history-line"></i>
            Total Managed: <?php echo $requests->num_rows; ?>
        </div>
    </div>

    <form method="POST" id="requestForm">
        <!-- Bulk Bar (Minimal) -->
        <div id="bulkBar" class="bulk-bar">
            <div class="checkbox-wrapper">
                <input type="checkbox" onchange="toggleSelectAll(this)" class="modern-checkbox">
            </div>
            <span id="selectedCount" class="bulk-label">0 items selected</span>
            <div style="flex: 1;"></div>
            <button type="submit" name="bulk_action" value="approve" class="btn-bulk btn-bulk-approve">Approve</button>
            <button type="submit" name="bulk_action" value="reject" class="btn-bulk btn-bulk-reject">Reject</button>
        </div>

        <div class="request-grid">
            <?php if ($requests->num_rows > 0): while ($r = $requests->fetch_assoc()): 
                $status_class = 'status-' . $r['status'];
            ?>
                <div class="request-card">
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" name="selected_requests[]" value="<?php echo $r['id']; ?>"
                                    class="admin-checkbox modern-checkbox" onchange="updateBulkBar()">
                        </div>

                        <div class="student-info-box">
                            <div class="student-initial">
                                <?php echo strtoupper(substr($r['student_name'], 0, 1)); ?>
                            </div>
                            <div class="student-details">
                                <h3><?php echo htmlspecialchars($r['student_name']); ?></h3>
                                <p><?php echo htmlspecialchars($r['student_email']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="trip-info-box">
                        <span class="trip-name"><?php echo htmlspecialchars($r['trip_title']); ?></span>
                        <span class="requested-on">
                            Requested <?php echo date('M d, Y', strtotime($r['request_date'])); ?>
                        </span>
                    </div>

                    <div class="actions-and-status">
                        <span class="status-badge <?php echo $status_class; ?>">
                            <?php echo $r['status']; ?>
                        </span>

                        <div class="btn-group">
                            <?php if ($r['status'] == 'pending'): ?>
                                <a href="?action=approve&rid=<?php echo $r['id']; ?>" class="action-btn-minimal btn-approve-min" title="Approve">
                                    <i class="ri-check-line"></i>
                                </a>
                                <a href="?action=reject&rid=<?php echo $r['id']; ?>" class="action-btn-minimal btn-reject-min" title="Reject">
                                    <i class="ri-close-line"></i>
                                </a>
                            <?php else: ?>
                                <a href="?action=approve&rid=<?php echo $r['id']; ?>" class="action-btn-minimal btn-reapprove-min" title="Re-Approve">
                                    RE-APPROVE
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 100px; color: #64748B; background: white; border: 1px solid #E2E8F0; border-radius: 12px;">
                    <i class="ri-inbox-line" style="font-size: 2.5rem; display: block; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h2 style="font-weight: 650; font-size: 1.1rem; color: #334155;">No requests at the moment</h2>
                    <p style="font-size: 0.9rem;">New student applications will be listed here.</p>
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