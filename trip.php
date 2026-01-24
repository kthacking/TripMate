<?php
require_once 'db.php';
require_once 'auth.php';
session_start();
checkLogin();
include 'header.php';

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$trip_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch Trip Details
$sql = "SELECT t.*, u.name as organizer FROM trips t JOIN users u ON t.created_by = u.id WHERE t.id = $trip_id";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    echo "<div class='container section'>Trip not found.</div>";
    include 'footer.php';
    exit();
}
$trip = $result->fetch_assoc();

// Check Enrollment (Code remains similar to before...)
$enrollment_status = 'none';
if ($role == 'student') {
    $e_check = $conn->query("SELECT status FROM enrollments WHERE trip_id = $trip_id AND student_id = $user_id");
    if ($e_check->num_rows > 0) {
        $enrollment_status = $e_check->fetch_assoc()['status'];
    }
}
$has_access = false;
if ($role == 'admin') $has_access = true;
if ($role == 'tripmaker' && $trip['created_by'] == $user_id) $has_access = true;
if ($enrollment_status == 'approved') $has_access = true;

// --- HANDLERS ---
if ($has_access && isset($_POST['send_message'])) {
    $msg = $conn->real_escape_string($_POST['message']);
    if (!empty($msg)) {
        // ... (existing message logic)
        $stmt = $conn->prepare("INSERT INTO messages (trip_id, user_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $trip_id, $user_id, $msg);
        $stmt->execute();
        header("Location: trip.php?id=$trip_id#chat");
        exit();
    }
}

if ($has_access && isset($_FILES['media_file'])) {
    // ... (existing upload logic)
    $target_dir = "uploads/";
    $file_name = basename($_FILES["media_file"]["name"]);
    $file_type = isset($_POST['media_type']) ? $_POST['media_type'] : 'image'; // New Type Field
    $target_file = $target_dir . time() . "_" . $file_name;
    
    if (move_uploaded_file($_FILES["media_file"]["tmp_name"], $target_file)) {
        // Determine type based on selection or fallback
        $save_type = $file_type; 
        
        $stmt = $conn->prepare("INSERT INTO media (trip_id, uploaded_by, file_path, type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $trip_id, $user_id, $target_file, $save_type);
        $stmt->execute();
        header("Location: trip.php?id=$trip_id#media");
        exit();
    }
}

// Helpers
$stars = str_repeat("★", $trip['comfort_level']) . str_repeat("☆", 5 - $trip['comfort_level']);
?>

<!-- HERO (Same as before) -->
<div style="background: url('<?php echo htmlspecialchars($trip['image_url']); ?>') center/cover no-repeat; height: 450px; position: relative; display: flex; align-items: flex-end;">
    <div style="background: linear-gradient(to top, rgba(0,0,0,0.9), transparent); width: 100%; padding: 40px 0;">
        <div class="container text-white" style="color: white; position: relative;">
              <!-- ... existing hero content ... -->
             <h1 style="font-size: 3rem; margin-bottom: 10px;">
                <?php echo htmlspecialchars($trip['title']); ?>
                <?php if($role == 'admin' || ($role == 'tripmaker' && $trip['created_by'] == $user_id)): ?>
                    <a href="edit_trip.php?id=<?php echo $trip['id']; ?>" style="font-size: 1.2rem; color: white; opacity: 0.7; vertical-align: middle; margin-left: 10px;" title="Edit Trip"><i class="ri-edit-2-line"></i></a>
                <?php endif; ?>
            </h1>
             <!-- ... -->
        </div>
    </div>
</div>

<div class="container section" style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px;">
    
    <!-- LEFT COLUMN -->
    <div>
        <!-- Inclusions Blocks ... -->

        <!-- About -->
        <div style="background: var(--white); padding: 30px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); margin-bottom: 40px;">
            <h2 style="margin-bottom: 16px; color: var(--secondary-color);">About the Trip</h2>
            <p style="color: var(--text-color); line-height: 1.8; white-space: pre-line;">
                <?php echo htmlspecialchars($trip['description']); ?>
            </p>
            
            <!-- TIMELINE SECTION (A) -->
            <?php if(isset($trip['timeline']) && $trip['timeline']): ?>
            <div style="margin-top: 40px;">
                <h3 style="font-size: 1.2rem; margin-bottom: 20px;">Trip Timeline</h3>
                <div class="timeline">
                    <?php 
                    // Simple parsing: Newlines
                    $days = explode("\n", $trip['timeline']);
                    foreach($days as $index => $day): 
                        if(trim($day) == '') continue;
                    ?>
                    <div class="timeline-item">
                        <div class="timeline-marker"><?php echo $index + 1; ?></div>
                        <div style="padding-top: 4px;">
                            <?php echo htmlspecialchars($day); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- CHECKLIST SECTION (B) (Checkboxes visible to all) -->
        <?php if(isset($trip['checklist']) && $trip['checklist']): ?>
        <div style="background: var(--white); padding: 30px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); margin-bottom: 40px;">
             <h2 style="margin-bottom: 16px; color: var(--secondary-color);">Essential Checklist</h2>
             <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                 <?php 
                 $items = explode("\n", $trip['checklist']);
                 foreach($items as $item): 
                     if(trim($item) == '') continue;
                 ?>
                 <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 8px; border-radius: 8px; background: #f8fafc;">
                     <input type="checkbox" style="width: 18px; height: 18px; accent-color: var(--primary-color);">
                     <span><?php echo htmlspecialchars($item); ?></span>
                 </label>
                 <?php endforeach; ?>
             </div>
        </div>
        <?php endif; ?>

        <?php if ($has_access): ?>
            
            <!-- MEDIA GALLERY (C) - Tabs -->
            <h2 id="media" style="margin-bottom: 16px; color: var(--secondary-color);">Shared Media</h2>
            
            <div style="background: var(--white); padding: 30px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); margin-bottom: 40px;">
                
                <!-- Upload (Modified) -->
                <form method="POST" enctype="multipart/form-data" style="margin-bottom: 30px; background: #f8fafc; padding: 15px; border-radius: var(--radius-md);">
                    <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                        <input type="file" name="media_file" required style="flex-grow: 1;">
                        <select name="media_type" class="form-select" style="width: 120px;">
                            <option value="image">Photo</option>
                            <option value="video">Video</option>
                            <option value="document">Doc</option>
                        </select>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>

                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab-btn active" onclick="filterMedia('all', this)">All</button>
                    <button class="tab-btn" onclick="filterMedia('image', this)">Photos</button>
                    <button class="tab-btn" onclick="filterMedia('video', this)">Videos</button>
                    <button class="tab-btn" onclick="filterMedia('document', this)">Docs</button>
                </div>

                <div id="media-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 16px;">
                    <?php
                    $m_sql = "SELECT * FROM media WHERE trip_id = $trip_id ORDER BY uploaded_at DESC";
                    $m_result = $conn->query($m_sql);
                    if ($m_result->num_rows > 0) {
                        while($media = $m_result->fetch_assoc()) {
                            $type_icon = ($media['type'] == 'video') ? 'ri-video-fill' : (($media['type'] == 'document') ? 'ri-file-text-fill' : '');
                            
                            echo '<a href="'.htmlspecialchars($media['file_path']).'" target="_blank" class="media-item" data-type="'.$media['type'].'" style="display:block; border-radius: 8px; overflow: hidden; height: 120px; position:relative; border: 1px solid #edf2f7;">';
                            
                            if ($media['type'] == 'image') {
                                echo '<img src="'.htmlspecialchars($media['file_path']).'" style="width:100%; height:100%; object-fit:cover;">';
                            } else {
                                echo '<div style="background:#f8fafc; width:100%; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; color: var(--secondary-color);">';
                                echo '<i class="'.$type_icon.'" style="font-size:2rem; margin-bottom:5px;"></i>';
                                echo '<span style="font-size:0.8rem;">'.ucfirst($media['type']).'</span>';
                                echo '</div>';
                            }
                            echo '</a>';
                        }
                    } else {
                        echo '<p style="color: var(--text-light);">No media shared yet.</p>';
                    }
                    ?>
                </div>
            </div>

            <!-- GROUP CHAT (Below media for flow) -->
            <h2 id="chat" style="margin-bottom: 16px; color: var(--secondary-color);">Trip Chat</h2>
            <div class="chat-container">
                 <!-- ... existing chat ... -->
                 <div class="chat-messages" id="chat-box">
                    <?php
                    $c_sql = "SELECT m.*, u.name FROM messages m JOIN users u ON m.user_id = u.id WHERE m.trip_id = $trip_id ORDER BY m.created_at ASC";
                    $c_result = $conn->query($c_sql);
                    if ($c_result->num_rows > 0) {
                        while($msg = $c_result->fetch_assoc()) {
                            $is_me = ($msg['user_id'] == $user_id);
                            echo '<div class="message '.($is_me ? 'self' : 'other').'">';
                            if (!$is_me) echo '<strong style="display:block; font-size:0.75rem; margin-bottom:2px;">'.htmlspecialchars($msg['name']).'</strong>';
                            echo htmlspecialchars($msg['message']);
                            echo '</div>';
                        }
                    } else {
                         echo '<p style="text-align:center; color:#a0aec0; margin-top:20px;">Start the conversation!</p>';
                    }
                    ?>
                </div>
                <form class="chat-input-area" method="POST">
                    <input type="text" name="message" class="form-control" placeholder="Type a message..." required autocomplete="off">
                    <button type="submit" name="send_message" class="btn btn-primary" style="padding: 0 20px;"><i class="ri-send-plane-fill"></i></button>
                </form>
            </div>

        <?php endif; ?>
    </div>

    <!-- RIGHT COLUMN -->
    <div>
         <!-- ... existing sidebar details ... -->
         <div style="background: var(--white); padding: 30px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); position: sticky; top: 100px;">
            <div style="text-align: center; margin-bottom: 24px;">
                <span style="font-size: 0.9rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px;">Total Cost</span>
                <div style="font-size: 2.5rem; font-weight: 700; color: var(--primary-color);">$<?php echo number_format($trip['cost']); ?></div>
            </div>
            
            <!-- Join Button / Status Logic (Existing) -->
            <?php if (!$has_access): ?>
                 <?php if ($enrollment_status == 'pending'): ?>
                    <button class="btn btn-outline" style="width: 100%;" disabled>Request Pending</button>
                    <p style="font-size: 0.85rem; color: var(--text-light); text-align: center; margin-top: 10px;">Typical response time: 24 hours</p>
                <?php elseif ($role == 'student'): ?>
                    <!-- Deadline Check -->
                    <?php 
                        $can_join = true;
                        if($trip['registration_deadline'] && strtotime($trip['registration_deadline']) < time()) {
                            $can_join = false;
                        }
                        if($trip['max_participants'] > 0) {
                             $curr = $conn->query("SELECT COUNT(*) as c FROM enrollments WHERE trip_id=$trip_id AND status='approved'")->fetch_assoc()['c'];
                             if($curr >= $trip['max_participants']) $can_join = false;
                        }
                    ?>
                    
                    <?php if($can_join): ?>
                        <a href="actions.php?action=join_trip&trip_id=<?php echo $trip_id; ?>" class="btn btn-primary" style="width: 100%;">Join This Trip</a>
                        <p style="font-size: 0.85rem; color: var(--text-light); text-align: center; margin-top: 10px;">Instant confirmation email sent</p>
                    <?php else: ?>
                        <button class="btn btn-outline" style="width: 100%; color: #c53030; border-color: #c53030;" disabled>Unavailable / Full</button>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <button class="btn btn-outline" style="width: 100%;" disabled>For Students</button>
                <?php endif; ?>
            <?php else: ?>
                <div style="text-align: center; color: #2f855a; background: #f0fff4; padding: 12px; border-radius: 8px; font-weight: 600;">
                    <i class="ri-checkbox-circle-fill"></i> You have access
                </div>
            <?php endif; ?>
         </div>
    </div>
</div>

<script>
    // Tab Filter Logic
    function filterMedia(type, btn) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        document.querySelectorAll('.media-item').forEach(item => {
            if (type === 'all' || item.dataset.type === type) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    var chatBox = document.getElementById("chat-box");
    if (chatBox) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }
</script>

<?php include 'footer.php'; ?>
