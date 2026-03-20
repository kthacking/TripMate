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

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h3 style="font-size: 1.25rem; font-weight: 850; color: var(--admin-text-main); letter-spacing: -0.6px;">Platform Trip Master List</h3>
    <a href="create_trip.php" class="btn-premium" style="padding: 10px 18px; font-size: 0.85rem;">
        <i class="ri-add-circle-line"></i> Launch New Expedition
    </a>
</div>

<div class="admin-card" style="padding: 0; overflow: hidden;">
    <table class="admin-table" style="width: 100%;">
        <thead>
            <tr>
                <th>Trip Particulars</th>
                <th>Lead Organizer</th>
                <th>Investment</th>
                <th>Status</th>
                <th>Manifest</th>
                <th style="text-align: right;">Operations</th>
            </tr>
        </thead>
        <tbody>
            <?php while($t = $trips->fetch_assoc()): ?>
            <tr style="transition: background 0.2s;">
                <td style="padding: 15px 20px;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <div style="position: relative;">
                            <img src="<?php echo htmlspecialchars($t['image_url']); ?>" style="width: 50px; height: 50px; border-radius: 12px; object-fit: cover; box-shadow: 0 3px 8px rgba(0,0,0,0.08); border: 2px solid white;">
                            <div style="position: absolute; bottom: -4px; right: -4px; width: 20px; height: 20px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                <i class="ri-earth-line" style="font-size: 0.7rem; color: var(--admin-primary);"></i>
                            </div>
                        </div>
                        <div>
                            <div style="font-weight: 800; color: var(--admin-text-main); font-size: 0.95rem; letter-spacing: -0.2px;"><?php echo htmlspecialchars($t['title']); ?></div>
                            <div style="font-size: 0.8rem; color: var(--admin-text-muted); font-weight: 600; margin-top: 1px;">
                                <i class="ri-map-pin-2-fill" style="color: #6366F1; font-size: 0.85rem;"></i> <?php echo htmlspecialchars($t['destination']); ?>
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 28px; height: 28px; background: #EEF2FF; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.75rem; color: var(--admin-primary);">
                             <?php echo strtoupper(substr($t['organizer'], 0, 1)); ?>
                        </div>
                        <span style="font-size: 0.9rem; font-weight: 700; color: #475569;"><?php echo htmlspecialchars($t['organizer']); ?></span>
                    </div>
                </td>
                <td><span style="font-weight: 850; color: var(--admin-primary); font-size: 1rem; letter-spacing: -0.4px;">$<?php echo number_format($t['cost']); ?></span></td>
                <td>
                    <?php 
                        $colors = [
                            'active' => ['#DCFCE7', '#166534', 'ri-flashlight-line'], 
                            'completed' => ['#F0FDF4', '#15803D', 'ri-checkbox-circle-line'], 
                            'cancelled' => ['#FEF2F2', '#991B1B', 'ri-close-circle-line']
                        ];
                        $c = $colors[$t['status']] ?? ['#F8FAFC', '#475569', 'ri-question-line'];
                    ?>
                    <span class="admin-badge" style="background: <?php echo $c[0]; ?>; color: <?php echo $c[1]; ?>; border: 1px solid rgba(0,0,0,0.05); display: inline-flex; align-items: center; gap: 5px;">
                        <i class="<?php echo $c[2]; ?>"></i> <?php echo strtoupper($t['status']); ?>
                    </span>
                </td>
                <td>
                    <?php 
                        $tid = $t['id'];
                        $p_count = $conn->query("SELECT COUNT(*) as c FROM enrollments WHERE trip_id = $tid AND status = 'approved'")->fetch_assoc()['c'];
                        $max = $t['max_participants'] ?: '∞';
                        $perc = is_numeric($max) ? ($p_count / $max) * 100 : 0;
                    ?>
                    <div style="width: 100px;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 800; color: #64748B; margin-bottom: 5px;">
                            <span><?php echo $p_count; ?> / <?php echo $max; ?></span>
                        </div>
                        <div style="height: 6px; background: #F1F5F9; border-radius: 10px; overflow: hidden;">
                            <div style="width: <?php echo $perc; ?>%; height: 100%; background: var(--admin-primary); border-radius: 10px;"></div>
                        </div>
                    </div>
                </td>
                <td style="padding-right: 30px;">
                    <div style="display: flex; gap: 6px; justify-content: flex-end;">
                        <a href="trip.php?id=<?php echo $t['id']; ?>" class="btn-icon" title="Preview Live Site" target="_blank" style="background: white; color: #475569; border: 1px solid #E2E8F0;"><i class="ri-eye-line"></i></a>
                        <a href="edit_trip.php?id=<?php echo $t['id']; ?>" class="btn-icon" title="Edit Content" style="background: white; color: #475569; border: 1px solid #E2E8F0;"><i class="ri-edit-2-line"></i></a>
                        
                        <div class="dropdown" style="position: relative;">
                            <button class="btn-icon" style="background: white; color: #475569; border: 1px solid #E2E8F0; cursor: pointer;" onclick="this.nextElementSibling.classList.toggle('show')"><i class="ri-more-fill"></i></button>
                            <div class="dropdown-menu" style="display: none; position: absolute; right: 0; background: white; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-radius: 12px; z-index: 100; min-width: 180px; padding: 8px; border: 1px solid #E2E8F0; top: 100%; margin-top: 5px;">
                                <a href="?action=clone&tid=<?php echo $t['id']; ?>" class="dropdown-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; font-size: 0.85rem; text-decoration: none; color: #475569; font-weight: 600; border-radius: 8px;">
                                    <i class="ri-file-copy-2-line"></i> Duplicate Trip
                                </a>
                                <div style="height: 1px; background: #F1F5F9; margin: 5px 0;"></div>
                                <a href="?action=toggle_status&tid=<?php echo $t['id']; ?>&status=active" class="dropdown-item" style="display: block; padding: 10px 12px; font-size: 0.85rem; text-decoration: none; color: #475569; font-weight: 600; border-radius: 8px;">Mark as Active</a>
                                <a href="?action=toggle_status&tid=<?php echo $t['id']; ?>&status=completed" class="dropdown-item" style="display: block; padding: 10px 12px; font-size: 0.85rem; text-decoration: none; color: #475569; font-weight: 600; border-radius: 8px;">Mark as Completed</a>
                                <a href="?action=toggle_status&tid=<?php echo $t['id']; ?>&status=cancelled" class="dropdown-item" style="display: block; padding: 10px 12px; font-size: 0.85rem; text-decoration: none; color: #E11D48; font-weight: 600; border-radius: 8px;">Cancel Operation</a>
                                <div style="height: 1px; background: #F1F5F9; margin: 5px 0;"></div>
                                <a href="?action=delete&tid=<?php echo $t['id']; ?>" class="dropdown-item" style="display: block; padding: 10px 12px; font-size: 0.85rem; text-decoration: none; color: #E11D48; font-weight: 600; border-radius: 8px;" onclick="return confirm('Definitive deletion? This cannot be undone.')">Delete Trip</a>
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
