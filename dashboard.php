<?php
require_once 'db.php';
require_once 'auth.php';
session_start();
checkLogin();
include 'header.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
?>

<div class="container dashboard-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
        <div>
            <h1 style="color: var(--secondary-color);">Dashboard</h1>
            <p style="color: var(--text-light);">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>.</p>
        </div>
        <?php if ($role == 'tripmaker' || $role == 'admin'): ?>
            <a href="create_trip.php" class="btn btn-primary">+ Create New Trip</a>
        <?php endif; ?>
    </div>

    <!-- STUDENT VIEW -->
    <?php if ($role == 'student'): ?>
        
        <!-- My Trips Section -->
        <h2 style="margin-bottom: 24px; font-size: 1.5rem;">Your Adventures</h2>
        <div class="trip-grid" style="margin-top: 20px;">
            <?php
            $sql = "SELECT t.*, e.status as enroll_status FROM trips t 
                    JOIN enrollments e ON t.id = e.trip_id 
                    WHERE e.student_id = $user_id ORDER BY e.request_date DESC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0):
                while($row = $result->fetch_assoc()):
            ?>
                <div class="trip-card">
                    <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="Trip" class="trip-image">
                    <div class="trip-content">
                        <h3 class="trip-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                        <div class="trip-meta">
                            <span><i class="ri-map-pin-line"></i> <?php echo htmlspecialchars($row['destination']); ?></span>
                        </div>
                        <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: center;">
                            <span style="
                                padding: 6px 12px; 
                                border-radius: 20px; 
                                font-size: 0.85rem; 
                                font-weight: 600; 
                                background: <?php echo $row['enroll_status'] == 'approved' ? '#dbfce1' : '#feebc8'; ?>; 
                                color: <?php echo $row['enroll_status'] == 'approved' ? '#2f855a' : '#c05621'; ?>;
                            ">
                                <?php echo ucfirst($row['enroll_status']); ?>
                            </span>
                            <a href="trip.php?id=<?php echo $row['id']; ?>" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.9rem;">View</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; else: ?>
                <p style="color: var(--text-light); grid-column: 1/-1;">You haven't joined any trips yet.</p>
            <?php endif; ?>
        </div>

        <!-- Explore Section -->
        <h2 style="margin-top: 60px; margin-bottom: 24px; font-size: 1.5rem;">Explore New Trips</h2>
        <!-- Search Bar -->
        <form action="" method="GET" style="margin-bottom: 30px; display: flex; gap: 10px; max-width: 500px;">
            <input type="text" name="search" placeholder="Search by ID or details..." class="form-control" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <div class="trip-grid" style="margin-top: 20px;">
            <?php
            $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
            $search_sql = "";
            if($search) {
                // If numeric, search by ID, else title/destination
                if(is_numeric($search)) {
                    $search_sql = " AND id = $search";
                } else {
                    $search_sql = " AND (title LIKE '%$search%' OR destination LIKE '%$search%')";
                }
            }
            
            // Show trips NOT joined by this user
            $sql = "SELECT * FROM trips WHERE status='active' 
                    AND id NOT IN (SELECT trip_id FROM enrollments WHERE student_id = $user_id) 
                    $search_sql 
                    ORDER BY created_at DESC LIMIT 12";
            $result = $conn->query($sql);
            if ($result->num_rows > 0):
                while($row = $result->fetch_assoc()):
            ?>
                <div class="trip-card">
                    <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="Trip" class="trip-image">
                    <div class="trip-content">
                        <div style="font-size:0.8rem; color:var(--text-light); margin-bottom:4px;">ID: #<?php echo $row['id']; ?></div>
                        <h3 class="trip-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                        <div class="trip-meta">
                            <span><i class="ri-map-pin-line"></i> <?php echo htmlspecialchars($row['destination']); ?></span>
                            <span><i class="ri-calendar-line"></i> <?php echo date('M d', strtotime($row['start_date'])); ?></span>
                        </div>
                        <div class="trip-price">$<?php echo number_format($row['cost'], 0); ?></div>
                        <div class="trip-footer">
                            <a href="trip.php?id=<?php echo $row['id']; ?>" class="btn btn-outline" style="width: 100%; text-align: center;">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; else: ?>
                <p style="color: var(--text-light); grid-column: 1/-1;">No new trips found.</p>
            <?php endif; ?>
        </div>

    <?php endif; ?>

    <!-- TRIPMAKER / ADMIN VIEW -->
    <?php if ($role == 'tripmaker' || $role == 'admin'): ?>
        
        <!-- Pending Requests -->
        <h2 style="margin-bottom: 24px; font-size: 1.5rem;">Join Requests</h2>
        <div style="background: var(--white); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 60px;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f8fafc; border-bottom: 1px solid #edf2f7;">
                    <tr>
                        <th style="padding: 16px; text-align: left; color: var(--text-light); font-weight: 600;">Trip</th>
                        <th style="padding: 16px; text-align: left; color: var(--text-light); font-weight: 600;">Student</th>
                        <th style="padding: 16px; text-align: left; color: var(--text-light); font-weight: 600;">Date</th>
                        <th style="padding: 16px; text-align: right; color: var(--text-light); font-weight: 600;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Get requests for trips created by this user
                    $extra_sql = ($role == 'admin') ? "" : "AND t.created_by = $user_id";
                    $sql = "SELECT e.id as req_id, t.title, u.name as student_name, e.request_date 
                            FROM enrollments e 
                            JOIN trips t ON e.trip_id = t.id 
                            JOIN users u ON e.student_id = u.id 
                            WHERE e.status = 'pending' $extra_sql";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0):
                        while($row = $result->fetch_assoc()):
                    ?>
                        <tr style="border-bottom: 1px solid #edf2f7;">
                            <td style="padding: 16px;"><?php echo htmlspecialchars($row['title']); ?></td>
                            <td style="padding: 16px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width: 32px; height: 32px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: var(--secondary-color);">
                                        <?php echo substr($row['student_name'], 0, 1); ?>
                                    </div>
                                    <?php echo htmlspecialchars($row['student_name']); ?>
                                </div>
                            </td>
                            <td style="padding: 16px; color: var(--text-light);"><?php echo date('M d, Y', strtotime($row['request_date'])); ?></td>
                            <td style="padding: 16px; text-align: right;">
                                <a href="actions.php?action=approve_request&req_id=<?php echo $row['req_id']; ?>" style="color: #2f855a; font-weight: 600; margin-right: 16px;">Approve</a>
                                <a href="actions.php?action=reject_request&req_id=<?php echo $row['req_id']; ?>" style="color: #c53030; font-weight: 600;">Reject</a>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="4" style="padding: 24px; text-align: center; color: var(--text-light);">No pending requests.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Managed Trips -->
        <h2 style="margin-bottom: 24px; font-size: 1.5rem;">Managed Trips</h2>
        <div class="trip-grid">
            <?php
            $sql = ($role == 'admin') ? "SELECT * FROM trips" : "SELECT * FROM trips WHERE created_by = $user_id";
            $result = $conn->query($sql);
            if ($result->num_rows > 0):
                while($row = $result->fetch_assoc()):
            ?>
                <div class="trip-card">
                    <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="Trip" class="trip-image">
                    <div class="trip-content">
                        <h3 class="trip-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                        <div class="trip-meta">
                            <span>#<?php echo $row['id']; ?></span>
                            <span><?php echo count(explode(',', 'student1,student2')); // Mock count ?> Joined</span>
                        </div>
                        <div class="trip-price">$<?php echo number_format($row['cost'], 0); ?></div>
                        <div class="trip-footer">
                            <a href="trip.php?id=<?php echo $row['id']; ?>" class="btn btn-outline" style="flex: 1; text-align: center; margin-right: 8px;">Manage</a>
                            <a href="actions.php?action=delete_trip&trip_id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?');" style="color: #cbd5e0; padding: 8px;"><i class="ri-delete-bin-line"></i></a>
                        </div>
                    </div>
                </div>
            <?php endwhile; else: ?>
                <p style="color: var(--text-light); grid-column: 1/-1;">You haven't created any trips yet.</p>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>
