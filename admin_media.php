<?php
require_once 'admin_header.php';

// Handle Deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete_media') {
    $mid = intval($_GET['mid']);
    $m = $conn->query("SELECT file_path FROM media WHERE id = $mid")->fetch_assoc();
    if ($m) {
        if (file_exists($m['file_path']))
            unlink($m['file_path']);
        $conn->query("DELETE FROM media WHERE id = $mid");
        logActivity($conn, $_SESSION['user_id'], "Deleted media", "File: " . $m['file_path']);
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
                if (file_exists($m['file_path']))
                    unlink($m['file_path']);
                $conn->query("DELETE FROM media WHERE id = $mid");
            }
        }
        logActivity($conn, $_SESSION['user_id'], "Bulk deleted media", "IDs: " . implode(',', $mids));
    }
    header("Location: admin_media.php?msg=bulk_deleted");
    exit();
}

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';

$where = "1=1";
if ($type_filter)
    $where .= " AND m.type = '$type_filter'";
if ($search)
    $where .= " AND (t.title LIKE '%$search%' OR u.name LIKE '%$search%')";

$media = $conn->query("SELECT m.*, u.name as uploader, t.title as trip_title 
                      FROM media m 
                      JOIN users u ON m.uploaded_by = u.id 
                      JOIN trips t ON m.trip_id = t.id 
                      WHERE $where 
                      ORDER BY m.uploaded_at DESC");

// Storage Calc
$storage_bytes = 0;
$m_all = $conn->query("SELECT file_path FROM media");
while ($ma = $m_all->fetch_assoc()) {
    if (file_exists($ma['file_path']))
        $storage_bytes += filesize($ma['file_path']);
}
$storage_mb = round($storage_bytes / 1048576, 2);
?>

<style>
    /* Hide Default Elements */
    .admin-nav, .admin-content > h2, .admin-content > div:first-child {
        display: none !important;
    }

    body {
        background-color: #F8F9FA;
        position: relative;
        overflow-x: hidden;
    }

    /* Command Center Background */
    .geo-bg {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
        overflow: hidden;
        background: radial-gradient(circle at 10% 20%, rgba(243, 232, 255, 0.4) 0%, transparent 40%),
                    radial-gradient(circle at 90% 80%, rgba(220, 252, 231, 0.4) 0%, transparent 40%),
                    radial-gradient(circle at 50% 50%, rgba(255, 237, 213, 0.3) 0%, transparent 60%);
    }

    .geo-bg::before {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        background-image: 
            linear-gradient(rgba(0,0,0,0.02) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0,0,0,0.02) 1px, transparent 1px);
        background-size: 40px 40px;
    }

    .dashboard-container {
        max-width: 1260px;
        margin: 0 auto;
        padding: 36px 18px;
    }

    /* Page Header */
    .page-header {
        margin-bottom: 36px;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
    }

    .header-text h1 {
        font-size: 2.5rem;
        font-weight: 900;
        letter-spacing: -1.2px;
        color: #1E293B;
        margin: 0 0 8px 0;
    }

    .header-text p {
        font-size: 1rem;
        color: #64748B;
        margin: 0;
        font-weight: 500;
    }

    /* Stats Card (Storage) */
    .storage-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        padding: 13.5px 22.5px;
        border-radius: 18px;
        border: 1px solid rgba(255, 255, 255, 0.5);
        display: flex;
        align-items: center;
        gap: 13.5px;
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.05);
    }

    .storage-icon {
        width: 40.5px;
        height: 40.5px;
        background: #EEF2FF;
        border-radius: 10.8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--admin-primary);
        font-size: 1.125rem;
    }

    /* Filter Strip */
    .filter-strip {
        background: rgba(255, 255, 255, 0.6);
        backdrop-filter: blur(10px);
        padding: 14.4px 18px;
        border-radius: 18px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        margin-bottom: 31.5px;
        display: flex;
        gap: 13.5px;
        align-items: center;
        border: 1px solid rgba(255, 255, 255, 0.5);
    }

    .search-wrapper {
        position: relative;
        flex: 1;
    }

    .search-wrapper i {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: #94A3B8;
        font-size: 1.1rem;
    }

    .search-wrapper input {
        width: 100%;
        height: 45px;
        padding-left: 45px;
        border-radius: 12.6px;
        border: 1px solid #E2E8F0;
        background: white;
        font-weight: 600;
        transition: all 0.3s;
    }

    .search-wrapper input:focus {
        background: white;
        border-color: #4338CA;
        box-shadow: 0 0 0 4px rgba(67, 56, 202, 0.1);
        outline: none;
    }

    .filter-select {
        height: 45px;
        border-radius: 12.6px;
        border: 1px solid #E2E8F0;
        padding: 0 18px;
        background: white;
        font-weight: 700;
        color: #475569;
        cursor: pointer;
    }

    .btn-sync {
        height: 45px;
        padding: 0 22.5px;
        border-radius: 12.6px;
        background: linear-gradient(135deg, var(--admin-primary), #6366F1);
        color: white;
        border: none;
        font-weight: 800;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 9px;
        flex-shrink: 0;
        box-shadow: 0 3.6px 10.8px rgba(99, 102, 241, 0.2);
    }

    .btn-sync:hover {
        transform: translateY(-2px);
        filter: brightness(1.1);
    }

    /* Media Grid */
    .media-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
        gap: 21.6px;
        padding-bottom: 108px;
    }

    .media-card {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10.8px);
        -webkit-backdrop-filter: blur(10.8px);
        border-radius: 21.6px;
        padding: 21.6px;
        display: flex;
        gap: 18px;
        align-items: center;
        position: relative;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: visible;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 7.2px 28.8px 0 rgba(31, 38, 135, 0.07);
        min-height: 126px;
    }

    .media-card:hover {
        transform: translateY(-10px);
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.4);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        z-index: 10;
    }

    .media-card.selected {
        border-color: var(--admin-primary);
        background: rgba(var(--admin-primary-rgb), 0.05) !important;
    }

    .card-checkbox {
        position: absolute;
        top: 13.5px;
        left: 13.5px;
        z-index: 5;
        width: 16.2px;
        height: 16.2px;
        cursor: pointer;
        accent-color: var(--admin-primary);
    }

    .thumbnail-container {
        width: 90px;
        height: 90px;
        border-radius: 18px;
        overflow: hidden;
        flex-shrink: 0;
        border: 2.7px solid white;
        box-shadow: 0 9px 18px rgba(0,0,0,0.1);
    }

    .thumbnail-container img, .thumbnail-container div {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .media-info {
        flex: 1;
        min-width: 0;
    }

    .media-meta {
        font-size: 0.65rem;
        font-weight: 850;
        color: #94A3B8;
        display: block;
        margin-bottom: 3.6px;
        text-transform: uppercase;
        letter-spacing: 0.45px;
    }

    .media-info h4 {
        margin: 0 0 5.4px 0;
        font-size: 1.08rem;
        font-weight: 850;
        color: #1E293B;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        letter-spacing: -0.45px;
    }

    .media-env {
        font-size: 0.76rem;
        font-weight: 600;
        color: #64748B;
        display: flex;
        align-items: center;
        gap: 5.4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .media-env i {
        color: var(--admin-primary);
    }

    .right-section {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 9px;
        flex-shrink: 0;
        justify-content: space-between;
        height: 100%;
        padding: 4.5px 0;
    }

    .format-badge {
        padding: 4.5px 10.8px;
        border-radius: 45px;
        font-size: 0.585rem;
        font-weight: 900;
        letter-spacing: 0.45px;
        display: inline-flex;
        align-items: center;
        gap: 4.5px;
        box-shadow: 0 1.8px 7.2px rgba(0,0,0,0.05);
    }

    .badge-image { background: #EEF2FF; color: var(--admin-primary); border: 1px solid #E0E7FF; }
    .badge-video { background: #ECFDF5; color: #10B981; border: 1px solid #D1FAE5; }

    .action-row {
        display: flex;
        gap: 6px;
    }

    .btn-media {
        width: 34.2px;
        height: 34.2px;
        border-radius: 10.8px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #E2E8F0;
        background: white;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        font-size: 1rem;
        color: #64748B;
    }

    .btn-media:hover {
        background: #F8FAFC;
        color: var(--admin-primary);
        transform: translateY(-1.8px);
        border-color: var(--admin-primary);
        box-shadow: 0 4.5px 13.5px rgba(var(--admin-primary-rgb), 0.1);
    }

    .btn-delete-card:hover {
        background: #FEF2F2;
        color: #EF4444;
        border-color: #FEE2E2;
    }

    /* Bulk Bar */
    .bulk-bar-floating {
        background: white;
        padding: 10.8px 21.6px;
        border-radius: 18px;
        box-shadow: 0 18px 36px rgba(0,0,0,0.15);
        display: none;
        align-items: center;
        position: fixed;
        bottom: 27px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1000;
        gap: 18px;
        border: 1px solid rgba(0,0,0,0.05);
    }

    .wipe-btn {
        background: #E11D48;
        color: white;
        padding: 9px 16.2px;
        border-radius: 10.8px;
        border: none;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 7.2px;
        cursor: pointer;
        font-size: 0.765rem;
    }

    .admin-content { padding: 0 !important; max-width: none !important; }

    /* Full Responsiveness */
    @media (max-width: 1200px) {
        .header-text h1 { font-size: 2.25rem; }
    }

    @media (max-width: 992px) {
        .dashboard-container { padding: 27px 13.5px; }
        .page-header { align-items: flex-start; gap: 18px; }
        .header-text h1 { font-size: 2rem; }
    }

    @media (max-width: 768px) {
        .page-header { flex-direction: column; }
        .storage-card { width: 100%; justify-content: space-between; padding: 13.5px 18px; }
        .filter-strip { flex-direction: column; align-items: stretch; border-radius: 18px; padding: 10.8px; }
        .filter-strip form { flex-direction: column; align-items: stretch; width: 100%; }
        .filter-select, .btn-sync, .search-wrapper input { width: 100%; height: 40.5px; }
        .checkbox-container { display: flex; justify-content: center; padding-top: 9px; border-top: 1px solid #f1f5f9; }
        .header-text h1 { font-size: 1.98rem; }
        .header-text p { font-size: 0.9rem; }
        .media-grid { grid-template-columns: 1fr; gap: 14.4px; }
    }

    @media (max-width: 480px) {
        .header-text h1 { font-size: 1.62rem; }
        .media-card { padding: 16.2px; gap: 13.5px; min-height: 108px; }
        .thumbnail-container { width: 72px; height: 72px; border-radius: 14.4px; }
        .media-info h4 { font-size: 0.945rem; }
        .media-meta { font-size: 0.585rem; }
        .btn-media { width: 30.6px; height: 30.6px; border-radius: 9px; font-size: 0.9rem; }
        .bulk-bar-floating { width: 90%; padding: 9px 13.5px; gap: 9px; border-radius: 14.4px; }
        .bulk-bar-floating #selectedCount { font-size: 0.72rem; }
        .wipe-btn { padding: 7.2px 10.8px; font-size: 0.675rem; border-radius: 9px; }
    }
</style>

<div class="geo-bg"></div>

<div class="dashboard-container">
    <div class="page-header">
        <div class="header-text">
            <h1>Digital Assets Library</h1>
            <p>Manage and monitor all platform media files and travel content</p>
        </div>
        
        <div class="storage-card">
            <div class="storage-icon">
                <i class="ri-database-2-line"></i>
            </div>
            <div>
                <span style="font-size: 0.65rem; font-weight: 850; color: #64748B; text-transform: uppercase;">Cloud Capacity</span>
                <div style="font-size: 1.26rem; font-weight: 950; color: #1E293B; line-height: 1;">
                    <?php echo $storage_mb; ?> <span style="font-size: 0.81rem; color: #94A3B8;">MB</span>
                </div>
            </div>
        </div>
    </div>

    <div class="filter-strip">
        <form method="GET" style="display: flex; gap: 13.5px; flex: 1; align-items: center;">
            <div class="search-wrapper">
                <i class="ri-search-2-line"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search trip assets or contributors...">
            </div>
            
            <select name="type" class="filter-select">
                <option value="">All Formats</option>
                <option value="image" <?php echo $type_filter == 'image' ? 'selected' : ''; ?>>Images Only</option>
                <option value="video" <?php echo $type_filter == 'video' ? 'selected' : ''; ?>>Videos Only</option>
            </select>
            
            <button type="submit" class="btn-sync">
                <i class="ri-refresh-line"></i> Sync Archive
            </button>
        </form>
        <div class="checkbox-container">
            <input type="checkbox" onchange="toggleSelectAll(this)" style="width: 18px; height: 18px; cursor: pointer;" title="Select All Assets">
        </div>
    </div>

    <form method="POST" id="mediaForm">
        <div id="bulkBar" class="bulk-bar-floating">
            <span id="selectedCount" style="font-weight: 800; color: #4338CA; font-size: 0.81rem;">
                <i class="ri-checkbox-circle-fill"></i> <span id="countText">0 selected</span>
            </span>
            <button type="submit" name="bulk_delete" class="wipe-btn" onclick="return confirm('Purge selected assets forever?')">
                <i class="ri-delete-bin-line"></i> Wipe Selected
            </button>
        </div>

        <div class="media-grid">
            <?php 
            if ($media->num_rows > 0): 
                while ($m = $media->fetch_assoc()): 
                    $badge_class = 'badge-' . $m['type'];
                    $format_icon = $m['type'] == 'image' ? 'ri-image-line' : 'ri-video-line';
            ?>
                <div class="media-card">
                    <input type="checkbox" name="selected_media[]" value="<?php echo $m['id']; ?>" class="admin-checkbox card-checkbox" onchange="updateBulkBar()">
                    
                    <div class="thumbnail-container">
                        <?php if ($m['type'] == 'image'): ?>
                            <img src="<?php echo htmlspecialchars($m['file_path']); ?>" alt="Media">
                        <?php else: ?>
                            <div style="background: white; border-radius: 10.8px; display: flex; align-items: center; justify-content: center; color: #4338CA;">
                                <i class="ri-video-chat-line" style="font-size: 1.35rem;"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="media-info">
                        <span class="media-meta">#ASSET-<?php echo $m['id']; ?></span>
                        <h4><?php echo basename($m['file_path']); ?></h4>
                        <div class="media-env">
                            <i class="ri-map-pin-2-line"></i>
                            <?php echo htmlspecialchars($m['trip_title']); ?>
                        </div>
                    </div>

                    <div class="right-section">
                        <span class="format-badge <?php echo $badge_class; ?>">
                            <i class="<?php echo $format_icon; ?>"></i>
                            <?php echo strtoupper($m['type']); ?>
                        </span>
                        
                        <div class="action-row">
                            <a href="<?php echo htmlspecialchars($m['file_path']); ?>" target="_blank" class="btn-media btn-view" title="View">
                                <i class="ri-external-link-line"></i>
                            </a>
                            <a href="?action=delete_media&mid=<?php echo $m['id']; ?>" class="btn-media btn-delete-card" onclick="return confirm('Purge asset?')" title="Delete">
                                <i class="ri-delete-bin-2-line"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 90px; color: #94A3B8;">
                    <i class="ri-camera-off-line" style="font-size: 3.6rem; display: block; margin-bottom: 18px;"></i>
                    <h2 style="font-weight: 800; color: #64748B;">No cloud assets detected</h2>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
    function toggleSelectAll(master) {
        const checkboxes = document.querySelectorAll('.admin-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = master.checked;
            updateCardStyle(cb);
        });
        updateBulkBar();
    }

    function updateBulkBar() {
        const checkboxes = document.querySelectorAll('.admin-checkbox');
        const bulkBar = document.getElementById('bulkBar');
        const countText = document.getElementById('countText');
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;

        if (checkedCount > 0) {
            bulkBar.style.display = 'flex';
            countText.textContent = `${checkedCount} selected`;
        } else {
            bulkBar.style.display = 'none';
        }
        
        checkboxes.forEach(cb => updateCardStyle(cb));
    }

    function updateCardStyle(cb) {
        const card = cb.closest('.media-card');
        if (card) {
            if (cb.checked) card.classList.add('selected');
            else card.classList.remove('selected');
        }
    }
</script>

<?php require_once 'admin_footer.php'; ?>