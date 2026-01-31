<?php
require_once 'db.php';
require_once 'auth.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
checkLogin();
// Header included later to allow redirects

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$trip_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch Trip & Perms
$sql = "SELECT title, created_by, status FROM trips WHERE id = $trip_id";
$result = $conn->query($sql);
if ($result->num_rows == 0) die("Trip not found");
$trip = $result->fetch_assoc();

// Check Access (Simplified replication of trip.php logic)
$has_access = false;
if ($role == 'admin') $has_access = true;
if ($role == 'tripmaker' && $trip['created_by'] == $user_id) $has_access = true;
if ($role == 'student') {
    $e_check = $conn->query("SELECT status FROM enrollments WHERE trip_id = $trip_id AND student_id = $user_id AND status='approved'");
    if ($e_check->num_rows > 0) $has_access = true;
}


if (!$has_access) {
    include 'header.php';
    echo "<div class='container section'>Access Denied</div>";
    include 'footer.php';
    exit();
}

// Logic for Upload/Delete handling (Copied and adapted from trip.php)
// UPLOAD
if (isset($_FILES['media_file'])) {
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    
    // Check if multiple files
    $count = is_array($_FILES['media_file']['name']) ? count($_FILES['media_file']['name']) : 1;
    $file_type = isset($_POST['media_type']) ? $_POST['media_type'] : 'image';

    for ($i = 0; $i < $count; $i++) {
        $name = is_array($_FILES['media_file']['name']) ? $_FILES['media_file']['name'][$i] : $_FILES['media_file']['name'];
        $tmp = is_array($_FILES['media_file']['tmp_name']) ? $_FILES['media_file']['tmp_name'][$i] : $_FILES['media_file']['tmp_name'];
        $size = is_array($_FILES['media_file']['size']) ? $_FILES['media_file']['size'][$i] : $_FILES['media_file']['size'];
        
        if (empty($name)) continue;

        // Check file size (Max 50MB)
        if ($size > 50000000) {
            continue; // Skip large files or handle error
        }

        $target_file = $target_dir . time() . "_" . $i . "_" . basename($name); // Add index to prevent overwrite timestamp collision
        
        if (move_uploaded_file($tmp, $target_file)) {
            $stmt = $conn->prepare("INSERT INTO media (trip_id, uploaded_by, file_path, type) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $trip_id, $user_id, $target_file, $file_type);
            $stmt->execute();
        }
    }
    header("Location: trip_gallery.php?id=$trip_id&msg=uploaded");
    exit();
}

// DELETE
if (isset($_POST['delete_media']) && ($role == 'admin' || $role == 'tripmaker' || $trip['created_by'] == $user_id)) {
    if(isset($_POST['selected_media']) && is_array($_POST['selected_media'])) {
        foreach($_POST['selected_media'] as $mid) {
           $mid = intval($mid);
           $m_q = $conn->query("SELECT file_path FROM media WHERE id=$mid AND trip_id=$trip_id");
           if($m_q->num_rows > 0) {
               $f = $m_q->fetch_assoc()['file_path'];
               if(file_exists($f)) unlink($f); 
               $conn->query("DELETE FROM media WHERE id=$mid");
           }
        }
    }
    header("Location: trip_gallery.php?id=$trip_id&msg=deleted");
    exit();
}

include 'header.php';
?>

<div class="container section">
    
    <!-- Header -->
    <a href="trip.php?id=<?php echo $trip_id; ?>" style="display: inline-flex; align-items: center; gap: 6px; color: var(--text-light); margin-bottom: 20px; font-weight: 500;">
        <i class="ri-arrow-left-line"></i> Back to Trip
    </a>

    <div class="gallery-header">
        <div>
            <h1 style="color: var(--secondary-color); margin-bottom: 6px;">Trip Gallery</h1>
            <p style="color: var(--text-light);"><?php echo htmlspecialchars($trip['title']); ?></p>
        </div>
        
        <div class="media-actions" style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
             <!-- Filters -->
             <div class="gallery-filters">
                <button class="gallery-filter-btn active" onclick="filterGallery('all', this)">All</button>
                <button class="gallery-filter-btn" onclick="filterGallery('image', this)">Photos</button>
                <button class="gallery-filter-btn" onclick="filterGallery('video', this)">Videos</button>
                <button class="gallery-filter-btn" onclick="filterGallery('document', this)">Docs</button>
            </div>
            
            <div style="width: 1px; height: 24px; background: #e2e8f0; display: none; sm: display: block;"></div>

            <!-- Global Actions -->
             <div style="display: flex; gap: 8px;">
                <button class="btn-icon danger" id="cancelSelBtn" onclick="exitSelectionMode()" style="display:none;" title="Cancel Selection"><i class="ri-close-line"></i></button>
                <button class="btn-icon" id="downloadBtn" onclick="triggerDownloadFlow()" title="Download"><i class="ri-download-cloud-2-line"></i></button>
                <?php if($role == 'admin' || $trip['created_by'] == $user_id): ?>
                    <button class="btn-icon danger" onclick="deleteSelected()" title="Delete"><i class="ri-delete-bin-line"></i></button>
                <?php endif; ?>
                <button class="btn btn-primary" onclick="document.getElementById('uploadModal').style.display='flex'">+ Upload</button>
             </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div id="uploadModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 20px; width: 400px; max-width: 90%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">Upload Media</h3>
                <button onclick="document.getElementById('uploadModal').style.display='none'" style="background:none; border:none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Select Files (Max 50MB each)</label>
                    <input type="file" name="media_file[]" class="form-control" multiple required>
                </div>
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="media_type" class="form-select">
                        <option value="image">Photo</option>
                        <option value="video">Video</option>
                        <option value="document">Document</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Upload</button>
            </form>
        </div>
    </div>

    <!-- Masonry Grid -->
    <form id="galleryForm" method="POST">
        <input type="hidden" name="delete_media" value="1">
        <div class="masonry-grid" id="masonryContainer">
            <?php
            $m_sql = "SELECT m.*, u.name as uploader FROM media m JOIN users u ON m.uploaded_by = u.id WHERE trip_id = $trip_id ORDER BY uploaded_at DESC";
            $res = $conn->query($m_sql);
            
            if ($res->num_rows > 0) {
                while($media = $res->fetch_assoc()) {
                    $type = $media['type'];
                    $file_url = htmlspecialchars($media['file_path']);
                    
                    echo '<div class="masonry-item" data-type="'.$type.'" data-src="'.$file_url.'" onclick="handleItemClick(event, this)">';
                    
                    // Hidden Checkbox Input
                    echo '<input type="checkbox" name="selected_media[]" value="'.$media['id'].'" data-file="'.$file_url.'" class="hidden-cb" style="display:none;">';
                    
                    // Media Wrapper with Overlay
                    echo '<div class="masonry-media-wrapper">';
                        
                        if ($type == 'image') {
                            echo '<img src="'.$file_url.'" loading="lazy" alt="Trip Photo">';
                        } else {
                            $icon = ($type == 'video') ? 'ri-video-fill' : 'ri-file-text-fill';
                            echo '<div class="masonry-stub">';
                            echo '<i class="'.$icon.'" style="font-size: 3rem;"></i>';
                            echo '<span style="font-size: 0.8rem; margin-top: 8px;">'.ucfirst($type).'</span>';
                            echo '</div>';
                        }
                        
                        // Hover Overlay
                        echo '<div class="masonry-overlay">';
                            // Checkbox Area
                            echo '<div class="masonry-checkbox-container">';
                                echo '<input type="checkbox" class="masonry-checkbox" onclick="event.stopPropagation(); toggleSelect(this.closest(\'.masonry-item\'))">'; 
                            echo '</div>';
                            
                            // Actions Area
                            echo '<div class="masonry-actions">';
                                echo '<a href="'.$file_url.'" download class="action-btn-mini" title="Download" onclick="event.stopPropagation()"><i class="ri-download-line"></i></a>';
                            echo '</div>';
                        echo '</div>'; // End Overlay

                    echo '</div>'; // End Wrapper

                    // Info Section
                    echo '<div class="masonry-info">';
                        echo '<span class="masonry-uploader">'.$media['uploader'].'</span>';
                        echo '<span class="masonry-date">'.date('M d, Y', strtotime($media['uploaded_at'])).'</span>';
                    echo '</div>';

                    echo '</div>'; // End Item
                }
            } else {
                echo '<p style="text-align: center; grid-column: 1/-1; color: var(--text-light); margin-top: 40px; font-size: 1.1rem;">No memories shared yet. Be the first to upload!</p>';
            }
            ?>
        </div>
    </form>

</div>

    <!-- Lightbox Modal -->
    <div id="lightbox" class="lightbox-overlay">
        <div class="lightbox-close" onclick="closeLightbox()">&times;</div>
        
        <div class="lightbox-nav lightbox-prev" onclick="changeImage(-1)"><i class="ri-arrow-left-s-line"></i></div>
        <div class="lightbox-nav lightbox-next" onclick="changeImage(1)"><i class="ri-arrow-right-s-line"></i></div>
        
        <div class="lightbox-content-wrapper">
            <img id="lightboxImg" src="" class="lightbox-media" alt="Full Preview" style="display:none;">
            <video id="lightboxVideo" class="lightbox-media" controls autoplay style="display:none;"></video>
        </div>
    </div>

    <!-- Download Choice Modal -->
    <div id="dlModal" style="display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2100; align-items:center; justify-content:center;">
        <div style="background: white; padding: 24px; border-radius: 16px; width: 320px; text-align: center;">
            <h3 style="margin-bottom: 16px;">Download Options</h3>
            <p style="color: var(--text-light); margin-bottom: 24px;">You have selected <span id="dlCount">0</span> files.</p>
            <div style="display: grid; gap: 12px;">
                <button onclick="processDownload('individual')" class="btn btn-outline" style="width: 100%;">Download Individually</button>
                <button onclick="processDownload('zip')" class="btn btn-primary" style="width: 100%;">Download as ZIP</button>
            </div>
            <button onclick="document.getElementById('dlModal').style.display='none'" style="margin-top: 16px; background: none; border: none; color: var(--text-light); cursor: pointer; text-decoration: underline;">Cancel</button>
        </div>
    </div>

</div>

<script>
    // --- GALLERY LOGIC ---
    let selectionMode = false;

    function filterGallery(type, btn) {
        document.querySelectorAll('.gallery-filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        document.querySelectorAll('.masonry-item').forEach(item => {
            if (type === 'all' || item.dataset.type === type) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
        
        // Re-index media for lightbox after filter
        updateLightboxIndex();
    }

    // Toggle Selection Logic
    function toggleSelect(item) {
        let hiddenCb = item.querySelector('.hidden-cb');
        if(!hiddenCb) return;

        hiddenCb.checked = !hiddenCb.checked;
        
        // Update visual checkbox to match
        let visualCb = item.querySelector('.masonry-checkbox');
        if(visualCb) visualCb.checked = hiddenCb.checked;

        if (hiddenCb.checked) {
            item.classList.add('selected');
        } else {
            item.classList.remove('selected');
        }
        
        if(selectionMode === false && hiddenCb.checked) {
            enterSelectionMode();
        }
    }

    function handleItemClick(event, item) {
        // If clicking checkbox/actions directly, stop
        if(event.target.closest('.masonry-checkbox-container') || event.target.closest('.masonry-actions')) return;

        if (selectionMode) {
            toggleSelect(item);
        } else {
            // Normal Mode -> Open Lightbox
            let type = item.dataset.type;
            if(type === 'image' || type === 'video') {
                let src = item.dataset.src;
                openLightbox(src, type);
            }
        }
    }

    // --- DOWNLOAD FLOW ---
    function triggerDownloadFlow() {
        if (!selectionMode) {
            enterSelectionMode();
        } else {
            performDownloadCheck();
        }
    }

    function enterSelectionMode() {
        selectionMode = true;
        document.body.classList.add('selection-mode');
        document.getElementById('cancelSelBtn').style.display = 'inline-flex';
    }

    function exitSelectionMode() {
        selectionMode = false;
        document.body.classList.remove('selection-mode');
        document.getElementById('cancelSelBtn').style.display = 'none';
        
        document.querySelectorAll('input.hidden-cb:checked').forEach(cb => {
            cb.checked = false;
            let item = cb.closest('.masonry-item');
            if(item) {
                item.classList.remove('selected');
                let vcb = item.querySelector('.masonry-checkbox');
                if(vcb) vcb.checked = false;
            }
        });
    }

    function performDownloadCheck() {
        let selected = document.querySelectorAll('input.hidden-cb:checked');
        if (selected.length === 0) {
            alert("No images selected. Please select images to download.");
            return;
        } else if (selected.length === 1) {
            forceDownload(selected[0].dataset.file);
            exitSelectionMode();
        } else {
            document.getElementById('dlCount').innerText = selected.length;
            document.getElementById('dlModal').style.display = 'flex';
        }
    }

    function processDownload(type) {
        let files = [];
        document.querySelectorAll('input.hidden-cb:checked').forEach(cb => files.push(cb.dataset.file));
        
        if (type === 'individual') {
            files.forEach(file => forceDownload(file));
        } else {
            // ZIP Download
            let form = document.createElement('form');
            form.method = 'POST';
            form.action = 'download_zip.php';
            files.forEach(f => {
                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'files[]';
                input.value = f;
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
        
        document.getElementById('dlModal').style.display='none';
        exitSelectionMode();
    }

    function forceDownload(url) {
        let link = document.createElement('a');
        link.href = url;
        link.download = '';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link); 
    }

    // --- OTHER ---
    function deleteSelected() { 
        if (document.querySelectorAll('input.hidden-cb:checked').length === 0) return alert('Select files');
        if (confirm('Delete selected items?')) { document.getElementById('galleryForm').submit(); }
    }

    // --- LIGHTBOX LOGIC ---
    let currentMediaIndex = 0;
    let galleryItems = []; // Stores {src, type}

    function updateLightboxIndex() {
        galleryItems = [];
        document.querySelectorAll('.masonry-item').forEach(item => {
            if(item.style.display !== 'none') {
                let t = item.dataset.type;
                if(t === 'image' || t === 'video') {
                    galleryItems.push({
                        src: item.dataset.src,
                        type: t
                    });
                }
            }
        });
    }

    function openLightbox(src, type) {
        updateLightboxIndex(); 
        // Find index
        currentMediaIndex = galleryItems.findIndex(i => i.src === src);
        if(currentMediaIndex === -1) currentMediaIndex = 0;

        showLightboxMedia(src, type);
        
        document.getElementById('lightbox').classList.add('active');
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', lightboxKeys);
    }

    function closeLightbox() {
        // Stop video
        let vid = document.getElementById('lightboxVideo');
        vid.pause();
        vid.src = "";

        document.getElementById('lightbox').classList.remove('active');
        document.body.style.overflow = 'auto'; 
        document.removeEventListener('keydown', lightboxKeys);
    }

    function showLightboxMedia(src, type) {
        let img = document.getElementById('lightboxImg');
        let vid = document.getElementById('lightboxVideo');
        
        // Reset
        img.style.display = 'none';
        vid.style.display = 'none';
        vid.pause();

        if(type === 'image') {
            img.style.opacity = 0;
            img.style.display = 'block';
            img.src = src;
            setTimeout(() => { img.style.opacity = 1; }, 50);
        } 
        else if(type === 'video') {
            vid.style.display = 'block';
            vid.src = src;
            vid.play(); // Auto-play
        }
    }

    function changeImage(dir) {
        if(galleryItems.length === 0) return;
        currentMediaIndex += dir;
        if(currentMediaIndex >= galleryItems.length) currentMediaIndex = 0;
        if(currentMediaIndex < 0) currentMediaIndex = galleryItems.length - 1;
        
        let item = galleryItems[currentMediaIndex];
        showLightboxMedia(item.src, item.type);
    }

    function lightboxKeys(e) {
        if(e.key === 'Escape') closeLightbox();
        if(e.key === 'ArrowRight') changeImage(1);
        if(e.key === 'ArrowLeft') changeImage(-1);
    }
    
    updateLightboxIndex();
</script>

<?php include 'footer.php'; ?>
