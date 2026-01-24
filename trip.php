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

// Check Enrollment Status
$enrollment_status = 'none';
if ($role == 'student') {
    $e_check = $conn->query("SELECT status FROM enrollments WHERE trip_id = $trip_id AND student_id = $user_id");
    if ($e_check->num_rows > 0) {
        $enrollment_status = $e_check->fetch_assoc()['status'];
    }
}
// Admin and TripMaker (Owner) possess 'approved' level access implicitly
$has_access = false;
if ($role == 'admin') $has_access = true;
if ($role == 'tripmaker' && $trip['created_by'] == $user_id) $has_access = true;
if ($enrollment_status == 'approved') $has_access = true;


// --- HANDLERS (Only if has_access) ---

// Handle Chat Post
if ($has_access && isset($_POST['send_message'])) {
    $msg = $conn->real_escape_string($_POST['message']);
    if (!empty($msg)) {
        $stmt = $conn->prepare("INSERT INTO messages (trip_id, user_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $trip_id, $user_id, $msg);
        $stmt->execute();
        // Redirect to avoid resubmission
        header("Location: trip.php?id=$trip_id#chat");
        exit();
    }
}

// Handle File Upload
if ($has_access && isset($_FILES['media_file'])) {
    $target_dir = "uploads/";
    $file_name = basename($_FILES["media_file"]["name"]);
    // Simple unique name
    $target_file = $target_dir . time() . "_" . $file_name;
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    
    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
    && $imageFileType != "gif" && $imageFileType != "mp4" ) {
        $uploadOk = 0;
    }

    if ($uploadOk && move_uploaded_file($_FILES["media_file"]["tmp_name"], $target_file)) {
        $type = ($imageFileType == 'mp4') ? 'video' : 'image';
        $stmt = $conn->prepare("INSERT INTO media (trip_id, uploaded_by, file_path, type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $trip_id, $user_id, $target_file, $type);
        $stmt->execute();
        header("Location: trip.php?id=$trip_id#media");
        exit();
    }
}

?>

<!-- HERO SECTION -->
<div style="background: url('<?php echo htmlspecialchars($trip['image_url']); ?>') center/cover no-repeat; height: 400px; position: relative; display: flex; align-items: flex-end;">
    <div style="background: linear-gradient(to top, rgba(0,0,0,0.8), transparent); width: 100%; padding: 40px 0;">
        <div class="container text-white" style="color: white; position: relative;">
            <div style="background: var(--primary-color); display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 0.8rem; margin-bottom: 10px;">
                TRIP ID: #<?php echo $trip['id']; ?>
            </div>
            <h1 style="font-size: 3rem; margin-bottom: 10px;"><?php echo htmlspecialchars($trip['title']); ?></h1>
            <div style="display: flex; gap: 24px; font-size: 1.1rem; opacity: 0.9;">
                <span><i class="ri-map-pin-line"></i> <?php echo htmlspecialchars($trip['destination']); ?></span>
                <span><i class="ri-calendar-line"></i> <?php echo date('M d', strtotime($trip['start_date'])); ?> - <?php echo date('M d', strtotime($trip['end_date'])); ?></span>
                <span><i class="ri-user-smile-line"></i> Organized by <?php echo htmlspecialchars($trip['organizer']); ?></span>
            </div>
        </div>
    </div>
</div>

<div class="container section" style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px;">
    
    <!-- LEFT COLUMN -->
    <div>
        <!-- About -->
        <div style="background: var(--white); padding: 30px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); margin-bottom: 40px;">
            <h2 style="margin-bottom: 16px; color: var(--secondary-color);">About the Trip</h2>
            <p style="color: var(--text-color); line-height: 1.8; white-space: pre-line;">
                <?php echo htmlspecialchars($trip['description']); ?>
            </p>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #edf2f7;">
                <h3 style="font-size: 1.2rem; margin-bottom: 15px;">Checklist & Requirements</h3>
                <ul style="list-style: none; color: var(--text-light);">
                    <li style="margin-bottom: 8px;"><i class="ri-checkbox-circle-line" style="color: #48bb78; margin-right: 8px;"></i> Valid Passport / ID</li>
                    <li style="margin-bottom: 8px;"><i class="ri-checkbox-circle-line" style="color: #48bb78; margin-right: 8px;"></i> Travel Insurance (Recommended)</li>
                    <li style="margin-bottom: 8px;"><i class="ri-checkbox-circle-line" style="color: #48bb78; margin-right: 8px;"></i> Personal Medication</li>
                </ul>
            </div>
        </div>

        <?php if ($has_access): ?>
            <!-- GROUP CHAT -->
            <h2 id="chat" style="margin-bottom: 16px; color: var(--secondary-color);">Trip Chat</h2>
            <div class="chat-container">
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

            <!-- MEDIA GALLERY -->
            <h2 id="media" style="margin: 40px 0 16px; color: var(--secondary-color);">Shared Media</h2>
            <div style="background: var(--white); padding: 30px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
                
                <!-- Upload Form -->
                <form method="POST" enctype="multipart/form-data" style="margin-bottom: 30px; display: flex; gap: 10px; align-items: center; background: #f8fafc; padding: 15px; border-radius: var(--radius-md);">
                    <input type="file" name="media_file" required style="flex-grow: 1;">
                    <button type="submit" class="btn btn-primary">Upload</button>
                </form>

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 16px;">
                    <?php
                    $m_sql = "SELECT * FROM media WHERE trip_id = $trip_id ORDER BY uploaded_at DESC";
                    $m_result = $conn->query($m_sql);
                    if ($m_result->num_rows > 0) {
                        while($media = $m_result->fetch_assoc()) {
                            // In a real app we'd verify file existence.
                            echo '<a href="'.htmlspecialchars($media['file_path']).'" target="_blank" style="display:block; border-radius: 8px; overflow: hidden; height: 120px; position:relative;">';
                            if ($media['type'] == 'image') {
                                echo '<img src="'.htmlspecialchars($media['file_path']).'" style="width:100%; height:100%; object-fit:cover;">';
                            } else {
                                echo '<div style="background:#2d3748; width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:white;"><i class="ri-video-fill" style="font-size:2rem;"></i></div>';
                            }
                            echo '</a>';
                        }
                    } else {
                        echo '<p style="color: var(--text-light);">No media shared yet.</p>';
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT COLUMN (Sidebar) -->
    <div>
        <div style="background: var(--white); padding: 30px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); position: sticky; top: 100px;">
            <div style="text-align: center; margin-bottom: 24px;">
                <span style="font-size: 0.9rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px;">Total Cost</span>
                <div style="font-size: 2.5rem; font-weight: 700; color: var(--primary-color);">$<?php echo number_format($trip['cost']); ?></div>
            </div>

            <?php if (!$has_access): ?>
                <?php if ($enrollment_status == 'pending'): ?>
                    <button class="btn btn-outline" style="width: 100%;" disabled>Request Pending</button>
                    <p style="font-size: 0.85rem; color: var(--text-light); text-align: center; margin-top: 10px;">Typical response time: 24 hours</p>
                <?php elseif ($role == 'student'): ?>
                    <a href="actions.php?action=join_trip&trip_id=<?php echo $trip_id; ?>" class="btn btn-primary" style="width: 100%;">Join This Trip</a>
                    <p style="font-size: 0.85rem; color: var(--text-light); text-align: center; margin-top: 10px;">Instant confirmation email sent</p>
                <?php else: ?>
                    <button class="btn btn-outline" style="width: 100%;" disabled>For Students</button>
                <?php endif; ?>
            <?php else: ?>
                <div style="text-align: center; color: #2f855a; background: #f0fff4; padding: 12px; border-radius: 8px; font-weight: 600;">
                    <i class="ri-checkbox-circle-fill"></i> You have access
                </div>
                
                <div style="margin-top: 24px;">
                    <h4 style="margin-bottom: 12px; font-size: 1rem;">Participants</h4>
                    <div style="display: flex; gap: -8px;">
                        <!-- Mock Participants avatars -->
                        <?php 
                        // Realistically query enrollments
                        $p_sql = "SELECT u.name FROM enrollments e JOIN users u ON e.student_id = u.id WHERE e.trip_id = $trip_id AND e.status='approved' LIMIT 5";
                        $p_res = $conn->query($p_sql);
                        while($p = $p_res->fetch_assoc()) {
                            echo '<div title="'.htmlspecialchars($p['name']).'" style="width:32px; height:32px; border-radius:50%; background:#cbd5e0; border:2px solid white; display:flex; align-items:center; justify-content:center; font-size:0.8rem; margin-right: -10px; position:relative; font-weight:bold;">'.substr($p['name'],0,1).'</div>';
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

</div>

<!-- Auto-scroll to bottom of chat if accessed -->
<script>
    var chatBox = document.getElementById("chat-box");
    if (chatBox) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }
</script>

<?php include 'footer.php'; ?>
