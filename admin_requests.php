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

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h3 style="color: #111827;">System-Wide Join Requests</h3>
    <div style="display: flex; gap: 10px;">
        <span class="admin-badge" style="background: #FFFBEB; color: #D97706; padding: 8px 16px; font-size: 0.85rem;">
            Total Managed: <?php echo $requests->num_rows; ?>
        </span>
    </div>
</div>

<form method="POST" id="requestForm">
    <div id="bulkBar" class="bulk-actions" style="display: none; margin-bottom: 20px;">
        <span id="selectedCount" style="margin-right: 20px; font-weight: 600; color: #4338CA;">0 items selected</span>
        <button type="submit" name="bulk_action" value="approve" class="btn btn-mini"
            style="background: #10B981; color: white; border: none; padding: 8px 16px; border-radius: 8px;">Approve
            All</button>
        <button type="submit" name="bulk_action" value="reject" class="btn btn-mini"
            style="background: #EF4444; color: white; border: none; padding: 8px 16px; border-radius: 8px;">Reject
            All</button>
    </div>

    <div style="background: white; border-radius: 16px; box-shadow: var(--shadow-sm); overflow: hidden;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th><input type="checkbox" onchange="toggleSelectAll(this)"></th>
                    <th>Student</th>
                    <th>Trip Destination</th>
                    <th>Date Requested</th>
                    <th>Current Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($r = $requests->fetch_assoc()): ?>
                    <tr>
                        <td><input type="checkbox" name="selected_requests[]" value="<?php echo $r['id']; ?>"
                                class="admin-checkbox" onchange="updateBulkBar()"></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div
                                    style="width: 36px; height: 36px; border-radius: 50%; background: #F3F4F6; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #6B7280; font-size: 0.8rem;">
                                    <?php echo strtoupper(substr($r['student_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: #111827; font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($r['student_name']); ?></div>
                                    <div style="font-size: 0.75rem; color: #6B7280;">
                                        <?php echo htmlspecialchars($r['student_email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #374151;"><?php echo htmlspecialchars($r['trip_title']); ?>
                            </div>
                        </td>
                        <td><span
                                style="font-size: 0.8rem; color: #9CA3AF;"><?php echo date('M d, Y H:i', strtotime($r['request_date'])); ?></span>
                        </td>
                        <td>
                            <?php
                            $st = $r['status'];
                            $c = $st == 'pending' ? ['#FFFBEB', '#D97706'] : ($st == 'approved' ? ['#D1FAE5', '#065F46'] : ['#FEE2E2', '#991B1B']);
                            ?>
                            <span class="admin-badge"
                                style="background: <?php echo $c[0]; ?>; color: <?php echo $c[1]; ?>;">
                                <?php echo strtoupper($st); ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <?php if ($r['status'] == 'pending'): ?>
                                    <a href="?action=approve&rid=<?php echo $r['id']; ?>" class="btn-icon"
                                        style="background: #D1FAE5; color: #065F46;" title="Approve"><i
                                            class="ri-check-line"></i></a>
                                    <a href="?action=reject&rid=<?php echo $r['id']; ?>" class="btn-icon"
                                        style="background: #FEE2E2; color: #B91C1C;" title="Reject"><i
                                            class="ri-close-line"></i></a>
                                <?php else: ?>
                                    <a href="?action=approve&rid=<?php echo $r['id']; ?>" class="btn-icon"
                                        style="background: #F3F4F6; color: #6B7280; font-size: 0.7rem; width: auto; padding: 0 8px;">Re-Approve</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</form>

<?php require_once 'admin_footer.php'; ?>