<?php
require_once 'admin_header.php';

// Stats aggregation
$most_popular_trips = $conn->query("SELECT t.title, COUNT(e.id) as enrolls 
                                    FROM trips t 
                                    LEFT JOIN enrollments e ON t.id = e.trip_id AND e.status = 'approved' 
                                    GROUP BY t.id ORDER BY enrolls DESC LIMIT 5");

$most_active_tm = $conn->query("SELECT u.name, COUNT(t.id) as trip_count 
                                FROM users u 
                                JOIN trips t ON u.id = t.created_by 
                                GROUP BY u.id ORDER BY trip_count DESC LIMIT 5");

// Request success rate
$all_req = $conn->query("SELECT COUNT(*) as c FROM enrollments")->fetch_assoc()['c'] ?: 1;
$app_req = $conn->query("SELECT COUNT(*) as c FROM enrollments WHERE status='approved'")->fetch_assoc()['c'];
$success_rate = round(($app_req / $all_req) * 100, 1);

// Media trends (monthly count)
$media_trend = $conn->query("SELECT DATE_FORMAT(uploaded_at, '%Y-%m') as month, COUNT(*) as count 
                             FROM media GROUP BY month ORDER BY month DESC LIMIT 6");

// Trips by type
$type_stats = $conn->query("SELECT trip_type, COUNT(*) as c FROM trips GROUP BY trip_type");
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px;">
    
    <!-- Most Popular Trips -->
    <div style="background: white; border-radius: 16px; padding: 25px; box-shadow: var(--shadow-sm);">
        <h3 style="font-size: 1.1rem; color: #111827; margin-bottom: 20px;">Most Popular Trips</h3>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php while($t = $most_popular_trips->fetch_assoc()): ?>
            <div>
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 8px;">
                    <span style="font-weight: 600;"><?php echo htmlspecialchars($t['title']); ?></span>
                    <span style="color: #6B7280;"><?php echo $t['enrolls']; ?> Explorers</span>
                </div>
                <div style="width: 100%; height: 8px; background: #EEE; border-radius: 4px; overflow: hidden;">
                    <div style="width: <?php echo min(100, $t['enrolls'] * 10); ?>%; height: 100%; background: var(--admin-primary);"></div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Active TripMakers -->
    <div style="background: white; border-radius: 16px; padding: 25px; box-shadow: var(--shadow-sm);">
        <h3 style="font-size: 1.1rem; color: #111827; margin-bottom: 20px;">Top TripMakers</h3>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php while($tm = $most_active_tm->fetch_assoc()): ?>
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; border-radius: 12px; border: 1px solid #F3F4F6;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 32px; height: 32px; background: #F3F4F6; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #6B7280; font-size: 0.75rem;">
                        <?php echo strtoupper(substr($tm['name'], 0, 1)); ?>
                    </div>
                    <span style="font-weight: 600; font-size: 0.9rem;"><?php echo htmlspecialchars($tm['name']); ?></span>
                </div>
                <span class="admin-badge" style="background: #E0E7FF; color: #4338CA;"><?php echo $tm['trip_count']; ?> Trips</span>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

</div>

<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 30px;">
    
    <!-- Success Rate -->
    <div style="background: white; border-radius: 16px; padding: 25px; box-shadow: var(--shadow-sm); text-align: center;">
        <h4 style="font-size: 0.9rem; color: #6B7280; margin-bottom: 15px;">Approval Rate</h4>
        <div style="position: relative; width: 120px; height: 120px; margin: 0 auto 20px;">
            <svg viewBox="0 0 36 36" style="width: 100%; height: 100%;">
                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#EEE" stroke-width="3" />
                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#4338CA" stroke-width="3" stroke-dasharray="<?php echo $success_rate; ?>, 100" />
            </svg>
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 1.5rem; font-weight: 800; color: #111827;"><?php echo $success_rate; ?>%</div>
        </div>
        <div style="font-size: 0.8rem; color: #6B7280;">Percentage of join requests that are approved system-wide.</div>
    </div>

    <!-- Media Trends -->
    <div style="background: white; border-radius: 16px; padding: 25px; box-shadow: var(--shadow-sm); grid-column: span 2;">
        <h3 style="font-size: 1.1rem; color: #111827; margin-bottom: 25px;">Media Upload Trends</h3>
        <div style="display: flex; align-items: flex-end; justify-content: space-around; height: 200px; padding-bottom: 30px; position: relative; border-bottom: 1px solid #EEE;">
            <?php while($row = $media_trend->fetch_assoc()): ?>
            <div style="display: flex; flex-direction: column; align-items: center; width: 12%;">
                <div style="width: 100%; min-height: 5px; height: <?php echo min(100, $row['count'] * 10); ?>%; background: #EEF2FF; border: 1px solid #C7D2FE; border-radius: 6px 6px 0 0; position: relative;">
                    <div style="position: absolute; top: -25px; left: 0; right: 0; text-align: center; font-size: 0.75rem; font-weight: 700; color: #4338CA;"><?php echo $row['count']; ?></div>
                </div>
                <div style="margin-top: 10px; font-size: 0.7rem; color: #9CA3AF; white-space: nowrap;"><?php echo $row['month']; ?></div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

</div>

<?php require_once 'admin_footer.php'; ?>
