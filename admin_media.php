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

<div style="display: grid; grid-template-columns: 1fr 280px; gap: 30px; margin-bottom: 35px; align-items: flex-end;">
    <div>
        <h3 style="font-size: 1.25rem; font-weight: 850; color: var(--admin-text-main); margin-bottom: 20px; letter-spacing: -0.6px;">Asset Management Library</h3>
        <form method="GET" style="display: flex; gap: 12px; align-items: center;">
            <div style="position: relative; flex: 1;">
                <i class="ri-search-line" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 1rem;"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search trip or user..." class="form-control" style="padding-left: 40px; height: 42px; border-radius: 12px; border: 1px solid var(--admin-border); background: white; width: 100%; font-weight: 500; font-size: 0.9rem;">
            </div>
            <select name="type" class="form-select" style="width: 140px; height: 42px; border-radius: 12px; border: 1px solid var(--admin-border); background: white; font-weight: 600; color: #475569; font-size: 0.85rem;">
                <option value="">All Formats</option>
                <option value="image" <?php if($type_filter == 'image') echo 'selected'; ?>>Images</option>
                <option value="video" <?php if($type_filter == 'video') echo 'selected'; ?>>Videos</option>
                <option value="document" <?php if($type_filter == 'document') echo 'selected'; ?>>Documents</option>
            </select>
            <button type="submit" class="btn-premium" style="height: 42px; padding: 0 20px; font-size: 0.85rem;">
                <i class="ri-equalizer-line"></i> Sync
            </button>
        </form>
    </div>
    
    <div style="background: linear-gradient(135deg, white 0%, #F8FAFC 100%); padding: 18px 22px; border-radius: 16px; border: 1px solid var(--admin-border); display: flex; align-items: center; gap: 15px; box-shadow: var(--shadow-premium);">
        <div style="width: 44px; height: 44px; background: #EEF2FF; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--admin-primary); font-size: 1.3rem;">
            <i class="ri-database-2-line"></i>
        </div>
        <div>
            <div style="font-size: 0.72rem; font-weight: 800; color: #64748B; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 2px;">Cloud Archive</div>
            <div style="font-size: 1.25rem; font-weight: 850; color: var(--admin-text-main); line-height: 1;">
                <?php echo $storage_mb; ?> <span style="font-size: 0.8rem; font-weight: 700; color: #94A3B8;">MB</span>
            </div>
        </div>
    </div>
</div>

<form method="POST">
    <div id="bulkBar" class="bulk-actions" style="display: none; position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); z-index: 2000; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); padding: 15px 30px; border-radius: 18px; display: flex; align-items: center; background: var(--admin-sidebar-bg); border: 1px solid rgba(255,255,255,0.1);">
        <span id="selectedCount" style="margin-right: 25px; font-weight: 700; color: white; display: flex; align-items: center; gap: 10px;">
            <i class="ri-checkbox-circle-fill" style="color: #10B981;"></i>
            <span id="countText">0 items selected</span>
        </span>
        <button type="submit" name="bulk_delete" class="btn-premium" onclick="return confirm('Security Check: Permanently purge selected cloud assets?')" style="background: #E11D48; padding: 10px 20px; font-size: 0.85rem;">
            <i class="ri-delete-bin-line"></i> Wipe Selected
        </button>
    </div>

    <div class="admin-card" style="padding: 0; overflow: hidden;">
        <table class="admin-table" style="width: 100%;">
            <thead>
                <tr>
                    <th style="width: 50px;"><input type="checkbox" onchange="toggleSelectAll(this)" style="width: 18px; height: 18px;"></th>
                    <th>Asset Details</th>
                    <th>Format</th>
                    <th>Environment</th>
                    <th>Contributor</th>
                    <th style="text-align: right;">Operations</th>
                </tr>
            </thead>
            <tbody>
                <?php while($m = $media->fetch_assoc()): ?>
                <tr style="transition: background 0.2s;">
                    <td><input type="checkbox" name="selected_media[]" value="<?php echo $m['id']; ?>" class="admin-checkbox" onchange="updateBulkBar()" style="width: 18px; height: 18px;"></td>
                    <td style="padding: 15px 20px;">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <div style="position: relative;">
                                <?php if($m['type'] == 'image'): ?>
                                    <img src="<?php echo htmlspecialchars($m['file_path']); ?>" style="width: 56px; height: 40px; object-fit: cover; border-radius: 8px; border: 1px solid #E2E8F0; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                <?php else: ?>
                                    <div style="width: 56px; height: 40px; background: #F8FAFC; border-radius: 8px; border: 1px solid #E2E8F0; display: flex; align-items: center; justify-content: center; color: var(--admin-primary);">
                                        <i class="<?php echo $m['type'] == 'video' ? 'ri-video-chat-line' : 'ri-file-3-line'; ?>" style="font-size: 1.25rem;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style="overflow: hidden;">
                                <div style="font-weight: 700; color: var(--admin-text-main); font-size: 0.9rem; white-space: nowrap; text-overflow: ellipsis; max-width: 250px;">
                                    <?php echo basename($m['file_path']); ?>
                                </div>
                                <div style="font-size: 0.75rem; color: #94A3B8; font-weight: 500; margin-top: 1px;">
                                    ID: #TRP-<?php echo $m['id']; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="admin-badge" style="background: #EEF2FF; color: #4338CA; border: 1px solid #E0E7FF;">
                            <?php echo strtoupper($m['type']); ?>
                        </span>
                    </td>
                    <td><span style="font-size: 0.9rem; font-weight: 700; color: #475569;"><?php echo htmlspecialchars($m['trip_title']); ?></span></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 28px; height: 28px; background: #F1F5F9; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.75rem; color: #64748B;">
                                 <?php echo strtoupper(substr($m['uploader'], 0, 1)); ?>
                            </div>
                            <div>
                                <div style="font-size: 0.88rem; font-weight: 700; color: #475569;"><?php echo htmlspecialchars($m['uploader']); ?></div>
                                <div style="font-size: 0.72rem; color: #94A3B8; font-weight: 600;"><?php echo date('d M, Y', strtotime($m['uploaded_at'])); ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="padding-right: 30px;">
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <a href="<?php echo htmlspecialchars($m['file_path']); ?>" class="btn-icon" target="_blank" style="background: white; border: 1px solid #E2E8F0; color: #475569; width: 38px; height: 38px;" title="Stream / Download">
                                <i class="ri-external-link-line" style="font-size: 1.1rem;"></i>
                            </a>
                            <a href="?action=delete_media&mid=<?php echo $m['id']; ?>" class="btn-icon" onclick="return confirm('Definitive deletion of cloud asset?')" style="background: #FFF1F2; border: 1px solid #FECDD3; color: #E11D48; width: 38px; height: 38px;" title="Purge Asset">
                                <i class="ri-delete-bin-2-line" style="font-size: 1.1rem;"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</form>

<?php require_once 'admin_footer.php'; ?>
