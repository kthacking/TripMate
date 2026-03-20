<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Fetch ALL active trips ──────────────────────────────
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? trim($_GET['type']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';

$where = "t.status = 'active'";
$params = [];
$types_str = '';

// Search
if ($search !== '') {
    $where .= " AND (t.title LIKE ? OR t.destination LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types_str .= 'ss';
}

// Type filter
$validTypes = ['Leisure', 'Adventure', 'Educational', 'Religious', 'Budget', 'Luxury'];
if ($type_filter !== '' && in_array($type_filter, $validTypes)) {
    $where .= " AND t.trip_type = ?";
    $params[] = $type_filter;
    $types_str .= 's';
}

// Sort
$orderBy = "t.created_at DESC";
if ($sort === 'price_low')
    $orderBy = "t.cost ASC";
if ($sort === 'price_high')
    $orderBy = "t.cost DESC";
if ($sort === 'popular')
    $orderBy = "enrolled_count DESC";
if ($sort === 'rating')
    $orderBy = "avg_rating DESC";

$sql = "SELECT t.*, u.name as organizer,
        (SELECT COUNT(*) FROM enrollments e WHERE e.trip_id = t.id AND e.status='approved') as enrolled_count,
        (SELECT IFNULL(AVG(r.rating),0) FROM reviews r WHERE r.trip_id = t.id) as avg_rating,
        (SELECT COUNT(*) FROM reviews r WHERE r.trip_id = t.id) as review_count
        FROM trips t
        JOIN users u ON t.created_by = u.id
        WHERE $where
        ORDER BY $orderBy";

$trips = [];
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types_str, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
}
else {
    $result = $conn->query($sql);
}

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $trips[] = $row;
    }
}

// Stats for header
$total_trips = count($trips);
$total_destinations = count(array_unique(array_column($trips, 'destination')));
?>
<?php include 'header.php'; ?>

<!-- ━━━ Page Hero Banner ━━━ -->
<div class="pt-hero">
    <div class="container" style="position: relative; z-index: 2;">
        <div class="pt-hero-content">
            <span class="pt-hero-badge"><i class="ri-fire-fill"></i> Trending Destinations</span>
            <h1 class="pt-hero-title">Popular Trips</h1>
            <p class="pt-hero-sub">Discover curated experiences from our top TripMakers. Browse, explore, and reserve your spot!</p>
            <div class="pt-hero-stats">
                <div class="pt-stat">
                    <strong><?php echo $total_trips; ?></strong>
                    <span>Active Trips</span>
                </div>
                <div class="pt-stat-divider"></div>
                <div class="pt-stat">
                    <strong><?php echo $total_destinations; ?></strong>
                    <span>Destinations</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ━━━ Filters Bar ━━━ -->
<div class="container" style="margin-top: -30px; position: relative; z-index: 10;">
    <form method="GET" class="pt-filters-bar" id="filtersForm">
        <!-- Search -->
        <div class="pt-search-wrap">
            <i class="ri-search-line"></i>
            <input type="text" name="search" class="pt-search-input" placeholder="Search trips or destinations..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <!-- Type -->
        <select name="type" class="pt-filter-select" onchange="document.getElementById('filtersForm').submit();">
            <option value="">All Types</option>
            <?php foreach ($validTypes as $vt): ?>
                <option value="<?php echo $vt; ?>" <?php echo($type_filter === $vt) ? 'selected' : ''; ?>><?php echo $vt; ?></option>
            <?php endforeach; ?>
        </select>
        <!-- Sort -->
        <select name="sort" class="pt-filter-select" onchange="document.getElementById('filtersForm').submit();">
            <option value="newest"     <?php echo($sort === 'newest') ? 'selected' : ''; ?>>Newest First</option>
            <option value="price_low"  <?php echo($sort === 'price_low') ? 'selected' : ''; ?>>Price: Low → High</option>
            <option value="price_high" <?php echo($sort === 'price_high') ? 'selected' : ''; ?>>Price: High → Low</option>
            <option value="popular"    <?php echo($sort === 'popular') ? 'selected' : ''; ?>>Most Popular</option>
            <option value="rating"     <?php echo($sort === 'rating') ? 'selected' : ''; ?>>Top Rated</option>
        </select>
        <button type="submit" class="pt-search-btn"><i class="ri-search-line"></i> Search</button>
    </form>
</div>

<!-- ━━━ Trips Grid ━━━ -->
<div class="container section" style="padding-top: 60px; padding-bottom: 80px;">
    
    <?php if ($search !== '' || $type_filter !== ''): ?>
        <div style="margin-bottom: 30px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <span style="color: var(--text-light); font-size: 1rem;">
                Showing <strong style="color: var(--secondary-color);"><?php echo $total_trips; ?></strong> result(s)
                <?php if ($search !== ''): ?>
                    for "<strong style="color: var(--primary-color);"><?php echo htmlspecialchars($search); ?></strong>"
                <?php endif; ?>
                <?php if ($type_filter !== ''): ?>
                    in <span style="background: rgba(234, 88, 12, 0.1); color: var(--primary-color); padding: 4px 10px; border-radius: 8px; font-weight: 700; font-size: 0.85rem;"><?php echo htmlspecialchars($type_filter); ?></span>
                <?php endif; ?>
            </span>
            <a href="popular_trips.php" style="font-size: 0.9rem; color: #dc2626; font-weight: 600; text-decoration: none; padding-left: 10px; border-left: 1px solid #e2e8f0;">
                <i class="ri-close-circle-line"></i> Clear Filters
            </a>
        </div>
    <?php endif; ?>

    <?php if (count($trips) > 0): ?>
    <div class="popular-trips-grid">
        <?php foreach ($trips as $t):
        $stars_display = str_repeat("★", intval($t['comfort_level'])) . str_repeat("☆", 5 - intval($t['comfort_level']));
        $d1 = new DateTime($t['start_date']);
        $d2 = new DateTime($t['end_date']);
        $duration = $d1->diff($d2)->days + 1;
        $avg = round(floatval($t['avg_rating']), 1);
        $days_until = ceil((strtotime($t['start_date']) - time()) / 86400);
        ?>
        <div class="pop-trip-card">
            <!-- Image -->
            <div class="pop-trip-img-wrap">
                <?php if (!empty($t['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($t['image_url']); ?>" alt="<?php echo htmlspecialchars($t['title']); ?>" loading="lazy">
                <?php else: ?>
                    <div class="pop-trip-img-placeholder">
                        <i class="ri-landscape-line"></i>
                    </div>
                <?php endif; ?>
                <div class="trip-img-badges">
                    <span class="trip-type-tag"><?php echo htmlspecialchars($t['trip_type']); ?></span>
                    <?php if ($days_until > 0): ?>
                        <span class="trip-type-tag" style="background:#f0fdf4; color:#16a34a;">
                            <i class="ri-time-line" style="font-size:0.75rem;"></i> In <?php echo $days_until; ?> days
                        </span>
                    <?php endif; ?>
                </div>
                <div class="trip-price-badge">$<?php echo number_format($t['cost']); ?></div>
            </div>

            <!-- Content -->
            <div class="pop-trip-content">
                <div class="trip-meta" style="margin-bottom: 8px;">
                    <span><i class="ri-map-pin-2-fill"></i> <?php echo htmlspecialchars($t['destination']); ?></span>
                </div>
                
                <h3 class="trip-title"><?php echo htmlspecialchars($t['title']); ?></h3>
                
                <div class="trip-meta" style="color: var(--text-light); border-bottom: none; margin-bottom: 2px;">
                    <span><i class="ri-calendar-event-line"></i> <?php echo $duration; ?> Days Trip</span>
                </div>

                <!-- Date Range -->
                <div style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 12px; display:flex; align-items:center; gap: 5px;">
                    <i class="ri-calendar-line" style="color: var(--primary-color);"></i>
                    <?php echo date('M d', strtotime($t['start_date'])); ?> – <?php echo date('M d, Y', strtotime($t['end_date'])); ?>
                </div>

                <!-- Info Row -->
                <div class="pop-trip-info-row">
                    <!-- Rating -->
                    <div class="pop-trip-rating">
                        <span class="pop-trip-stars"><?php echo $stars_display; ?></span>
                        <?php if ($t['review_count'] > 0): ?>
                            <span class="pop-trip-rating-num" style="font-weight:700; color:var(--secondary-color); margin-left:4px;"><?php echo $avg; ?> <span style="font-weight:500; color:var(--text-light);">out of 5</span></span>
                        <?php else: ?>
                            <span class="pop-trip-rating-num" style="background:var(--bg-light); padding:2px 6px; border-radius:4px;">No reviews yet</span>
                        <?php endif; ?>
                    </div>
                    <!-- Enrolled -->
                    <div class="pop-trip-enrolled" title="Enrolled Users">
                        <i class="ri-group-fill"></i> 
                        <?php echo $t['enrolled_count']; ?> / <?php echo $t['max_participants'] > 0 ? $t['max_participants'] : '∞'; ?>
                    </div>
                </div>

                <!-- Footer -->
                <div class="pop-trip-footer">
                    <div class="pop-trip-organizer">
                        <div class="pop-trip-avatar"><?php echo strtoupper(substr($t['organizer'], 0, 1)); ?></div>
                        <span>By <?php echo htmlspecialchars($t['organizer']); ?></span>
                    </div>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="trip.php?id=<?php echo $t['id']; ?>" class="trip-cta-btn">
                            Book <i class="ri-arrow-right-s-line" style="position:relative; top:1px;"></i>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="trip-cta-btn">
                            Book <i class="ri-arrow-right-s-line" style="position:relative; top:1px;"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php else: ?>
    <!-- Empty State -->
    <div class="pt-empty-state">
        <div class="pt-empty-icon">
            <i class="ri-plane-line"></i>
        </div>
        <h3>No Trips Found</h3>
        <?php if ($search !== '' || $type_filter !== ''): ?>
            <p>Try adjusting your search or filters to find what you're looking for.</p>
            <a href="popular_trips.php" class="trip-cta-btn" style="display:inline-block; margin-top: 16px;">
                <i class="ri-restart-line"></i> Clear Filters
            </a>
        <?php else: ?>
            <p>Our TripMakers are crafting amazing experiences. Check back soon for exciting destinations!</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ━━━ Styles ━━━ -->
<style>
/* ── Theme Definitions ── */
:root {
    --primary-color: #ea580c;
    --primary-hover: #c2410c;
    --secondary-color: #1e293b;
    --text-color: #334155;
    --text-light: #64748b;
    --bg-light: #f8fafc;
    --white: #ffffff;
    --radius-sm: 12px;
    --radius-md: 20px;
    --radius-lg: 30px;
    --shadow-sm: 0 4px 6px rgba(0,0,0,0.05);
    --shadow-md: 0 10px 25px rgba(0,0,0,0.08);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ── Hero Banner ── */
.pt-hero {
    position: relative;
    padding: 140px 0 100px;
    overflow: hidden;
    margin-top: -85px;
    background: linear-gradient(135deg, #fffaf5 0%, #ffffff 100%);
}
.pt-hero-content {
    text-align: center;
    color: var(--secondary-color);
    max-width: 640px;
    margin: 0 auto;
}
.pt-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(234, 88, 12, 0.1);
    color: var(--primary-color);
    font-size: 0.8rem;
    font-weight: 800;
    padding: 8px 18px;
    border-radius: 50px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 24px;
    border: 1px solid rgba(234, 88, 12, 0.2);
}
.pt-hero-title {
    font-size: 3.5rem;
    font-weight: 800;
    margin-bottom: 16px;
    line-height: 1.1;
    letter-spacing: -1.5px;
    color: var(--secondary-color);
}
.pt-hero-sub {
    font-size: 1.15rem;
    color: var(--text-light);
    line-height: 1.6;
    margin-bottom: 35px;
}
.pt-hero-stats {
    display: inline-flex;
    align-items: center;
    gap: 28px;
    background: var(--white);
    padding: 16px 36px;
    border-radius: var(--radius-md);
    border: 1px solid #edf2f7;
    box-shadow: var(--shadow-sm);
}
.pt-stat { text-align: center; }
.pt-stat strong { display: block; font-size: 1.8rem; font-weight: 900; color: var(--secondary-color); line-height: 1; margin-bottom: 4px; }
.pt-stat span { font-size: 0.8rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
.pt-stat-divider { width: 1px; height: 36px; background: #edf2f7; }

/* ── Filters Bar ── */
.pt-filters-bar {
    display: flex;
    gap: 15px;
    align-items: center;
    background: var(--white);
    padding: 15px 20px;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-md);
    border: 1px solid #f1f5f9;
    flex-wrap: wrap;
}
.pt-search-wrap { flex: 1; min-width: 250px; position: relative; display: flex; align-items: center; }
.pt-search-wrap i { position: absolute; left: 16px; color: #94a3b8; font-size: 1.2rem; }
.pt-search-input { width: 100%; padding: 14px 14px 14px 44px; border: 1px solid #e2e8f0; border-radius: 14px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; transition: var(--transition); color: var(--text-color); }
.pt-search-input:focus { outline: none; border-color: var(--primary-color); background: white; box-shadow: 0 0 0 4px rgba(234, 88, 12, 0.1); }
.pt-filter-select { padding: 14px 16px; border: 1px solid #e2e8f0; border-radius: 14px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; color: var(--secondary-color); cursor: pointer; min-width: 170px; transition: var(--transition); font-weight: 500; }
.pt-filter-select:focus { outline: none; border-color: var(--primary-color); background: white; box-shadow: 0 0 0 4px rgba(234, 88, 12, 0.1); }
.pt-search-btn { display: inline-flex; align-items: center; gap: 8px; padding: 14px 30px; background: var(--primary-color); color: white; border: none; border-radius: 14px; font-family: inherit; font-size: 0.95rem; font-weight: 700; cursor: pointer; transition: var(--transition); box-shadow: 0 4px 14px rgba(234, 88, 12, 0.25); }
.pt-search-btn:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(234, 88, 12, 0.4); }

/* ── Grid & Cards ── */
.popular-trips-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 35px; }

.pop-trip-card { background: var(--white); border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-sm); transition: var(--transition); border: 1px solid #f1f5f9; display: flex; flex-direction: column; }
.pop-trip-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-md); border-color: #ffedd5; }

/* Image Area */
.pop-trip-img-wrap { width: 100%; height: 240px; overflow: hidden; position: relative; }
.pop-trip-img-wrap img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; }
.pop-trip-card:hover .pop-trip-img-wrap img { transform: scale(1.05); }

.trip-img-badges { position: absolute; top: 18px; left: 18px; display: flex; gap: 8px; flex-wrap: wrap; z-index: 2; }
.trip-type-tag { background: rgba(255,255,255,0.95); padding: 6px 14px; border-radius: 20px; font-weight: 800; font-size: 0.8rem; color: var(--primary-color); backdrop-filter: blur(4px); letter-spacing: 0.5px; text-transform: uppercase; }

.trip-price-badge { position: absolute; bottom: 18px; right: 18px; background: var(--secondary-color); color: white; padding: 8px 16px; border-radius: 20px; font-weight: 900; font-size: 1.1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.2); z-index: 2; }
.pop-trip-img-placeholder { width: 100%; height: 100%; background: #fffaf5; display: flex; align-items: center; justify-content: center; font-size: 4rem; color: #fed7aa; }

/* Content Area */
.pop-trip-content { padding: 25px; flex-grow: 1; display: flex; flex-direction: column; }
.trip-meta { color: var(--text-light); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 6px; }
.trip-meta i { color: var(--primary-color); font-size: 1rem; }
.trip-title { font-size: 1.4rem; font-weight: 800; margin-bottom: 12px; color: var(--secondary-color); line-height: 1.3; letter-spacing: -0.5px; }

/* Info Row */
.pop-trip-info-row { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; margin: auto 0 20px; }
.pop-trip-rating { display: flex; align-items: center; }
.pop-trip-stars { color: #fbbf24; font-size: 1rem; letter-spacing: 2px; }
.pop-trip-rating-num { font-size: 0.85rem; }
.pop-trip-enrolled { font-size: 0.85rem; color: var(--text-color); font-weight: 700; display: flex; align-items: center; gap: 6px; }
.pop-trip-enrolled i { color: var(--primary-color); font-size: 1.1rem; }

/* Footer Area */
.pop-trip-footer { display: flex; justify-content: space-between; align-items: center; }
.pop-trip-organizer { display: flex; align-items: center; gap: 12px; font-size: 0.9rem; color: var(--text-light); font-weight: 600; }
.pop-trip-avatar { width: 40px; height: 40px; border-radius: 50%; background: #ffedd5; color: var(--primary-color); display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: 900; }

.trip-cta-btn { background: var(--primary-color); color: white; padding: 12px 24px; border-radius: 50px; font-weight: 800; font-size: 0.95rem; text-decoration: none; transition: var(--transition); border: none; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
.trip-cta-btn:hover { background: var(--primary-hover); transform: translateY(-3px); box-shadow: 0 6px 18px rgba(234, 88, 12, 0.3); color: white; }

/* ── Empty State ── */
.pt-empty-state { text-align: center; padding: 100px 20px; background: white; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border: 1px solid #f1f5f9; margin-top: 20px; }
.pt-empty-icon { font-size: 5rem; color: #e2e8f0; margin-bottom: 24px; }
.pt-empty-state h3 { color: var(--secondary-color); margin-bottom: 12px; font-size: 1.8rem; font-weight: 800; }
.pt-empty-state p { color: var(--text-light); max-width: 450px; margin: 0 auto; font-size: 1.05rem; line-height: 1.6; }

/* ── Responsive ── */
@media (max-width: 768px) {
    .pt-hero-title { font-size: 2.5rem; }
    .pt-filters-bar { flex-direction: column; align-items: stretch; border-radius: 16px; padding: 20px; }
    .pt-search-wrap { width: 100%; }
    .pt-filter-select { width: 100%; }
    .popular-trips-grid { grid-template-columns: 1fr; }
    .pop-trip-img-wrap { height: 220px; }
}
@media (max-width: 480px) {
    .pt-hero { padding: 120px 0 60px; }
    .pt-hero-stats { padding: 12px 20px; gap: 15px; flex-direction: column; border-radius: var(--radius-sm); }
    .pt-stat-divider { width: 100%; height: 1px; }
}
</style>

<?php include 'footer.php'; ?>

