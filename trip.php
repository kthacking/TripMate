<?php
require_once 'db.php';
require_once 'auth.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
checkLogin();
include 'header.php';

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$trip_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch Trip Details with Prepared Statement
$stmt = $conn->prepare("SELECT t.*, u.name as organizer FROM trips t JOIN users u ON t.created_by = u.id WHERE t.id = ?");
$stmt->bind_param("i", $trip_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<div class='container section text-center'>
            <div style='padding: 60px; background: white; border-radius: 20px; box-shadow: var(--shadow-md);'>
                <h1 style='color: var(--secondary-color);'>Trip not found</h1>
                <p style='color: var(--text-light); margin: 20px 0;'>The trip you are looking for does not exist or has been removed.</p>
                <a href='dashboard.php' class='btn btn-primary'>Back to Dashboard</a>
            </div>
          </div>";
    include 'footer.php';
    exit();
}
$trip = $result->fetch_assoc();

// Check Enrollment
$enrollment_status = 'none';
if ($role == 'student') {
    $e_stmt = $conn->prepare("SELECT status FROM enrollments WHERE trip_id = ? AND student_id = ?");
    $e_stmt->bind_param("ii", $trip_id, $user_id);
    $e_stmt->execute();
    $e_res = $e_stmt->get_result();
    if ($e_res->num_rows > 0) {
        $enrollment_status = $e_res->fetch_assoc()['status'];
    }
}

$has_access = false;
if ($role == 'admin') $has_access = true;
if ($role == 'tripmaker' && $trip['created_by'] == $user_id) $has_access = true;
if ($enrollment_status == 'approved') $has_access = true;

// --- HANDLERS ---
if ($has_access && isset($_POST['send_message'])) {
    $msg = trim($_POST['message']);
    if (!empty($msg)) {
        $msg_stmt = $conn->prepare("INSERT INTO messages (trip_id, user_id, message) VALUES (?, ?, ?)");
        $msg_stmt->bind_param("iis", $trip_id, $user_id, $msg);
        $msg_stmt->execute();
        header("Location: trip.php?id=$trip_id#chat");
        exit();
    }
}

if ($has_access && isset($_POST['mark_completed'])) {
    if ($role == 'admin' || $trip['created_by'] == $user_id) {
        $comp_stmt = $conn->prepare("UPDATE trips SET status='completed' WHERE id = ?");
        $comp_stmt->bind_param("i", $trip_id);
        $comp_stmt->execute();
        header("Location: trip.php?id=$trip_id&msg=completed");
        exit();
    }
}

// Helpers
$stars = str_repeat("★", $trip['comfort_level']) . str_repeat("☆", 5 - $trip['comfort_level']);
$days_left = ceil((strtotime($trip['registration_deadline']) - time()) / 86400);
?>

<!-- HERO SECTION -->
<div style="background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.7)), url('<?php echo htmlspecialchars($trip['image_url']); ?>') center/cover no-repeat; height: 500px; position: relative; display: flex; align-items: flex-end; margin-top: -80px;">
    <div style="width: 100%; padding: 60px 0;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 20px;">
                <div style="color: white; flex: 1; min-width: 300px;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                        <span class="badge" style="background: rgba(255,255,255,0.2); color: white; backdrop-filter: blur(10px);"><?php echo htmlspecialchars($trip['trip_type']); ?></span>
                        <span style="color: #F6E05E; font-weight: 700;"><?php echo $stars; ?></span>
                    </div>
                    <h1 style="font-size: 3.5rem; line-height: 1.1; margin-bottom: 16px; text-shadow: 0 4px 10px rgba(0,0,0,0.3);">
                        <?php echo htmlspecialchars($trip['title']); ?>
                        <?php if($role == 'admin' || ($role == 'tripmaker' && $trip['created_by'] == $user_id)): ?>
                            <a href="edit_trip.php?id=<?php echo $trip['id']; ?>" style="font-size: 1.5rem; color: white; opacity: 0.8; vertical-align: middle; margin-left: 15px;" title="Edit Trip"><i class="ri-edit-2-line"></i></a>
                        <?php endif; ?>
                    </h1>
                    <div style="display: flex; gap: 24px; font-size: 1.1rem; opacity: 0.9;">
                        <span><i class="ri-map-pin-2-fill"></i> <?php echo htmlspecialchars($trip['destination']); ?></span>
                        <span><i class="ri-calendar-event-fill"></i> <?php echo date('M d', strtotime($trip['start_date'])); ?> - <?php echo date('M d, Y', strtotime($trip['end_date'])); ?></span>
                        <span><i class="ri-user-star-fill"></i> Hosted by <?php echo htmlspecialchars($trip['organizer']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container section" style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px; align-items: start;">
    
    <!-- LEFT COLUMN -->
    <div>
        <!-- Inclusions Grid -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 40px;">
            <div style="background: #f0fff4; padding: 24px; border-radius: 20px; border: 1px solid #c6f6d5;">
                <h4 style="color: #2f855a; margin-bottom: 12px;"><i class="ri-checkbox-circle-fill"></i> What's Included</h4>
                <ul style="font-size: 0.95rem; color: #276749; list-style: none; padding: 0;">
                    <?php 
                    $included_clean = str_replace(['\r\n', '\r', '\n'], "\n", $trip['included_items']);
                    $included = preg_split('/\R/u', $included_clean);
                    foreach($included as $item) if(trim($item)) echo "<li style='margin-bottom: 5px; display: flex; align-items: flex-start; gap: 8px;'><i class='ri-check-line' style='margin-top: 3px;'></i> <span>".htmlspecialchars(trim($item))."</span></li>";
                    ?>
                </ul>
            </div>
            <div style="background: #fff5f5; padding: 24px; border-radius: 20px; border: 1px solid #fed7d7;">
                <h4 style="color: #c53030; margin-bottom: 12px;"><i class="ri-close-circle-fill"></i> Not Included</h4>
                <ul style="font-size: 0.95rem; color: #9b2c2c; list-style: none; padding: 0;">
                    <?php 
                    $not_included_clean = str_replace(['\r\n', '\r', '\n'], "\n", $trip['not_included_items']);
                    $not_included = preg_split('/\R/u', $not_included_clean);
                    foreach($not_included as $item) if(trim($item)) echo "<li style='margin-bottom: 5px; display: flex; align-items: flex-start; gap: 8px;'><i class='ri-close-line' style='margin-top: 3px;'></i> <span>".htmlspecialchars(trim($item))."</span></li>";
                    ?>
                </ul>
            </div>
        </div>

        <!-- About -->
        <div style="background: var(--white); padding: 35px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); margin-bottom: 40px;">
            <h2 style="margin-bottom: 20px; color: var(--secondary-color); font-size: 1.8rem;">About the Trip</h2>
            <div style="color: var(--text-color); line-height: 1.8; white-space: pre-line; font-size: 1.1rem; opacity: 0.9;">
                <?php 
                $description_clean = str_replace(['\r\n', '\r', '\n'], "\n", $trip['description']);
                echo htmlspecialchars($description_clean); 
                ?>
            </div>
            
            <!-- Timeline Section -->
            <?php if(!empty($trip['timeline'])): ?>
            <div style="margin-top: 50px;">
                <h3 style="font-size: 1.4rem; margin-bottom: 25px; color: var(--secondary-color);">Detailed Itinerary</h3>
                <div class="timeline" style="position: relative; padding-left: 30px;">
                    <div style="position: absolute; left: 0; top: 0; bottom: 0; width: 2px; background: #e2e8f0;"></div>
                    <?php 
                    $timeline_clean = str_replace(['\r\n', '\r', '\n'], "\n", $trip['timeline']);
                    $days = preg_split('/\R/u', $timeline_clean);
                    $day_count = 1;
                    foreach($days as $day): 
                        if(trim($day) == '') continue;
                    ?>
                    <div style="position: relative; margin-bottom: 30px;">
                        <div style="position: absolute; left: -36px; top: 0; width: 14px; height: 14px; border-radius: 50%; background: var(--primary-color); border: 3px solid white; box-shadow: 0 0 0 4px #e0e7ff;"></div>
                        <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #edf2f7;">
                            <strong style="color: var(--primary-color); display: block; margin-bottom: 5px;">Day <?php echo $day_count++; ?></strong>
                            <div style="color: var(--text-color);"><?php echo htmlspecialchars(trim($day)); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Checklist Section -->
        <?php if(!empty($trip['checklist'])): ?>
        <div style="background: var(--white); padding: 35px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); margin-bottom: 40px;">
             <h2 style="margin-bottom: 24px; color: var(--secondary-color); font-size: 1.8rem;">Essential Checklist</h2>
             <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                 <?php 
                 $checklist_clean = str_replace(['\r\n', '\r', '\n'], "\n", $trip['checklist']);
                 $items = preg_split('/\R/u', $checklist_clean);
                 foreach($items as $item): 
                     if(trim($item) == '') continue;
                 ?>
                 <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 15px; border-radius: 12px; background: #f8fafc; border: 1px solid #edf2f7; transition: var(--transition);">
                     <input type="checkbox" style="width: 20px; height: 20px; accent-color: var(--primary-color);">
                     <span style="font-weight: 500;"><?php echo htmlspecialchars(trim($item)); ?></span>
                 </label>
                 <?php endforeach; ?>
             </div>
        </div>
        <?php endif; ?>

        <!-- SHARED MEDIA SECTION -->
        <?php if ($has_access): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 id="media" style="margin: 0; color: var(--secondary-color); font-size: 1.8rem;">Shared Media</h2>
                <a href="trip_gallery.php?id=<?php echo $trip_id; ?>" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.9rem;">View All Gallery</a>
            </div>
            
            <div style="background: var(--white); padding: 30px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); margin-bottom: 40px;">
                <div style="display: flex; gap: 15px; margin-bottom: 24px; overflow-x: auto; padding-bottom: 10px;">
                    <?php
                    $prev_stmt = $conn->prepare("SELECT file_path, type FROM media WHERE trip_id = ? ORDER BY uploaded_at DESC LIMIT 5");
                    $prev_stmt->bind_param("i", $trip_id);
                    $prev_stmt->execute();
                    $prev_res = $prev_stmt->get_result();
                    if ($prev_res->num_rows > 0) {
                        while($p = $prev_res->fetch_assoc()) {
                            if($p['type'] == 'image') {
                                echo '<img src="'.htmlspecialchars($p['file_path']).'" style="width: 120px; height: 120px; object-fit: cover; border-radius: 12px; flex-shrink: 0; box-shadow: var(--shadow-sm);">';
                            } else {
                                echo '<div style="width: 120px; height: 120px; background: #f8fafc; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #a0aec0; border: 1px solid #edf2f7; flex-shrink: 0;"><i class="ri-video-line"></i></div>';
                            }
                        }
                    } else {
                        echo '<div style="background: #f8fafc; width: 100%; padding: 40px; border-radius: 12px; color: var(--text-light); text-align: center; border: 2px dashed #e2e8f0;"><i class="ri-gallery-line" style="font-size: 2.5rem; display: block; margin-bottom: 10px;"></i>No media shared yet. Be the first to upload!</div>';
                    }
                    ?>
                </div>
                <div style="text-align: center;">
                    <h3 style="margin-bottom: 8px; font-size: 1.25rem;">Capture the Moments</h3>
                    <p style="color: var(--text-light); margin-bottom: 20px; font-size: 0.95rem;">Collaborate with your fellow travelers by sharing photos and videos.</p>
                    <a href="trip_gallery.php?id=<?php echo $trip_id; ?>#upload" class="btn btn-primary" style="padding: 12px 30px;"><i class="ri-upload-cloud-2-line"></i> Upload Photos</a>
                </div>
            </div>

            <!-- GROUP CHAT -->
            <h2 id="chat" style="margin-bottom: 20px; color: var(--secondary-color); font-size: 1.8rem;">Group Discussions</h2>
            <div class="chat-container" style="height: 500px; border: 1px solid #edf2f7;">
                 <div class="chat-messages" id="chat-box" style="padding: 30px;">
                    <?php
                    $c_stmt = $conn->prepare("SELECT m.*, u.name FROM messages m JOIN users u ON m.user_id = u.id WHERE m.trip_id = ? ORDER BY m.created_at ASC");
                    $c_stmt->bind_param("i", $trip_id);
                    $c_stmt->execute();
                    $c_result = $c_stmt->get_result();
                    if ($c_result->num_rows > 0) {
                        while($msg = $c_result->fetch_assoc()) {
                            $is_me = ($msg['user_id'] == $user_id);
                            echo '<div class="message '.($is_me ? 'self' : 'other').'" style="'.($is_me ? 'margin-left: auto;' : '').' box-shadow: var(--shadow-sm);">';
                            if (!$is_me) echo '<strong style="display:block; font-size:0.7rem; margin-bottom:4px; opacity: 0.8;">'.htmlspecialchars($msg['name']).'</strong>';
                            echo '<div style="font-size: 0.95rem;">'.htmlspecialchars($msg['message']).'</div>';
                            echo '<div style="font-size: 0.65rem; margin-top: 4px; opacity: 0.6; text-align: right;">'.date('H:i', strtotime($msg['created_at'])).'</div>';
                            echo '</div>';
                        }
                    } else {
                         echo '<div style="text-align:center; padding: 40px; color:#a0aec0;"><i class="ri-chat-3-line" style="font-size: 3rem; display: block; margin-bottom: 10px;"></i>Start the conversation with your team!</div>';
                    }
                    ?>
                </div>
                <form class="chat-input-area" method="POST" style="padding: 20px; background: white;">
                    <input type="text" name="message" class="form-control" placeholder="Type your message here..." required autocomplete="off" style="border-radius: 30px; padding-left: 20px;">
                    <button type="submit" name="send_message" class="btn btn-primary" style="border-radius: 50%; width: 45px; height: 45px; padding: 0; display: flex; align-items: center; justify-content: center;"><i class="ri-send-plane-fill"></i></button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT COLUMN (SIDEBAR) -->
    <div>
        <div style="background: var(--white); padding: 35px; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); position: sticky; top: 100px; border: 1px solid #edf2f7;">
            <div style="text-align: center; margin-bottom: 30px; padding-bottom: 25px; border-bottom: 1px solid #edf2f7;">
                <span class="badge <?php echo ($trip['status'] == 'active') ? 'badge-green' : (($trip['status'] == 'completed') ? 'badge-purple' : 'badge-gray'); ?>" style="font-size: 0.9rem; padding: 6px 18px; margin-bottom: 15px;">
                    <i class="ri-checkbox-blank-circle-fill" style="font-size: 0.5rem; vertical-align: middle; margin-right: 5px;"></i> <?php echo strtoupper($trip['status']); ?>
                </span>
                <div style="font-size: 0.85rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 5px;">Price per person</div>
                <div style="font-size: 3rem; font-weight: 800; color: var(--secondary-color);">$<?php echo number_format($trip['cost']); ?></div>
            </div>
            
            <!-- Quick Stats -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px;">
                <div style="text-align: center; background: #f8fafc; padding: 15px; border-radius: 12px;">
                    <i class="ri-group-line" style="color: var(--primary-color); font-size: 1.2rem;"></i>
                    <div style="font-size: 0.75rem; color: var(--text-light); margin-top: 5px;">Max Seats</div>
                    <strong style="font-size: 1.1rem;"><?php echo $trip['max_participants'] ?: 'Unlimited'; ?></strong>
                </div>
                <div style="text-align: center; background: #f8fafc; padding: 15px; border-radius: 12px;">
                    <i class="ri-time-line" style="color: var(--primary-color); font-size: 1.2rem;"></i>
                    <div style="font-size: 0.75rem; color: var(--text-light); margin-top: 5px;">Duration</div>
                    <strong style="font-size: 1.1rem;"><?php 
                        $d1 = new DateTime($trip['start_date']);
                        $d2 = new DateTime($trip['end_date']);
                        echo $d1->diff($d2)->days + 1;
                    ?> Days</strong>
                </div>
            </div>

            <!-- Booking Section -->
            <?php if (!$has_access): ?>
                 <?php if ($enrollment_status == 'pending'): ?>
                    <div style="background: #FFFBEB; border: 1px solid #FEF3C7; padding: 20px; border-radius: 15px; text-align: center;">
                        <i class="ri-time-fill" style="color: #D97706; font-size: 2rem;"></i>
                        <h4 style="color: #92400E; margin: 10px 0 5px;">Request Pending</h4>
                        <p style="font-size: 0.85rem; color: #B45309;">The organizer is reviewing your request. You'll be notified soon.</p>
                    </div>
                <?php elseif ($role == 'student'): ?>
                    <?php 
                        $can_join = true;
                        $error_msg = "";
                        if($trip['registration_deadline'] && strtotime($trip['registration_deadline']) < time()) {
                            $can_join = false; $error_msg = "Deadline passed";
                        }
                        if($trip['max_participants'] > 0) {
                             $curr = $conn->query("SELECT COUNT(*) as c FROM enrollments WHERE trip_id=$trip_id AND status='approved'")->fetch_assoc()['c'];
                             if($curr >= $trip['max_participants']) { $can_join = false; $error_msg = "Trip is Full"; }
                        }
                        if($trip['status'] != 'active') {
                            $can_join = false; $error_msg = "Booking Closed";
                        }
                    ?>
                    
                    <?php if($can_join): ?>
                        <div style="margin-bottom: 20px;">
                            <p style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 15px; text-align: center;"><i class="ri-error-warning-line"></i> Deadline: <?php echo date('M d, Y', strtotime($trip['registration_deadline'])); ?> (<?php echo $days_left; ?> days left)</p>
                            <a href="actions.php?action=join_trip&trip_id=<?php echo $trip_id; ?>" class="btn btn-primary" style="width: 100%; padding: 18px; font-size: 1.1rem; border-radius: 15px;">Reserve My Spot</a>
                        </div>
                    <?php else: ?>
                        <div style="background: #FEE2E2; border: 1px solid #FECACA; padding: 20px; border-radius: 15px; text-align: center; color: #991B1B;">
                            <i class="ri-error-warning-fill" style="font-size: 2rem;"></i>
                            <h4 style="margin: 10px 0 5px;"><?php echo $error_msg; ?></h4>
                            <p style="font-size: 0.85rem; opacity: 0.8;">Reservations for this trip are no longer being accepted.</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="text-align: center; color: var(--text-light); font-size: 0.9rem; padding: 20px; background: #f8fafc; border-radius: 12px;">Only students can join trips.</div>
                <?php endif; ?>
            <?php else: ?>
                <div style="text-align: center; color: #065F46; background: #D1FAE5; padding: 20px; border-radius: 15px; font-weight: 700; border: 1px solid #A7F3D0; margin-bottom: 20px;">
                    <i class="ri-checkbox-circle-fill" style="font-size: 1.5rem; display: block; margin-bottom: 5px;"></i> YOU ARE ENROLLED
                </div>
                
                <?php if(($role == 'admin' || $trip['created_by'] == $user_id) && $trip['status'] == 'active'): ?>
                    <form method="POST" onsubmit="return confirm('Mark this trip as completed? This will allow students to leave reviews.');">
                        <button type="submit" name="mark_completed" class="btn btn-primary" style="width: 100%; background: var(--secondary-color); padding: 15px; border-radius: 12px;">Finalize Trip</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- REVIEWS SIDEBAR SECTION -->
        <div style="background: var(--white); padding: 30px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); margin-top: 30px; border: 1px solid #edf2f7;">
            <?php
            $avg_rating = 0.0; $total_reviews = 0;
            $r_stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM reviews WHERE trip_id = ?");
            $r_stmt->bind_param("i", $trip_id);
            $r_stmt->execute();
            $r_stats = $r_stmt->get_result()->fetch_assoc();
            $avg_rating = round($r_stats['avg_rating'], 1);
            $total_reviews = $r_stats['total'];
            ?>
            <h3 style="margin-bottom: 20px; color: var(--secondary-color);">Guest Reviews</h3>
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px;">
                <div style="font-size: 2.5rem; font-weight: 800; color: #F6E05E;"><?php echo $avg_rating ?: '---'; ?></div>
                <div>
                    <div style="color: #F6E05E; font-size: 1.1rem;"><?php echo str_repeat("★", floor($avg_rating)) . str_repeat("☆", 5 - floor($avg_rating)); ?></div>
                    <div style="color: var(--text-light); font-size: 0.85rem;">Based on <?php echo $total_reviews; ?> reviews</div>
                </div>
            </div>

            <?php
            // Review Button Logic
            if ($role == 'student' && $enrollment_status == 'approved' && $trip['status'] == 'completed') {
                $rev_check = $conn->prepare("SELECT id FROM reviews WHERE trip_id = ? AND student_id = ?");
                $rev_check->bind_param("ii", $trip_id, $user_id);
                $rev_check->execute();
                if ($rev_check->get_result()->num_rows == 0):
            ?>
                <div style="background: #f8fafc; padding: 20px; border-radius: 15px; margin-bottom: 25px; border: 1px solid #e2e8f0;">
                    <h4 style="margin-bottom: 12px; font-size: 1rem;">Share Your Experience</h4>
                    <form method="POST">
                        <div class="star-rating" style="margin-bottom: 15px;">
                            <input type="radio" id="r5" name="rating" value="5" required/><label for="r5">★</label>
                            <input type="radio" id="r4" name="rating" value="4" /><label for="r4">★</label>
                            <input type="radio" id="r3" name="rating" value="3" /><label for="r3">★</label>
                            <input type="radio" id="r2" name="rating" value="2" /><label for="r2">★</label>
                            <input type="radio" id="r1" name="rating" value="1" /><label for="r1">★</label>
                        </div>
                        <textarea name="review_text" class="form-control" rows="3" placeholder="How was your trip?" style="font-size:0.9rem; margin-bottom:12px;"></textarea>
                        <button type="submit" name="submit_review" class="btn btn-primary" style="width: 100%; padding: 10px;">Post Review</button>
                    </form>
                </div>
                <?php
                if (isset($_POST['submit_review'])) {
                    $rating = intval($_POST['rating']);
                    $review_text = trim($_POST['review_text']);
                    $tm_id = $trip['created_by'];
                    $ins_rev = $conn->prepare("INSERT INTO reviews (trip_id, student_id, tripmaker_id, rating, review_text) VALUES (?, ?, ?, ?, ?)");
                    $ins_rev->bind_param("iiiis", $trip_id, $user_id, $tm_id, $rating, $review_text);
                    if ($ins_rev->execute()) {
                        echo "<script>window.location.href='trip.php?id=$trip_id&msg=reviewed';</script>";
                        exit();
                    }
                }
                ?>
            <?php endif; } ?>

            <div class="reviews-list">
                <?php
                $rev_stmt = $conn->prepare("SELECT r.*, u.name FROM reviews r JOIN users u ON r.student_id = u.id WHERE r.trip_id = ? ORDER BY r.created_at DESC LIMIT 3");
                $rev_stmt->bind_param("i", $trip_id);
                $rev_stmt->execute();
                $revs = $rev_stmt->get_result();
                if ($revs->num_rows > 0) {
                    while($rev = $revs->fetch_assoc()) {
                        echo '<div style="padding: 15px 0; border-bottom: 1px solid #edf2f7; last-child { border: none; }">';
                        echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">';
                        echo '<strong style="font-size: 0.95rem;">'.htmlspecialchars($rev['name']).'</strong>';
                        echo '<span style="color: #F6E05E; font-size: 0.8rem;">'.str_repeat("★", $rev['rating']).'</span>';
                        echo '</div>';
                        if (!empty($rev['review_text'])) echo '<p style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 4px;">'.htmlspecialchars($rev['review_text']).'</p>';
                        echo '<small style="color: #cbd5e0; font-size: 0.75rem;">'.date('M Y', strtotime($rev['created_at'])).'</small>';
                        echo '</div>';
                    }
                } else {
                    echo '<p style="color: var(--text-light); font-size: 0.9rem; text-align: center; padding: 20px;">No reviews yet.</p>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-scroll chat to bottom
    const chatBox = document.getElementById("chat-box");
    if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;

    // Toggle Checklist items locally (visual only)
    document.querySelectorAll('.checklist-item input').forEach(cb => {
        cb.addEventListener('change', function() {
            if (this.checked) this.nextElementSibling.style.textDecoration = 'line-through';
            else this.nextElementSibling.style.textDecoration = 'none';
        });
    });
</script>

<?php include 'footer.php'; ?>

