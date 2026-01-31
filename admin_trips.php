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
        $values = array_map(function($v) use ($conn) { return "'".$conn->real_escape_string($v)."'"; }, array_values($trip));
        $conn->query("INSERT INTO trips (".implode(",", $keys).") VALUES (".implode(",", $values).")");
        logActivity($conn, $_SESSION['user_id'], "Cloned trip", "Trip ID: $tid");
    }
    header("Location: admin_trips.php?msg=success");
    exit();
}

$trips = $conn->query("SELECT t.*, u.name as organizer FROM trips t JOIN users u ON t.created_by = u.id ORDER BY t.created_at DESC");
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h3 style="color: #111827;">All Platform Trips</h3>
    <a href="create_trip.php" class="btn btn-primary" style="border-radius: 10px;"><i class="ri-add-line"></i> Create New Trip</a>
</div>

<div style="background: white; border-radius: 16px; box-shadow: var(--shadow-sm); overflow: hidden;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Trip Details</th>
                <th>Organizer</th>
                <th>Cost</th>
                <th>Status</th>
                <th>Participants</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($t = $trips->fetch_assoc()): ?>
            <tr>
                <td>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <img src="<?php echo htmlspecialchars($t['image_url']); ?>" style="width: 50px; height: 50px; border-radius: 10px; object-fit: cover;">
                        <div>
                            <div style="font-weight: 700; color: #111827;"><?php echo htmlspecialchars($t['title']); ?></div>
                            <div style="font-size: 0.8rem; color: #6B7280;"><i class="ri-map-pin-line"></i> <?php echo htmlspecialchars($t['destination']); ?></div>
                        </div>
                    </div>
                </td>
                <td><span style="font-size: 0.9rem; font-weight: 600;"><?php echo htmlspecialchars($t['organizer']); ?></span></td>
                <td><span style="font-weight: 700; color: var(--admin-primary);">$<?php echo number_format($t['cost']); ?></span></td>
                <td>
                    <?php 
                        $colors = ['active' => ['#D1FAE5', '#065F46'], 'completed' => ['#EEF2FF', '#4338CA'], 'cancelled' => ['#FEE2E2', '#991B1B']];
                        $c = $colors[$t['status']] ?? ['#F3F4F6', '#374151'];
                    ?>
                    <span class="admin-badge" style="background: <?php echo $c[0]; ?>; color: <?php echo $c[1]; ?>;">
                        <?php echo strtoupper($t['status']); ?>
                    </span>
                </td>
                <td>
                    <?php 
                        $tid = $t['id'];
                        $p_count = $conn->query("SELECT COUNT(*) as c FROM enrollments WHERE trip_id = $tid AND status = 'approved'")->fetch_assoc()['c'];
                        $max = $t['max_participants'] ?: '∞';
                    ?>
                    <span style="font-size: 0.85rem; font-weight: 600;"><?php echo $p_count; ?> / <?php echo $max; ?></span>
                </td>
                <td>
                    <div style="display: flex; gap: 8px;">
                        <a href="trip.php?id=<?php echo $t['id']; ?>" class="btn-icon" title="View" target="_blank" style="background: #F3F4F6; color: #374151;"><i class="ri-eye-line"></i></a>
                        <a href="edit_trip.php?id=<?php echo $t['id']; ?>" class="btn-icon" title="Edit" style="background: #F3F4F6; color: #374151;"><i class="ri-edit-line"></i></a>
                        <a href="?action=clone&tid=<?php echo $t['id']; ?>" class="btn-icon" title="Clone" style="background: #F3F4F6; color: #374151;"><i class="ri-file-copy-line"></i></a>
                        
                        <div class="dropdown" style="position: relative;">
                            <button class="btn-icon" style="background: #F3F4F6; color: #374151; border: none; cursor: pointer;" onclick="this.nextElementSibling.classList.toggle('show')"><i class="ri-more-2-fill"></i></button>
                            <div class="dropdown-menu" style="display: none; position: absolute; right: 0; background: white; box-shadow: var(--shadow-lg); border-radius: 8px; z-index: 100; min-width: 150px;">
                                <a href="?action=toggle_status&tid=<?php echo $t['id']; ?>&status=active" class="dropdown-item" style="display: block; padding: 10px; font-size: 0.8rem; text-decoration: none; color: #333;">Mark Active</a>
                                <a href="?action=toggle_status&tid=<?php echo $t['id']; ?>&status=completed" class="dropdown-item" style="display: block; padding: 10px; font-size: 0.8rem; text-decoration: none; color: #333;">Mark Completed</a>
                                <a href="?action=toggle_status&tid=<?php echo $t['id']; ?>&status=cancelled" class="dropdown-item" style="display: block; padding: 10px; font-size: 0.8rem; text-decoration: none; color: #333;">Cancel Trip</a>
                                <hr style="margin: 5px 0; border: 0; border-top: 1px solid #EEE;">
                                <a href="?action=delete&tid=<?php echo $t['id']; ?>" class="dropdown-item" style="display: block; padding: 10px; font-size: 0.8rem; text-decoration: none; color: #EF4444;" onclick="return confirm('Delete this trip definitively?')">Delete Trip</a>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
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
.dropdown-item:hover { background: #F9FAFB; }
</style>

<?php require_once 'admin_footer.php'; ?>
