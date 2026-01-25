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

if ($has_access && isset($_POST['mark_completed'])) {
    if ($role == 'admin' || $trip['created_by'] == $user_id) {
        $conn->query("UPDATE trips SET status='completed' WHERE id=$trip_id");
        header("Location: trip.php?id=$trip_id&msg=completed");
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

        <!-- REVIEW SECTION -->
        <?php
        // 1. Fetch Reviews Stats
        $avg_rating = 0.0;
        $total_reviews = 0;
        try {
            $r_check = $conn->query("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM reviews WHERE trip_id = $trip_id");
            if ($r_check) {
                $r_stats = $r_check->fetch_assoc();
                $avg_rating = round($r_stats['avg_rating'], 1);
                $total_reviews = $r_stats['total'];
            }
        } catch (Exception $e) { 
            // Table might not exist yet
        }

        // 2. Handle Review Submission
        $can_review = false;
        if ($role == 'student' && $enrollment_status == 'approved' && $trip['status'] == 'completed') {
            $has_reviewed = $conn->query("SELECT id FROM reviews WHERE trip_id=$trip_id AND student_id=$user_id")->num_rows > 0;
            if (!$has_reviewed) {
                $can_review = true;
                if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
                    $rating = intval($_POST['rating']);
                    $review_text = $conn->real_escape_string($_POST['review_text']);
                    $tm_id = $trip['created_by'];
                    
                    $stmt = $conn->prepare("INSERT INTO reviews (trip_id, student_id, tripmaker_id, rating, review_text) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iiiis", $trip_id, $user_id, $tm_id, $rating, $review_text);
                    if ($stmt->execute()) {
                        echo "<script>window.location.href='trip.php?id=$trip_id&msg=reviewed';</script>";
                        exit();
                    }
                }
            }
        }
        ?>

        <div style="background: var(--white); padding: 30px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); margin-bottom: 40px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h2 style="margin: 0; color: var(--secondary-color);">Reviews</h2>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 1.5rem; color: #F6E05E; font-weight: 700;">★ <?php echo $avg_rating ?: '0.0'; ?></span>
                    <span style="color: var(--text-light);">(/5 based on <?php echo $total_reviews; ?> reviews)</span>
                </div>
            </div>

            <!-- Review Form -->
            <?php if($can_review): ?>
                <div style="background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #edf2f7;">
                    <h4 style="margin-bottom: 12px; color: var(--secondary-color);">Write a Review</h4>
                    <form method="POST">
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; font-size: 0.9rem; margin-bottom: 4px; color: var(--text-light);">Rating</label>
                            <div class="star-rating" style="justify-content: flex-end;"> 
                                <!-- Reuse existing star rating css logic but left-aligned concept needed? Existing is row-reverse right-aligned. Let's stick to standard behavior or just use simple select for reliability if CSS is tricky. Actually, standard CSS from edit_trip works. -->
                                <input type="radio" id="r5" name="rating" value="5" required/><label for="r5">★</label>
                                <input type="radio" id="r4" name="rating" value="4" /><label for="r4">★</label>
                                <input type="radio" id="r3" name="rating" value="3" /><label for="r3">★</label>
                                <input type="radio" id="r2" name="rating" value="2" /><label for="r2">★</label>
                                <input type="radio" id="r1" name="rating" value="1" /><label for="r1">★</label>
                            </div>
                        </div>
                        <div style="margin-bottom: 12px;">
                            <textarea name="review_text" class="form-control" rows="2" placeholder="Share your experience (optional)..."></textarea>
                        </div>
                        <button type="submit" name="submit_review" class="btn btn-primary" style="padding: 8px 24px;">Submit Review</button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Recent Reviews -->
            <div class="reviews-list">
                <?php
                $rev_sql = "SELECT r.*, u.name FROM reviews r JOIN users u ON r.student_id = u.id WHERE r.trip_id = $trip_id ORDER BY r.created_at DESC LIMIT 5";
                $revs = $conn->query($rev_sql);
                if ($revs->num_rows > 0) {
                    while($rev = $revs->fetch_assoc()) {
                        $r_stars = str_repeat("★", $rev['rating']) . str_repeat("☆", 5 - $rev['rating']);
                        echo '<div style="padding: 16px 0; border-bottom: 1px solid #edf2f7;">';
                        echo '<div style="display: flex; justify-content: space-between; margin-bottom: 4px;">';
                        echo '<strong style="color: var(--secondary-color);">'.htmlspecialchars($rev['name']).'</strong>';
                        echo '<span style="color: #F6E05E; letter-spacing: 2px;">'.$r_stars.'</span>';
                        echo '</div>';
                        if (!empty($rev['review_text'])) {
                            echo '<p style="color: var(--text-color); font-size: 0.95rem;">'.htmlspecialchars($rev['review_text']).'</p>';
                        }
                        echo '<small style="color: var(--text-light);">'.date('M d, Y', strtotime($rev['created_at'])).'</small>';
                        echo '</div>';
                    }
                } else {
                    echo '<p style="color: var(--text-light);">No reviews yet.</p>';
                }
                ?>
            </div>
        </div>

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
            
             <!-- Status Badge -->
             <div style="text-align: center; margin-bottom: 20px;">
                <span class="badge <?php echo ($trip['status'] == 'active') ? 'badge-green' : (($trip['status'] == 'completed') ? 'badge-purple' : 'badge-gray'); ?>" style="font-size: 1rem; padding: 6px 16px;">
                    <?php echo ucfirst($trip['status']); ?>
                </span>
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
                        if($trip['status'] != 'active') {
                            $can_join = false;
                        }
                    ?>
                    
                    <?php if($can_join): ?>
                        <a href="actions.php?action=join_trip&trip_id=<?php echo $trip_id; ?>" class="btn btn-primary" style="width: 100%;">Join This Trip</a>
                        <p style="font-size: 0.85rem; color: var(--text-light); text-align: center; margin-top: 10px;">Instant confirmation email sent</p>
                    <?php else: ?>
                        <button class="btn btn-outline" style="width: 100%; color: #c53030; border-color: #c53030;" disabled>Unavailable / Closed</button>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <button class="btn btn-outline" style="width: 100%;" disabled>For Students</button>
                <?php endif; ?>
            <?php else: ?>
                <div style="text-align: center; color: #2f855a; background: #f0fff4; padding: 12px; border-radius: 8px; font-weight: 600; margin-bottom: 16px;">
                    <i class="ri-checkbox-circle-fill"></i> You have access
                </div>
                
                <!-- TripMaker Actions -->
                <?php if(($role == 'admin' || $trip['created_by'] == $user_id) && $trip['status'] == 'active'): ?>
                    <form method="POST" onsubmit="return confirm('Mark this trip as completed? This will allow students to leave reviews.');">
                        <button type="submit" name="mark_completed" class="btn btn-primary" style="width: 100%; background: var(--secondary-color);">Mark as Completed</button>
                    </form>
                <?php endif; ?>
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
