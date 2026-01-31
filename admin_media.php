<?php
require_once 'admin_header.php';

// Handle Deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete_media') {
    $mid = intval($_GET['mid']);
    $m = $conn->query("SELECT file_path FROM media WHERE id = $mid")->fetch_assoc();
    if ($m) {
        if (file_exists($m['file_path'])) unlink($m['file_path']);
        $conn->query("DELETE FROM media WHERE id = $mid");
        logActivity($conn, $_SESSION['user_id'], "Deleted media", "File: ".$m['file_path']);
    }
    header("Location: admin_media.php?msg=deleted");
    exit();
}

// Bulk Delete
if (isset($_POST['bulk_delete'])) {
    $mids = $_POST['selected_media'] ?? [];
    if (!empty($mids)) {
        foreach ($mids as $mid) {
            $mid = intval($mid);
            $m = $conn->query("SELECT file_path FROM media WHERE id = $mid")->fetch_assoc();
            if ($m) {
                if (file_exists($m['file_path'])) unlink($m['file_path']);
                $conn->query("DELETE FROM media WHERE id = $mid");
            }
        }
        logActivity($conn, $_SESSION['user_id'], "Bulk deleted media", "IDs: ".implode(',', $mids));
    }
    header("Location: admin_media.php?msg=bulk_deleted");
    exit();
}

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';

$where = "1=1";
if ($type_filter) $where .= " AND m.type = '$type_filter'";
if ($search) $where .= " AND (t.title LIKE '%$search%' OR u.name LIKE '%$search%')";

$media = $conn->query("SELECT m.*, u.name as uploader, t.title as trip_title 
                      FROM media m 
                      JOIN users u ON m.uploaded_by = u.id 
                      JOIN trips t ON m.trip_id = t.id 
                      WHERE $where 
                      ORDER BY m.uploaded_at DESC");

// Storage Calc
$storage_bytes = 0;
$m_all = $conn->query("SELECT file_path FROM media");
while($ma = $m_all->fetch_assoc()) {
    if(file_exists($ma['file_path'])) $storage_bytes += filesize($ma['file_path']);
}
$storage_mb = round($storage_bytes / 1048576, 2);
?>

<div style="display: grid; grid-template-columns: 3fr 1fr; gap: 30px; margin-bottom: 30px; align-items: end;">
    <div>
        <h3 style="margin-bottom: 15px; color: #111827;">Global Media Library</h3>
        <form method="GET" style="display: flex; gap: 10px;">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search trip or user..." class="form-control" style="border-radius: 10px; max-width: 300px;">
            <select name="type" class="form-select" style="border-radius: 10px; width: 150px;">
                <option value="">All Types</option>
                <option value="image" <?php if($type_filter == 'image') echo 'selected'; ?>>Images</option>
                <option value="video" <?php if($type_filter == 'video') echo 'selected'; ?>>Videos</option>
                <option value="document" <?php if($type_filter == 'document') echo 'selected'; ?>>Documents</option>
            </select>
            <button type="submit" class="btn btn-primary" style="border-radius: 10px;">Filter</button>
        </form>
    </div>
    
    <div style="background: white; padding: 20px; border-radius: 16px; box-shadow: var(--shadow-sm); border: 1px solid #E5E7EB; text-align: center;">
        <div style="font-size: 0.75rem; font-weight: 700; color: #6B7280; text-transform: uppercase; margin-bottom: 5px;">Storage Used</div>
        <div style="font-size: 1.5rem; font-weight: 800; color: var(--admin-primary);"><?php echo $storage_mb; ?> MB</div>
    </div>
</div>

<form method="POST">
    <div id="bulkBar" class="bulk-actions" style="display: none; margin-bottom: 20px;">
        <span id="selectedCount" style="margin-right: 20px; font-weight: 600;">0 items selected</span>
        <button type="submit" name="bulk_delete" class="btn btn-mini btn-mini-reject" onclick="return confirm('Permanently delete selected files?')" style="background: #EF4444; color: white;">Delete Selected</button>
    </div>

    <div style="background: white; border-radius: 16px; box-shadow: var(--shadow-sm); overflow: hidden;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th><input type="checkbox" onchange="toggleSelectAll(this)"></th>
                    <th>Preview / File</th>
                    <th>Type</th>
                    <th>Trip Context</th>
                    <th>Uploaded By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($m = $media->fetch_assoc()): ?>
                <tr>
                    <td><input type="checkbox" name="selected_media[]" value="<?php echo $m['id']; ?>" class="admin-checkbox" onchange="updateBulkBar()"></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <?php if($m['type'] == 'image'): ?>
                                <img src="<?php echo htmlspecialchars($m['file_path']); ?>" style="width: 60px; height: 45px; object-fit: cover; border-radius: 6px; border: 1px solid #E5E7EB;">
                            <?php else: ?>
                                <div style="width: 60px; height: 45px; background: #F3F4F6; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #9CA3AF;">
                                    <i class="<?php echo $m['type'] == 'video' ? 'ri-video-line' : 'ri-file-text-line'; ?>" style="font-size: 1.2rem;"></i>
                                </div>
                            <?php endif; ?>
                            <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 150px; font-size: 0.8rem; color: #6B7280;">
                                <?php echo basename($m['file_path']); ?>
                            </div>
                        </div>
                    </td>
                    <td><span class="admin-badge" style="background: #F3F4F6; color: #374151;"><?php echo strtoupper($m['type']); ?></span></td>
                    <td><span style="font-size: 0.85rem; font-weight: 600; color: #111827;"><?php echo htmlspecialchars($m['trip_title']); ?></span></td>
                    <td>
                        <div style="font-size: 0.85rem; font-weight: 600;"><?php echo htmlspecialchars($m['uploader']); ?></div>
                        <div style="font-size: 0.7rem; color: #9CA3AF;"><?php echo date('M d, Y', strtotime($m['uploaded_at'])); ?></div>
                    </td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <a href="<?php echo htmlspecialchars($m['file_path']); ?>" class="btn-icon" target="_blank" style="background: #F3F4F6; color: #374151;" title="Download"><i class="ri-download-line"></i></a>
                            <a href="?action=delete_media&mid=<?php echo $m['id']; ?>" class="btn-icon danger" onclick="return confirm('Delete this file?')" style="background: #FEE2E2; color: #EF4444;" title="Delete"><i class="ri-delete-bin-line"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</form>

<?php require_once 'admin_footer.php'; ?>
